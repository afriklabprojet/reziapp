<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\SupportService;
use Illuminate\Http\Request;

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
        $user = auth()->user();
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
                ->where('user_id', auth()->id())
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
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        try {
            $user = auth()->user();

            // Process attachments
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = $this->supportService->uploadAttachment($file, 0);
                }
            }

            $ticket = $this->supportService->createTicket(
                $user->id,
                $validated['category'],
                $validated['subject'],
                $validated['message'],
                $validated['booking_id'] ?? null,
                null,
                $validated['priority'] ?? 'medium',
                !empty($attachments) ? $attachments : null,
            );

            return redirect()
                ->route('support.show', $ticket)
                ->with('success', 'Votre demande a été envoyée. Numéro de ticket: '.$ticket->ticket_number);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show ticket details
     */
    public function show(SupportTicket $ticket)
    {
        $user = auth()->user();

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
        $user = auth()->user();

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
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
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

    /**
     * Close ticket (by user)
     */
    public function close(SupportTicket $ticket)
    {
        $user = auth()->user();

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
        $user = auth()->user();

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
        $user = auth()->user();

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
        $user = auth()->user();
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
            $ticket = $this->supportService->createTicket(
                auth()->id(),
                $validated['category'],
                $validated['subject'],
                $validated['message'],
                $validated['booking_id'] ?? null,
            );

            return response()->json([
                'success' => true,
                'data' => $ticket,
                'message' => 'Ticket créé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * API: Get ticket details
     */
    public function apiShow(SupportTicket $ticket)
    {
        $user = auth()->user();

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
        $user = auth()->user();

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
        $count = $this->supportService->getUnreadCount(auth()->id());

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }
}
