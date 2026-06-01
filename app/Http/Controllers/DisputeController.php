<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Cancellation;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisputeController extends Controller
{
    protected DisputeService $disputeService;

    public function __construct(DisputeService $disputeService)
    {
        $this->disputeService = $disputeService;
    }

    /**
     * Show user's disputes
     */
    public function index()
    {
        $user = Auth::user();
        $disputes = $this->disputeService->getUserDisputes($user->id);

        return view('disputes.index', compact('disputes'));
    }

    /**
     * Show create dispute form
     */
    public function create(Request $request)
    {
        $booking = null;
        $cancellation = null;

        if ($request->filled('booking_id')) {
            $booking = Booking::with('residence')->findOrFail($request->booking_id);

            // Check authorization
            $user = Auth::user();
            if ($booking->user_id !== $user->id && $booking->residence->owner_id !== $user->id) {
                abort(403);
            }
        }

        if ($request->filled('cancellation_id')) {
            $cancellation = Cancellation::with('booking.residence')->findOrFail($request->cancellation_id);

            // Check authorization
            $user = Auth::user();
            $cancelBooking = $cancellation->booking;
            if ($cancelBooking && $cancelBooking->user_id !== $user->id && $cancelBooking->residence->owner_id !== $user->id) {
                abort(403);
            }
        }

        $types = Dispute::getTypes();

        return view('disputes.create', compact('booking', 'cancellation', 'types'));
    }

    /**
     * Store new dispute
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'type' => 'required|string|in:'.implode(',', array_keys(Dispute::getTypes())),
            'reason' => 'required|string|max:255',
            'detailed_description' => 'required|string|max:5000',
            'evidence' => 'nullable|array',
            'evidence.*' => 'file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        try {
            $user = Auth::user();

            // Determine who is initiating
            $initiatedBy = 'guest';
            if ($validated['booking_id']) {
                $booking = Booking::with('residence')->findOrFail($validated['booking_id']);

                if (!$this->canAccessBooking($booking, $user)) {
                    abort(403);
                }

                if ($booking->residence && $booking->residence->owner_id === $user->id) {
                    $initiatedBy = 'host';
                }
            }

            // Process evidence files
            $evidence = [];
            if ($request->hasFile('evidence')) {
                foreach ($request->file('evidence') as $file) {
                    $path = $file->store('disputes/evidence', 'private');
                    $evidence[] = [
                        'path' => $path,
                        'disk' => 'private',
                        'name' => $file->getClientOriginalName(),
                        'type' => $file->getMimeType(),
                    ];
                }
            }

            $dispute = $this->disputeService->createDispute(
                $user->id,
                $initiatedBy,
                $validated['type'],
                $validated['reason'],
                $validated['detailed_description'],
                $validated['booking_id'] ?? null,
                $evidence,
            );

            return redirect()
                ->route('disputes.show', $dispute)
                ->with('success', 'Votre litige a été soumis. Notre équipe vous contactera dans les 48h.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show dispute details
     */
    public function show(Dispute $dispute)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$this->canAccessDispute($dispute, $user)) {
            abort(403);
        }

        $dispute->load(['booking.residence', 'opener', 'assignedAdmin', 'supportTickets']);

        return view('disputes.show', compact('dispute'));
    }

    /**
     * Add evidence to dispute
     */
    public function addEvidence(Request $request, Dispute $dispute)
    {
        $user = Auth::user();

        if (!$this->canAccessDispute($dispute, $user)) {
            abort(403);
        }

        if (!$dispute->isOpen()) {
            return back()->with('error', 'Ce litige est fermé.');
        }

        $validated = $request->validate([
            'description' => 'required|string|max:1000',
            'file' => 'required|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $path = $request->file('file')->store('disputes/evidence', 'private');

        $this->disputeService->addEvidence($dispute, [
            'path' => $path,
            'disk' => 'private',
            'name' => $request->file('file')->getClientOriginalName(),
            'type' => $request->file('file')->getMimeType(),
            'description' => $validated['description'],
        ], $user->id);

        return back()->with('success', 'Preuve ajoutée.');
    }

    public function downloadEvidence(Dispute $dispute, int $index): StreamedResponse
    {
        $user = Auth::user();

        if (!$this->canAccessDispute($dispute, $user)) {
            abort(403);
        }

        $evidence = $dispute->evidence_files ?? [];
        if (!isset($evidence[$index])) {
            abort(404);
        }

        $file = $evidence[$index];
        $diskName = $file['disk'] ?? 'public';
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);
        $path = $file['path'] ?? '';

        if (!$path || !$disk->exists($path)) {
            abort(404);
        }

        return $disk->download($path, $file['name'] ?? 'preuve');
    }

    // ===== OWNER SECTION =====

    /**
     * Owner: Show disputes for their properties
     */
    public function ownerIndex()
    {
        $user = Auth::user();
        $disputes = $this->disputeService->getOwnerDisputes($user->id);

        return view('owner.disputes.index', compact('disputes'));
    }

    // ===== API ENDPOINTS =====

    /**
     * API: Get dispute types
     */
    public function apiTypes()
    {
        return response()->json([
            'success' => true,
            'data' => Dispute::getTypes(),
        ]);
    }

    /**
     * API: Get dispute status
     */
    public function apiStatus(Dispute $dispute)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$this->canAccessDispute($dispute, $user)) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $dispute->id,
                'status' => $dispute->status,
                'status_label' => $dispute->status_label,
                'priority' => $dispute->priority,
                'priority_label' => $dispute->priority_label,
                'resolution' => $dispute->resolution,
                'resolution_label' => $dispute->resolution_label,
                'is_open' => $dispute->isOpen(),
                'response_deadline' => $dispute->response_deadline?->toISOString(),
                'resolved_at' => $dispute->resolved_at?->toISOString(),
            ],
        ]);
    }

    private function canAccessBooking(Booking $booking, \App\Models\User $user): bool
    {
        return (int) $booking->user_id === (int) $user->id
            || (int) ($booking->residence?->owner_id ?? 0) === (int) $user->id
            || $user->isAdmin();
    }

    private function canAccessDispute(Dispute $dispute, \App\Models\User $user): bool
    {
        if ((int) $dispute->opened_by === (int) $user->id || $user->isAdmin()) {
            return true;
        }

        $booking = $dispute->booking;

        return $booking && $this->canAccessBooking($booking, $user);
    }
}
