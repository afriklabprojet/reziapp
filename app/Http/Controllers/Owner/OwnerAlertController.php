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
    ) {}

    public function index(Request $request): View
    {
        $alerts = $this->alertService->getActiveAlerts($request->user());

        return view('owner.alerts.index', compact('alerts'));
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
}
