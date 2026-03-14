<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreMessageSequenceRequest;
use App\Models\MessageSequence;
use App\Models\MessageSequenceStep;
use App\Services\MessageSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageSequenceController extends Controller
{
    public function __construct(
        private MessageSequenceService $sequenceService,
    ) {}

    public function index(Request $request): View
    {
        $user      = $request->user();
        $sequences = MessageSequence::forOwner($user->id)
            ->withCount('steps')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('owner.sequences.index', compact('sequences'));
    }

    public function create(): View
    {
        $user       = request()->user();
        $residences = $user->residences()->orderBy('name')->get();
        $triggers   = MessageSequence::TRIGGERS;
        $channels   = MessageSequenceStep::CHANNELS;

        return view('owner.sequences.create', compact('residences', 'triggers', 'channels'));
    }

    public function store(StoreMessageSequenceRequest $request): RedirectResponse
    {
        $data            = $request->validated();
        $data['user_id'] = $request->user()->id;

        $sequence = MessageSequence::create($data);

        return redirect()->route('owner.sequences.show', $sequence)
            ->with('success', 'Séquence créée avec succès.');
    }

    public function show(MessageSequence $sequence): View
    {
        $this->authorize('view', $sequence);
        $sequence->load('steps', 'logs.booking');

        return view('owner.sequences.show', compact('sequence'));
    }

    public function edit(MessageSequence $sequence): View
    {
        $this->authorize('update', $sequence);
        $sequence->load('steps');
        $residences = request()->user()->residences()->orderBy('name')->get();
        $triggers   = MessageSequence::TRIGGERS;
        $channels   = MessageSequenceStep::CHANNELS;

        return view('owner.sequences.edit', compact('sequence', 'residences', 'triggers', 'channels'));
    }

    public function update(StoreMessageSequenceRequest $request, MessageSequence $sequence): RedirectResponse
    {
        $this->authorize('update', $sequence);
        $sequence->update($request->validated());

        return redirect()->route('owner.sequences.show', $sequence)
            ->with('success', 'Séquence mise à jour.');
    }

    public function destroy(MessageSequence $sequence): RedirectResponse
    {
        $this->authorize('delete', $sequence);
        $sequence->delete();

        return redirect()->route('owner.sequences.index')
            ->with('success', 'Séquence supprimée.');
    }

    public function addStep(Request $request, MessageSequence $sequence): RedirectResponse
    {
        $this->authorize('update', $sequence);

        $validated = $request->validate([
            'delay_hours'     => 'required|integer|min:0',
            'delay_reference' => 'required|in:after_trigger,before_checkin,after_checkout,before_checkout',
            'channel'         => 'required|in:email,sms,whatsapp,in_app',
            'subject'         => 'nullable|string|max:255',
            'message'         => 'required|string|max:5000',
        ]);

        $validated['step_order'] = $sequence->steps()->count() + 1;

        $sequence->steps()->create($validated);

        return redirect()->route('owner.sequences.show', $sequence)
            ->with('success', 'Étape ajoutée.');
    }

    public function removeStep(MessageSequence $sequence, MessageSequenceStep $step): RedirectResponse
    {
        $this->authorize('update', $sequence);
        $step->delete();

        return redirect()->route('owner.sequences.show', $sequence)
            ->with('success', 'Étape supprimée.');
    }

    public function createDefaults(Request $request): RedirectResponse
    {
        $this->sequenceService->createDefaultSequences($request->user());

        return redirect()->route('owner.sequences.index')
            ->with('success', 'Séquences par défaut créées avec succès.');
    }

    public function toggle(MessageSequence $sequence): RedirectResponse
    {
        $this->authorize('update', $sequence);
        $sequence->update(['is_active' => !$sequence->is_active]);

        $status = $sequence->is_active ? 'activée' : 'désactivée';
        return back()->with('success', "Séquence $status.");
    }
}
