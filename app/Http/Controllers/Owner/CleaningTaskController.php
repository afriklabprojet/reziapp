<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreCleaningTaskRequest;
use App\Models\CleaningTask;
use App\Services\CleaningTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CleaningTaskController extends Controller
{
    public function __construct(
        private CleaningTaskService $cleaningService,
    ) {
    }

    public function index(Request $request): View
    {
        $user     = $request->user();
        $tasks    = $this->cleaningService->getTasks($user, $request->only(['status', 'residence_id']));
        $upcoming = $this->cleaningService->getUpcomingTasks($user);
        $residences = $user->residences()->orderBy('name')->get();

        return view('owner.cleaning.index', compact('tasks', 'upcoming', 'residences'));
    }

    public function create(Request $request): View
    {
        $residences = $request->user()->residences()->orderBy('name')->get();

        return view('owner.cleaning.create', compact('residences'));
    }

    public function store(StoreCleaningTaskRequest $request): RedirectResponse
    {
        $this->cleaningService->create($request->user(), $request->validated());

        return redirect()->route('owner.cleaning.index')
            ->with('success', 'Tâche de ménage planifiée.');
    }

    public function show(CleaningTask $cleaning): View
    {
        $cleaning->load(['residence', 'assignee', 'booking']);

        return view('owner.cleaning.show', compact('cleaning'));
    }

    public function complete(CleaningTask $cleaning): RedirectResponse
    {
        $this->cleaningService->markCompleted($cleaning);

        return back()->with('success', 'Tâche marquée comme terminée.');
    }

    public function verify(CleaningTask $cleaning): RedirectResponse
    {
        $this->cleaningService->verify($cleaning);

        return back()->with('success', 'Ménage vérifié et validé.');
    }

    public function destroy(CleaningTask $cleaning): RedirectResponse
    {
        $this->cleaningService->delete($cleaning);

        return redirect()->route('owner.cleaning.index')
            ->with('success', 'Tâche supprimée.');
    }
}
