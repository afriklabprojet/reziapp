<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerAlert;
use App\Services\OwnerAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerAlertController extends Controller
{
    public function __construct(
        private OwnerAlertService $alertService,
    ) {
    }

    public function index(Request $request): View
    {
        $user  = $request->user();
        $unread = OwnerAlert::where('owner_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->get();
        $read = OwnerAlert::where('owner_id', $user->id)
            ->whereNotNull('read_at')
            ->latest()
            ->paginate(20);
        $settings = $user->alert_settings ?? [];

        return view('owner.alerts.index', compact('unread', 'read', 'settings'));
    }

    public function acknowledge(OwnerAlert $alert): RedirectResponse
    {
        $alert->acknowledge();

        return back()->with('success', 'Alerte acquittée.');
    }

    public function resolve(OwnerAlert $alert): RedirectResponse
    {
        $alert->resolve();

        return back()->with('success', 'Alerte résolue.');
    }

    public function dismiss(OwnerAlert $alert): RedirectResponse
    {
        $alert->dismiss();

        return back()->with('success', 'Alerte masquée.');
    }

    public function markRead(OwnerAlert $alert): RedirectResponse
    {
        $alert->update(['read_at' => now()]);

        return back()->with('success', 'Alerte marquée comme lue.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled_types'   => 'nullable|array',
            'enabled_types.*' => 'string|in:' . implode(',', array_keys(OwnerAlert::TYPES)),
        ]);

        $request->user()->update([
            'alert_settings' => ['enabled_types' => $validated['enabled_types'] ?? []],
        ]);

        return back()->with('success', 'Paramètres sauvegardés.');
    }
}
