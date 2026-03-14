<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\RentReminder;
use App\Services\RentReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RentReminderController extends Controller
{
    public function __construct(
        private RentReminderService $reminderService,
    ) {}

    public function index(Request $request): View
    {
        $user      = $request->user();
        $reminders = $this->reminderService->getReminders($user, $request->only(['status', 'residence_id']));
        $overdue   = $this->reminderService->getOverdueSummary($user);
        $residences = $user->residences()->orderBy('name')->get();

        return view('owner.rent-reminders.index', compact('reminders', 'overdue', 'residences'));
    }

    public function create(Request $request): View
    {
        $residences = $request->user()->residences()->orderBy('name')->get();

        return view('owner.rent-reminders.create', compact('residences'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id'        => ['required', 'integer', 'exists:users,id'],
            'residence_id'     => ['required', 'integer', 'exists:residences,id'],
            'lease_contract_id' => ['nullable', 'integer', 'exists:lease_contracts,id'],
            'amount'           => ['required', 'numeric', 'min:0'],
            'due_date'         => ['required', 'date'],
            'channel'          => ['required', 'in:email,sms,whatsapp'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $this->reminderService->create($request->user(), $data);

        return redirect()->route('owner.rent-reminders.index')
            ->with('success', 'Relance créée avec succès.');
    }

    public function markPaid(RentReminder $rentReminder): RedirectResponse
    {
        $this->reminderService->markPaid($rentReminder);

        return back()->with('success', 'Paiement enregistré.');
    }

    public function send(RentReminder $rentReminder): RedirectResponse
    {
        $this->reminderService->sendReminder($rentReminder);

        return back()->with('success', 'Relance envoyée.');
    }
}
