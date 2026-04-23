<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\VacationMode;
use App\Services\VacationModeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VacationModeController extends Controller
{
    public function __construct(
        private VacationModeService $vacationService,
    ) {
    }

    public function index(Request $request): View
    {
        $user       = $request->user();
        $activeMode = $this->vacationService->getActiveMode($user);
        $history    = VacationMode::forOwner($user->id)->orderByDesc('created_at')->paginate(10);
        $residences = $user->residences()->orderBy('name')->get();

        return view('owner.vacation-mode.index', compact('activeMode', 'history', 'residences'));
    }

    public function activate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'start_date'          => ['required', 'date', 'after_or_equal:today'],
            'end_date'            => ['required', 'date', 'after:start_date'],
            'auto_message'        => ['nullable', 'string', 'max:500'],
            'affected_residences' => ['nullable', 'array'],
            'affected_residences.*' => ['integer', 'exists:residences,id'],
        ]);

        $this->vacationService->activate($request->user(), $data);

        return redirect()->route('owner.vacation-mode.index')
            ->with('success', 'Mode vacances activé. Vos résidences sont marquées indisponibles.');
    }

    public function deactivate(VacationMode $vacationMode): RedirectResponse
    {
        abort_unless($vacationMode->owner_id === Auth::id(), 403);
        $this->vacationService->deactivate($vacationMode);

        return redirect()->route('owner.vacation-mode.index')
            ->with('success', 'Mode vacances désactivé. Vos résidences sont de nouveau disponibles.');
    }
}
