<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StorePropertyInspectionRequest;
use App\Models\InspectionItem;
use App\Models\PropertyInspection;
use App\Models\Residence;
use App\Services\PropertyInspectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Gestion des états des lieux numériques pour les propriétaires.
 */
class PropertyInspectionController extends Controller
{
    public function __construct(
        private readonly PropertyInspectionService $inspectionService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        $owner = $request->user();

        $inspections = PropertyInspection::forOwner($owner->id)
            ->with(['tenant:id,name,email', 'residence:id,title,commune'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('inspection_date')
            ->paginate(15)
            ->withQueryString();

        return view('owner.property-inspections.index', compact('inspections'));
    }

    // ===== CRÉATION =====

    public function create(Request $request): View
    {
        $owner      = $request->user();
        $residences = Residence::where('user_id', $owner->id)
            ->where('status', 'active')
            ->select('id', 'title', 'commune', 'bedrooms')
            ->get();

        $defaultRooms    = InspectionItem::defaultRooms();
        $defaultElements = InspectionItem::defaultElements();

        return view('owner.property-inspections.create', compact('residences', 'defaultRooms', 'defaultElements'));
    }

    public function store(StorePropertyInspectionRequest $request): RedirectResponse
    {
        $data             = $request->validated();
        $data['owner_id'] = $request->user()->id;

        $inspection = $this->inspectionService->create($data);

        return redirect()
            ->route('owner.property-inspections.show', $inspection)
            ->with('success', "État des lieux {$inspection->reference} créé.");
    }

    // ===== SHOW / EDIT =====

    public function show(PropertyInspection $propertyInspection): View
    {
        $this->authorize('view', $propertyInspection);

        $propertyInspection->load([
            'tenant:id,name,email,phone',
            'owner:id,name,email',
            'residence:id,title,commune,address',
            'items',
        ]);

        $itemsByRoom     = $propertyInspection->itemsByRoom();
        $defaultRooms    = InspectionItem::defaultRooms();
        $defaultElements = InspectionItem::defaultElements();

        return view('owner.property-inspections.show', compact(
            'propertyInspection',
            'itemsByRoom',
            'defaultRooms',
            'defaultElements',
        ));
    }

    // ===== MISE À JOUR D'UN ÉLÉMENT (AJAX) =====

    public function updateItem(Request $request, PropertyInspection $propertyInspection, InspectionItem $item): JsonResponse
    {
        $this->authorize('update', $propertyInspection);

        abort_unless($item->property_inspection_id === $propertyInspection->id, 404);

        $validated = $request->validate([
            'condition'       => ['required', 'in:good,fair,damaged,missing'],
            'observations'    => ['nullable', 'string', 'max:500'],
            'repair_estimate' => ['nullable', 'numeric', 'min:0'],
            'requires_action' => ['boolean'],
        ]);

        $item = $this->inspectionService->updateItem($item, $validated);

        return response()->json([
            'success'          => true,
            'item'             => $item,
            'condition_label'  => $item->condition_label,
            'condition_color'  => $item->condition_color,
        ]);
    }

    // ===== AJOUT D'UN ÉLÉMENT =====

    public function addItem(Request $request, PropertyInspection $propertyInspection): JsonResponse
    {
        $this->authorize('update', $propertyInspection);

        $validated = $request->validate([
            'room'       => ['required', 'string', 'max:100'],
            'element'    => ['required', 'string', 'max:100'],
            'condition'  => ['required', 'in:good,fair,damaged,missing'],
        ]);

        $item = $propertyInspection->items()->create(array_merge($validated, [
            'sort_order' => $propertyInspection->items()->max('sort_order') + 1,
        ]));

        return response()->json(['success' => true, 'item' => $item]);
    }

    // ===== MARQUER COMME COMPLÉTÉ =====

    public function complete(PropertyInspection $propertyInspection): RedirectResponse
    {
        $this->authorize('update', $propertyInspection);

        $propertyInspection->complete();

        return back()->with('success', 'État des lieux marqué comme complété.');
    }

    // ===== SIGNATURE =====

    public function sign(Request $request, PropertyInspection $propertyInspection): RedirectResponse
    {
        $this->authorize('sign', $propertyInspection);

        $user = $request->user();
        $role = $user->id === $propertyInspection->owner_id ? 'owner' : 'tenant';

        $this->inspectionService->sign($propertyInspection, $role, $request->ip());

        return back()->with('success', 'Signature enregistrée avec succès.');
    }

    // ===== TÉLÉCHARGEMENT PDF =====

    public function download(PropertyInspection $propertyInspection): Response
    {
        $this->authorize('download', $propertyInspection);

        $pdfContent = $this->inspectionService->downloadPdf($propertyInspection);
        $filename   = "etat-des-lieux-{$propertyInspection->reference}.pdf";

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ===== COMPARAISON ENTRÉE/SORTIE =====

    public function compare(Residence $residence): View
    {
        abort_unless($residence->user_id === auth()->id(), 403);

        $checkIn  = PropertyInspection::forOwner(auth()->id())
            ->where('residence_id', $residence->id)
            ->where('type', PropertyInspection::TYPE_CHECK_IN)
            ->where('status', PropertyInspection::STATUS_SIGNED)
            ->with('items')
            ->latest('inspection_date')
            ->first();

        $checkOut = PropertyInspection::forOwner(auth()->id())
            ->where('residence_id', $residence->id)
            ->where('type', PropertyInspection::TYPE_CHECK_OUT)
            ->where('status', PropertyInspection::STATUS_SIGNED)
            ->with('items')
            ->latest('inspection_date')
            ->first();

        $comparison = null;
        if ($checkIn && $checkOut) {
            $comparison = $this->inspectionService->compareCheckInOut($checkIn, $checkOut);
        }

        return view('owner.property-inspections.compare', compact(
            'residence',
            'checkIn',
            'checkOut',
            'comparison',
        ));
    }
}
