<?php

namespace App\Http\Controllers;

use App\Models\PropertyShare;
use App\Models\Residence;
use App\Services\FavoriteService;
use App\Services\ShareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    public function __construct(
        protected ShareService $shareService,
        protected FavoriteService $favoriteService,
    ) {
    }

    /**
     * Créer un lien de partage
     */
    public function create(Request $request, int $residenceId)
    {
        $validated = $request->validate([
            'platform' => 'required|in:whatsapp,facebook,twitter,email,link,sms',
        ]);

        $share = $this->shareService->createShare(
            $residenceId,
            $validated['platform'],
            Auth::id(),
            $request->ip(),
            $request->userAgent(),
        );

        $residence = Residence::with('quartier')->findOrFail($residenceId);

        return response()->json([
            'success' => true,
            'share_url' => $share->getShareUrl(),
            'platform_url' => $this->getPlatformUrl($share, $residence),
        ]);
    }

    /**
     * Obtenir tous les liens de partage
     */
    public function getShareLinks(int $residenceId)
    {
        $links = $this->shareService->generateShareUrls($residenceId, Auth::id());

        return response()->json([
            'success' => true,
            'links' => $links,
            'copy_text' => $this->shareService->getCopyText($residenceId),
        ]);
    }

    /**
     * Gérer le clic sur un lien partagé
     */
    public function handleSharedLink(string $token)
    {
        $share = $this->shareService->getShareByToken($token);

        if (!$share) {
            return redirect()->route('home')->with('error', 'Lien invalide.');
        }

        $this->shareService->recordClick($token);

        return redirect()->route('residences.show', $share->residence_id);
    }

    /**
     * Statistiques de partage pour le propriétaire
     */
    public function ownerStats(int $residenceId)
    {
        $residence = Residence::findOrFail($residenceId);

        if ($residence->owner_id !== Auth::id()) {
            abort(403);
        }

        $stats = $this->shareService->getResidenceStats($residenceId);

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Comparaison de résidences
     */
    public function compareIndex()
    {
        $comparison = $this->favoriteService->getComparisonList(Auth::id());
        $residences = $comparison->getResidences()
            ->load(['photos', 'quartier', 'amenities']);

        return view('compare.index', compact('comparison', 'residences'));
    }

    /**
     * Ajouter à la comparaison
     */
    public function addToCompare(Request $request, int $residenceId)
    {
        $comparison = $this->favoriteService->addToComparison(Auth::id(), $residenceId);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $comparison->getCount(),
                'message' => 'Ajouté à la comparaison',
            ]);
        }

        return back()->with('success', 'Ajouté à la comparaison.');
    }

    /**
     * Retirer de la comparaison
     */
    public function removeFromCompare(Request $request, int $residenceId)
    {
        $comparison = $this->favoriteService->removeFromComparison(Auth::id(), $residenceId);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $comparison->getCount(),
                'message' => 'Retiré de la comparaison',
            ]);
        }

        return back()->with('success', 'Retiré de la comparaison.');
    }

    /**
     * Vider la comparaison
     */
    public function clearCompare()
    {
        $this->favoriteService->clearComparison(Auth::id());

        return back()->with('success', 'Comparaison vidée.');
    }

    /**
     * Comparaison partagée
     */
    public function sharedCompare(string $token)
    {
        $comparison = \App\Models\ComparisonList::where('share_token', $token)->firstOrFail();
        $residences = $comparison->getResidences()->load(['photos', 'quartier', 'amenities']);

        return view('compare.shared', compact('comparison', 'residences'));
    }

    /**
     * URL de partage spécifique à la plateforme
     */
    protected function getPlatformUrl(PropertyShare $share, Residence $residence): string
    {
        $shareUrl = $share->getShareUrl();
        $quartierName = $residence->quartier?->name ?? $residence->commune ?? $residence->city ?? 'REZI';
        $description = "Découvrez ce logement à {$quartierName} sur REZI";

        return match($share->platform) {
            'whatsapp' => 'https://wa.me/?text='.urlencode("{$description}\n\n{$shareUrl}"),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($shareUrl),
            'twitter' => 'https://twitter.com/intent/tweet?text='.urlencode($description).'&url='.urlencode($shareUrl),
            'email' => 'mailto:?subject='.urlencode('Logement à découvrir').'&body='.urlencode("Bonjour,\n\n{$description}\n\n{$shareUrl}"),
            'sms' => 'sms:?body='.urlencode("{$description} {$shareUrl}"),
            default => $shareUrl,
        };
    }
}
