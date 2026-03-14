<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Services\RefundService;

class RefundController extends Controller
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Show user's refunds
     */
    public function index()
    {
        $user = auth()->user();
        $refunds = $this->refundService->getUserRefunds($user->id);

        return view('refunds.index', compact('refunds'));
    }

    /**
     * Show refund details
     */
    public function show(Refund $refund)
    {
        $user = auth()->user();

        // Check authorization
        if ($refund->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Non autorisé');
        }

        $refund->load(['booking.residence', 'cancellation']);

        return view('refunds.show', compact('refund'));
    }

    // ===== API ENDPOINTS =====

    /**
     * API: Get user refunds
     */
    public function apiIndex()
    {
        $user = auth()->user();
        $refunds = $this->refundService->getUserRefunds($user->id);

        return response()->json([
            'success' => true,
            'data' => $refunds,
        ]);
    }

    /**
     * API: Get refund status
     */
    public function apiStatus(Refund $refund)
    {
        $user = auth()->user();

        if ($refund->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $refund->id,
                'status' => $refund->status,
                'status_label' => $refund->status_label,
                'amount' => $refund->amount,
                'formatted_amount' => $refund->formatted_amount,
                'method' => $refund->method,
                'method_label' => $refund->method_label,
                'processed_at' => $refund->processed_at?->toISOString(),
                'transaction_id' => $refund->transaction_id,
            ],
        ]);
    }

    /**
     * API: Get available refund methods
     */
    public function apiMethods()
    {
        return response()->json([
            'success' => true,
            'data' => Refund::getAvailableMethods(),
        ]);
    }
}
