<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\IdentityVerification;
use App\Models\Review;
use App\Models\Statistic;
use App\Models\User;
use App\Services\Owner\OwnerStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Controller pour le dashboard propriétaire
 */
class OwnerController extends Controller
{
    public function __construct(
        private readonly OwnerStatsService $stats,
    ) {
    }

    /**
     * Dashboard propriétaire avec statistiques complètes
     */
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $cacheKey = "owner_dashboard:{$user->id}";
        $cacheTtl = 300; // 5 minutes

        // Mes résidences (les 5 dernières) — toujours frais
        $residences = $user->residences()
            ->with(['photos', 'amenities'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // ── Données lourdes cachées 5 min ──
        $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($user) {
            $residenceIds = $user->residences()->pluck('id');

            $stats = $this->stats->calculateStats($user);
            $revenueData = $this->stats->calculateRevenue($residenceIds);
            $viewsTrend = $this->stats->calculateViewsTrend($user, $residenceIds);
            $bookingsData = $this->stats->getBookingsData($residenceIds);
            $earningsData = $this->stats->getEarningsData($user, $residenceIds);
            $reviewsData = $this->stats->getReviewsData($residenceIds);
            $responseMetrics = $this->stats->getResponseMetrics($user);
            $hostScore = $this->stats->calculateHostScore($user, $stats, $reviewsData, $responseMetrics);

            // Stats journalières sur 30 jours
            $dailyStats = Statistic::whereIn('residence_id', $residenceIds)
                ->where('stat_date', '>=', now()->subDays(30))
                ->selectRaw('stat_date, SUM(views) as views, SUM(contacts) as contacts')
                ->groupBy('stat_date')
                ->orderBy('stat_date')
                ->get()
                ->keyBy(fn ($item) => $item->stat_date->format('Y-m-d'));

            $chartData = collect();
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $dayData = $dailyStats->get($date);
                $chartData->push([
                    'date' => $date,
                    'label' => now()->subDays($i)->format('d/m'),
                    'views' => $dayData ? (int) $dayData->views : 0,
                    'contacts' => $dayData ? (int) $dayData->contacts : 0,
                ]);
            }

            // Distribution des étoiles
            $starDistribution = $reviewsData['total'] > 0
                ? Review::whereIn('residence_id', $residenceIds)
                    ->where('status', 'approved')
                    ->selectRaw('rating, COUNT(*) as count')
                    ->groupBy('rating')
                    ->orderBy('rating', 'desc')
                    ->pluck('count', 'rating')
                    ->toArray()
                : [];

            return compact(
                'stats',
                'revenueData',
                'viewsTrend',
                'bookingsData',
                'earningsData',
                'reviewsData',
                'responseMetrics',
                'hostScore',
                'chartData',
                'starDistribution',
                'residenceIds',
            );
        });

        // Extraire les données cachées
        extract($cached);

        // ── Données temps-réel (non cachées) ──
        $recentContacts = Contact::where('owner_id', $user->id)
            ->with(['user:id,name,email,phone', 'residence:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $conversionRate = $stats['total_views'] > 0
            ? round(($stats['total_contacts'] / $stats['total_views']) * 100, 1)
            : 0;

        $todayTasks = $this->stats->getTodayTasks($user, $stats, $residences);

        $hour = (int) now()->format('H');
        $greeting = match (true) {
            $hour < 12 => 'Bonjour',
            $hour < 18 => 'Bon après-midi',
            default => 'Bonsoir',
        };

        $unreadMessages = Conversation::where(function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->sum('unread_owner_count');

        $identityVerification = IdentityVerification::where('user_id', $user->id)
            ->latest()
            ->first();
        $verificationStatus = $identityVerification?->status;

        $recentMessages = Conversation::where('owner_id', $user->id)
            ->whereHas('messages')
            ->with(['user:id,name,profile_photo,avatar', 'residence:id,name', 'latestMessage'])
            ->orderBy('last_message_at', 'desc')
            ->take(3)
            ->get();

        $totalOwners = Cache::remember('total_owners_count', 3600, fn () => User::where('role', 'owner')->count());

        $calendarEvents = Booking::whereIn('residence_id', $residenceIds)
            ->where(function ($q) {
                $q->whereBetween('check_in', [now()->toDateString(), now()->addDays(7)->toDateString()])
                    ->orWhereBetween('check_out', [now()->toDateString(), now()->addDays(7)->toDateString()]);
            })
            ->whereIn('status', ['confirmed', 'pending'])
            ->with(['user:id,name', 'residence:id,name'])
            ->orderBy('check_in')
            ->take(5)
            ->get();

        return view('owner.dashboard', compact(
            'residences',
            'stats',
            'recentContacts',
            'conversionRate',
            'viewsTrend',
            'revenueData',
            'chartData',
            'todayTasks',
            'greeting',
            'bookingsData',
            'earningsData',
            'reviewsData',
            'responseMetrics',
            'hostScore',
            'unreadMessages',
            'verificationStatus',
            'recentMessages',
            'calendarEvents',
            'starDistribution',
            'totalOwners',
        ));
    }
    /**
     * Page statistiques détaillées
     */
    public function statistics(Request $request): View
    {
        $user = $request->user();

        // Statistiques par résidence (1 requête)
        $residenceStats = $user->residences()
            ->select(['id', 'name', 'commune', 'views_count', 'contacts_count', 'status', 'price_per_month', 'price_per_day'])
            ->orderBy('views_count', 'desc')
            ->get()
            ->map(function ($residence) {
                $residence->conversion_rate = $residence->views_count > 0
                    ? round(($residence->contacts_count / $residence->views_count) * 100, 1)
                    : 0;

                return $residence;
            });

        // IDs réutilisables (évite de re-requêter)
        $residenceIds = $residenceStats->pluck('id');

        // Évolution sur les 30 derniers jours
        $dailyStats = Statistic::whereIn('residence_id', $residenceIds)
            ->where('stat_date', '>=', now()->subDays(30))
            ->selectRaw('stat_date, SUM(views) as views, SUM(contacts) as contacts')
            ->groupBy('stat_date')
            ->orderBy('stat_date')
            ->get();

        // Statistiques par commune
        $communeStats = $user->residences()
            ->selectRaw('commune, COUNT(*) as count, SUM(views_count) as views, SUM(contacts_count) as contacts')
            ->groupBy('commune')
            ->orderBy('views', 'desc')
            ->get();

        // Statistiques globales (calculées in-memory depuis residenceStats)
        $viewsThisMonth = Statistic::whereIn('residence_id', $residenceIds)
            ->whereMonth('stat_date', now()->month)
            ->whereYear('stat_date', now()->year)
            ->sum('views');

        $contactsThisMonth = Statistic::whereIn('residence_id', $residenceIds)
            ->whereMonth('stat_date', now()->month)
            ->whereYear('stat_date', now()->year)
            ->sum('contacts');

        // Mois précédent pour comparaison
        $viewsLastMonth = Statistic::whereIn('residence_id', $residenceIds)
            ->whereMonth('stat_date', now()->subMonth()->month)
            ->whereYear('stat_date', now()->subMonth()->year)
            ->sum('views');

        $globalStats = [
            'total_views' => $residenceStats->sum('views_count'),
            'total_contacts' => $residenceStats->sum('contacts_count'),
            'avg_conversion' => $residenceStats->avg('conversion_rate') ?? 0,
            'best_residence' => $residenceStats->first(),
            'views_this_month' => $viewsThisMonth,
            'contacts_this_month' => $contactsThisMonth,
            'views_change' => $viewsLastMonth > 0
                ? round((($viewsThisMonth - $viewsLastMonth) / $viewsLastMonth) * 100)
                : ($viewsThisMonth > 0 ? 100 : 0),
            'total_residences' => $residenceStats->count(),
            'active_residences' => $residenceStats->whereIn('status', ['active', 'approved'])->count(),
        ];

        // Top 5 jours avec le plus de vues
        $topDays = Statistic::whereIn('residence_id', $residenceIds)
            ->selectRaw('stat_date, SUM(views) as total_views')
            ->groupBy('stat_date')
            ->orderBy('total_views', 'desc')
            ->limit(5)
            ->get();

        return view('owner.statistics', compact('residenceStats', 'dailyStats', 'communeStats', 'globalStats', 'topDays'));
    }

    /**
     * Liste des contacts
     */
    public function contacts(Request $request): View
    {
        $user = $request->user();

        $status = $request->get('status');

        $query = Contact::where('owner_id', $user->id)
            ->with(['user:id,name,email,phone,created_at', 'residence:id,name,commune,slug'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        // Recherche par nom ou résidence
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('residence', fn ($r) => $r->where('name', 'like', "%{$search}%"))
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate(20)->withQueryString();

        // Stats pour les filtres (1 requête agrégée)
        $rawContactStats = Contact::where('owner_id', $user->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'viewed' THEN 1 ELSE 0 END) as viewed")
            ->selectRaw("SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) as responded")
            ->first();

        $contactStats = [
            'all' => (int) $rawContactStats->total,
            'pending' => (int) $rawContactStats->pending,
            'viewed' => (int) $rawContactStats->viewed,
            'responded' => (int) $rawContactStats->responded,
        ];

        // KPIs supplémentaires
        $responseRate = $contactStats['all'] > 0
            ? round(($contactStats['responded'] / $contactStats['all']) * 100)
            : 0;

        // Temps de réponse moyen (pour les contacts répondus)
        $avgResponseTime = Contact::where('owner_id', $user->id)
            ->where('status', 'responded')
            ->whereNotNull('responded_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, responded_at)) as avg_hours')
            ->value('avg_hours');
        $avgResponseTime = $avgResponseTime ? round($avgResponseTime, 1) : null;

        // Contacts aujourd'hui
        $todayCount = Contact::where('owner_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        return view('owner.contacts.index', compact(
            'contacts',
            'contactStats',
            'status',
            'responseRate',
            'avgResponseTime',
            'todayCount',
        ));
    }

    /**
     * Marquer un contact comme répondu
     */
    public function markContactAsResponded(Request $request, Contact $contact)
    {
        // Vérifier que le contact appartient au propriétaire
        if ($contact->owner_id !== $request->user()->id) {
            abort(403);
        }

        $contact->markAsResponded();

        return back()->with('success', 'Contact marqué comme répondu');
    }

    /**
     * Notifications du propriétaire (enrichi)
     */
    public function notifications(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('type', 'all');

        $notifications = collect();

        // Contacts en attente
        $pendingContacts = Contact::where('owner_id', $user->id)
            ->where('status', 'pending')
            ->with(['residence:id,name', 'user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($contact) {
                return [
                    'type' => 'contact',
                    'title' => 'Nouvelle demande de contact',
                    'message' => ($contact->user->name ?? 'Un visiteur')." souhaite vous contacter pour {$contact->residence->name}",
                    'action_url' => route('owner.contacts.index', ['status' => 'pending']),
                    'action_text' => 'Voir le contact',
                    'created_at' => $contact->created_at,
                    'is_new' => true,
                ];
            });
        $notifications = $notifications->merge($pendingContacts);

        // Réservations récentes (pending / confirmed ces 7 derniers jours)
        $recentBookings = \App\Models\Booking::whereHas('residence', fn ($q) => $q->where('owner_id', $user->id))
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('created_at', '>=', now()->subDays(7))
            ->with(['user:id,name,first_name', 'residence:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($booking) {
                $isPending = $booking->status === 'pending';

                return [
                    'type' => 'booking',
                    'title' => $isPending ? 'Nouvelle réservation' : 'Réservation confirmée',
                    'message' => ($booking->user->name ?? 'Un voyageur').' — '.($booking->residence->name ?? 'Résidence')
                        .' · '.number_format((float) $booking->total_amount, 0, ',', ' ').' FCFA',
                    'action_url' => route('owner.bookings.show', $booking),
                    'action_text' => 'Voir la réservation',
                    'created_at' => $booking->created_at,
                    'is_new' => $isPending,
                ];
            });
        $notifications = $notifications->merge($recentBookings);

        // Résidences approuvées récemment
        $recentlyApproved = $user->residences()
            ->whereIn('status', ['active', 'approved'])
            ->where('updated_at', '>=', now()->subDays(7))
            ->get()
            ->map(function ($residence) {
                return [
                    'type' => 'approval',
                    'title' => 'Annonce approuvée',
                    'message' => "Votre annonce \"{$residence->name}\" est maintenant visible sur ReziApp",
                    'action_url' => route('residences.show', $residence),
                    'action_text' => "Voir l'annonce",
                    'created_at' => $residence->updated_at,
                    'is_new' => $residence->updated_at >= now()->subDay(),
                ];
            });
        $notifications = $notifications->merge($recentlyApproved);

        // Résidences rejetées
        $rejected = $user->residences()
            ->where('status', 'rejected')
            ->where('updated_at', '>=', now()->subDays(7))
            ->get()
            ->map(function ($residence) {
                return [
                    'type' => 'rejection',
                    'title' => 'Annonce rejetée',
                    'message' => "Votre annonce \"{$residence->name}\" n'a pas été approuvée. Modifiez-la pour la resoumettre.",
                    'action_url' => route('owner.residences.edit', $residence),
                    'action_text' => "Modifier l'annonce",
                    'created_at' => $residence->updated_at,
                    'is_new' => true,
                ];
            });
        $notifications = $notifications->merge($rejected);

        // Trier par date
        $notifications = $notifications->sortByDesc('created_at')->values();

        // Compteurs par type
        $counts = [
            'all' => $notifications->count(),
            'contact' => $notifications->where('type', 'contact')->count(),
            'booking' => $notifications->where('type', 'booking')->count(),
            'approval' => $notifications->where('type', 'approval')->count(),
            'rejection' => $notifications->where('type', 'rejection')->count(),
        ];

        // Filtrer par type
        if ($filter !== 'all') {
            $notifications = $notifications->where('type', $filter)->values();
        }

        $newCount = $notifications->where('is_new', true)->count();

        return view('owner.notifications', compact('notifications', 'filter', 'counts', 'newCount'));
    }
}
