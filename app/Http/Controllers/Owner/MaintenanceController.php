<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreMaintenanceRequest as StoreRequest;
use App\Models\MaintenanceRequest;
use App\Services\MaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function __construct(
        private MaintenanceService $maintenanceService,
    ) {
    }

    public function index(Request $request): View
    {
        $user     = $request->user();
        $requests = $this->maintenanceService->getRequests($user, $request->only(['status', 'priority', 'residence_id']));
        $stats    = $this->maintenanceService->getDashboardStats($user);
        $residences = $user->residences()->orderBy('name')->get();

        return view('owner.maintenance.index', compact('requests', 'stats', 'residences'));
    }

    public function create(Request $request): View
    {
        $residences = $request->user()->residences()->orderBy('name')->get();
        $categories = MaintenanceRequest::CATEGORIES;
        $priorities = MaintenanceRequest::PRIORITIES;

        return view('owner.maintenance.create', compact('residences', 'categories', 'priorities'));
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photos')) {
            $photos = [];
            foreach ($request->file('photos') as $photo) {
                $photos[] = $photo->store('maintenance/'.$request->user()->id, 'public');
            }
            $data['photos'] = $photos;
        }

        $this->maintenanceService->create($request->user(), $data);

        return redirect()->route('owner.maintenance.index')
            ->with('success', 'Demande de maintenance créée.');
    }

    public function show(MaintenanceRequest $maintenance): View
    {
        $maintenance->load(['residence', 'reporter', 'assignee']);

        return view('owner.maintenance.show', compact('maintenance'));
    }

    public function updateStatus(Request $request, MaintenanceRequest $maintenance): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(MaintenanceRequest::STATUSES))],
        ]);

        $this->maintenanceService->updateStatus($maintenance, $data['status']);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function assign(Request $request, MaintenanceRequest $maintenance): RedirectResponse
    {
        $data = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $this->maintenanceService->assign($maintenance, $data['assigned_to']);

        return back()->with('success', 'Intervention assignée.');
    }
}
