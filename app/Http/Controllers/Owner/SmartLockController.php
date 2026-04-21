<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreSmartLockRequest;
use App\Models\SmartLock;
use App\Models\SmartLockCode;
use App\Services\SmartLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmartLockController extends Controller
{
    public function __construct(
        private SmartLockService $lockService,
    ) {
    }

    public function index(Request $request): View
    {
        $user       = $request->user();
        $residences = $user->residences()->orderBy('name')->get();

        $locks = SmartLock::whereIn('residence_id', $residences->pluck('id'))
            ->with(['residence', 'codes' => fn ($q) => $q->where('status', 'active')->latest()])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('owner.smart-locks.index', compact('locks', 'residences'));
    }

    public function store(StoreSmartLockRequest $request): RedirectResponse
    {
        SmartLock::create($request->validated());

        return redirect()->route('owner.smart-locks.index')
            ->with('success', 'Serrure connectée ajoutée.');
    }

    public function show(SmartLock $smartLock): View
    {
        $smartLock->load(['residence', 'codes' => fn ($q) => $q->orderByDesc('created_at')]);

        return view('owner.smart-locks.show', compact('smartLock'));
    }

    public function generateCode(Request $request, SmartLock $smartLock): RedirectResponse
    {
        $validated = $request->validate([
            'booking_id' => 'nullable|integer|exists:bookings,id',
            'guest_name' => 'required|string|max:255',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
        ]);

        SmartLockCode::create([
            'smart_lock_id' => $smartLock->id,
            'booking_id'    => $validated['booking_id'] ?? null,
            'code'          => str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT),
            'code_type'     => SmartLockCode::TYPE_TEMPORARY,
            'status'        => SmartLockCode::STATUS_ACTIVE,
            'valid_from'    => $validated['valid_from'],
            'valid_until'   => $validated['valid_until'],
            'guest_name'    => $validated['guest_name'],
        ]);

        return back()->with('success', 'Code d\'accès généré.');
    }

    public function revokeCode(SmartLockCode $code): RedirectResponse
    {
        $code->revoke();

        return back()->with('success', 'Code révoqué.');
    }

    public function destroy(SmartLock $smartLock): RedirectResponse
    {
        $smartLock->codes()->delete();
        $smartLock->delete();

        return redirect()->route('owner.smart-locks.index')
            ->with('success', 'Serrure supprimée.');
    }
}
