<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\SupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportController extends Controller
{
    protected SupportService $supportService;

    public function __construct(SupportService $supportService)
    {
        $this->supportService = $supportService;
    }

    /**
     * Show user's support tickets
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tickets = $this->supportService->getUserTickets($user->id);
        $unreadCount = $this->supportService->getUnreadCount($user->id);

        return view('support.index', compact('tickets', 'unreadCount'));
    }

    /**
     * Show create ticket form
     */
    public function create(Request $request)
    {
        $booking = null;
        if ($request->filled('booking_id')) {
            $booking = \App\Models\Booking::with('residence')
                ->where('user_id', Auth::id())
                ->findOrFail($request->booking_id);
        }

        $categories = SupportTicket::getCategories();
        $priorities = SupportTicket::getPriorities();

        return view('support.create', compact('booking', 'categories', 'priorities'));
    }

    /**
     * Store new ticket
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:'.implode(',', array_keys(SupportTicket::getCategories())),
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'booking_id' => 'nullable|exists:bookings,id',
            'priority' => 'nullable|string|in:'.implode(',', array_keys(SupportTicket::getPriorities())),
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $bookingId = $this->authorizedBookingId($validated['booking_id'] ?? null, $user);

            // Process attachments
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = $this->supportService->uploadAttachment($file, 0);
                }
            }

            $ticket = $this->supportService->createTicket(
                $user->id,
                [
                    'category' => $validated['category'],
                    'subject' => $validated['subject'],
                    'message' => $validated['message'],
                    'booking_id' => $bookingId,
                    'priority' => $validated['priority'] ?? 'medium',
                ],
                !empty($attachments) ? $attachments : null,
            );

            return redirect()
                ->route('support.show', $ticket)
                ->with('success', 'Votre demande a été envoyée. Numéro de ticket: '.$ticket->ticket_number);
        } catch (\Exception $e) {
            Log::error('Support ticket creation failed', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', config('app.debug') ? $e->getMessage() : 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer.');
        }
    }

    /**
     * Show ticket details
     */
    public function show(SupportTicket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check authorization
        if ($ticket->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        $ticket = $this->supportService->getTicketWithMessages($ticket);

        // Mark messages as read
        $this->supportService->markMessagesAsRead($ticket, $user->id);

        return view('support.show', compact('ticket'));
    }

    /**
     * Add reply to ticket
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check authorization
        if ($ticket->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        if (!$ticket->isActive()) {
            return back()->with('error', 'Ce ticket est fermé.');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        // Process attachments
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachments[] = $this->supportService->uploadAttachment($file, $ticket->id);
            }
        }

        $this->supportService->addMessage(
            $ticket,
            $user->id,
            $validated['message'],
            !empty($attachments) ? $attachments : null,
        );

        return back()->with('success', 'Réponse envoyée.');
    }

    public function downloadAttachment(SupportTicket $ticket, SupportMessage $message, int $index): StreamedResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($ticket->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        if ((int) $message->support_ticket_id !== (int) $ticket->id) {
            abort(404);
        }

        $attachments = $message->attachments ?? [];
        if (!isset($attachments[$index])) {
            abort(404);
        }

        $attachment = $attachments[$index];
        $diskName = $attachment['disk'] ?? 'public';
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);
        $path = $attachment['path'] ?? '';

        if (!$path || !$disk->exists($path)) {
            abort(404);
        }

        return $disk->download($path, $attachment['name'] ?? 'piece-jointe');
    }

    /**
     * Close ticket (by user)
     */
    public function close(SupportTicket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($ticket->user_id !== $user->id) {
            abort(403);
        }

        $this->supportService->closeTicket($ticket);

        return back()->with('success', 'Ticket fermé.');
    }

    /**
     * Reopen ticket (by user)
     */
    public function reopen(SupportTicket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($ticket->user_id !== $user->id) {
            abort(403);
        }

        $this->supportService->reopenTicket($ticket);

        return back()->with('success', 'Ticket réouvert.');
    }

    /**
     * Rate ticket satisfaction
     */
    public function rate(Request $request, SupportTicket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($ticket->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $this->supportService->rateTicket($ticket, $validated['rating'], $validated['comment'] ?? null);

        return back()->with('success', 'Merci pour votre évaluation!');
    }

    // ===== API ENDPOINTS =====

    /**
     * API: Get user tickets
     */
    public function apiIndex()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tickets = $this->supportService->getUserTickets($user->id);

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    /**
     * API: Create ticket
     */
    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:'.implode(',', array_keys(SupportTicket::getCategories())),
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'booking_id' => 'nullable|exists:bookings,id',
        ]);

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $bookingId = $this->authorizedBookingId($validated['booking_id'] ?? null, $user);

            $ticket = $this->supportService->createTicket(
                $user->id,
                [
                    'category' => $validated['category'],
                    'subject' => $validated['subject'],
                    'message' => $validated['message'],
                    'booking_id' => $bookingId,
                ],
            );

            return response()->json([
                'success' => true,
                'data' => $ticket,
                'message' => 'Ticket créé avec succès',
            ]);
        } catch (\Exception $e) {
            Log::error('API support ticket creation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Impossible de créer le ticket. Veuillez réessayer.',
            ], 400);
        }
    }

    /**
     * API: Get ticket details
     */
    public function apiShow(SupportTicket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($ticket->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $ticket = $this->supportService->getTicketWithMessages($ticket);

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    /**
     * API: Reply to ticket
     */
    public function apiReply(Request $request, SupportTicket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($ticket->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message = $this->supportService->addMessage($ticket, $user->id, $validated['message']);

        return response()->json([
            'success' => true,
            'data' => $message,
        ]);
    }

    /**
     * API: Get categories
     */
    public function apiCategories()
    {
        return response()->json([
            'success' => true,
            'data' => SupportTicket::getCategories(),
        ]);
    }

    /**
     * API: Get unread count
     */
    public function apiUnreadCount()
    {
        $count = $this->supportService->getUnreadCount(Auth::id());

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }

    private function authorizedBookingId(?int $bookingId, \App\Models\User $user): ?int
    {
        if (!$bookingId) {
            return null;
        }

        $booking = Booking::with('residence')->findOrFail($bookingId);
        $isGuest = (int) $booking->user_id === (int) $user->id;
        $isOwner = (int) ($booking->residence?->owner_id ?? 0) === (int) $user->id;

        if (!$isGuest && !$isOwner && !$user->isAdmin()) {
            abort(403);
        }

        return $booking->id;
    }
}
