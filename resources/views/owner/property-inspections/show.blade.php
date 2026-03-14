@extends('layouts.owner')

@section('title', 'État des lieux ' . $inspection->reference)

@section('owner-content')
<div class="space-y-6" x-data="inspectionApp()">

    <nav class="text-sm text-gray-400 flex items-center gap-2">
        <a href="{{ route('owner.property-inspections.index') }}" class="hover:text-indigo-600">États des lieux</a>
        <span>›</span>
        <span class="text-gray-700 font-mono">{{ $inspection->reference }}</span>
    </nav>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">🏠 {{ $inspection->reference }}</h1>
            @php
                $statusColors = ['draft' => 'gray', 'in_progress' => 'blue', 'completed' => 'amber', 'signed' => 'emerald'];
                $typeLabels = ['check_in' => 'Entrée ➜', 'check_out' => '← Sortie', 'periodic' => '○ Périodique'];
            @endphp
            <div class="flex gap-2 mt-2">
                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-700">
                    {{ $typeLabels[$inspection->type] ?? $inspection->type }}
                </span>
                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-{{ $statusColors[$inspection->status] ?? 'gray' }}-100 text-{{ $statusColors[$inspection->status] ?? 'gray' }}-700">
                    {{ ucfirst($inspection->status) }}
                </span>
            </div>
        </div>
        <div class="flex gap-2 flex-wrap">
            @if(in_array($inspection->status, ['completed', 'signed']))
            <a href="{{ route('owner.property-inspections.download', $inspection) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition">
                PDF
            </a>
            @endif
            @if($inspection->status === 'draft' || $inspection->status === 'in_progress')
            <form method="POST" action="{{ route('owner.property-inspections.complete', $inspection) }}">
                @csrf
                <button type="submit"
                    class="px-4 py-2 bg-amber-500 text-white rounded-xl text-sm font-semibold hover:bg-amber-600 transition">
                    ✅ Marquer complété
                </button>
            </form>
            @endif
            @if($inspection->status === 'completed')
            <form method="POST" action="{{ route('owner.property-inspections.sign', $inspection) }}">
                @csrf
                <button type="submit"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition">
                    ✍️ Signer
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Contenu principal - pièces --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Info générale --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Résidence</div>
                        <div class="font-medium">{{ $inspection->residence->title }}</div>
                    </div>
                    @if($inspection->tenant)
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Locataire</div>
                        <div class="font-medium">{{ $inspection->tenant->name }}</div>
                    </div>
                    @endif
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Date</div>
                        <div class="font-medium">{{ $inspection->inspection_date->format('d/m/Y') }}</div>
                    </div>
                    @if($inspection->electricity_index !== null)
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">⚡ Électricité</div>
                        <div class="font-medium">{{ $inspection->electricity_index }} kWh</div>
                    </div>
                    @endif
                    @if($inspection->water_index !== null)
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">💧 Eau</div>
                        <div class="font-medium">{{ $inspection->water_index }} m³</div>
                    </div>
                    @endif
                    @if($inspection->keys_handed_over)
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">🔑 Clés</div>
                        <div class="font-medium">{{ $inspection->keys_count ?? 0 }} remise(s)</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Pièces et éléments (Alpine.js interactif) --}}
            @php $itemsByRoom = $inspection->itemsByRoom(); @endphp

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800">Détail par pièce</h2>
                    @if(in_array($inspection->status, ['draft', 'in_progress']))
                    <button type="button" @click="showAddItem = !showAddItem"
                        class="px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-medium hover:bg-indigo-100 transition">
                        + Ajouter un élément
                    </button>
                    @endif
                </div>

                {{-- Formulaire d'ajout d'élément --}}
                @if(in_array($inspection->status, ['draft', 'in_progress']))
                <div x-show="showAddItem" x-cloak class="p-5 bg-indigo-50 border-b border-indigo-100">
                    <form @submit.prevent="addItem"
                        class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-indigo-700 mb-1 block">Pièce *</label>
                            <input type="text" x-model="newItem.room" list="room-suggestions" required
                                class="w-full px-3 py-2 border border-indigo-200 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ex: Salon, Chambre 1...">
                            <datalist id="room-suggestions">
                                @foreach(\App\Models\InspectionItem::defaultRooms() as $room)
                                <option value="{{ $room }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label class="text-xs text-indigo-700 mb-1 block">Élément *</label>
                            <input type="text" x-model="newItem.element" list="element-suggestions" required
                                class="w-full px-3 py-2 border border-indigo-200 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ex: Peintures, Carrelage...">
                            <datalist id="element-suggestions">
                                @foreach(\App\Models\InspectionItem::defaultElements() as $el)
                                <option value="{{ $el }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label class="text-xs text-indigo-700 mb-1 block">État *</label>
                            <select x-model="newItem.condition" required
                                class="w-full px-3 py-2 border border-indigo-200 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="new">Neuf</option>
                                <option value="good">Bon</option>
                                <option value="fair">Passable</option>
                                <option value="damaged">Abîmé</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-indigo-700 mb-1 block">Coût estimé (FCFA)</label>
                            <input type="number" x-model="newItem.repair_estimate" min="0" step="500"
                                class="w-full px-3 py-2 border border-indigo-200 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="0">
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs text-indigo-700 mb-1 block">Notes</label>
                            <input type="text" x-model="newItem.notes"
                                class="w-full px-3 py-2 border border-indigo-200 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Observations...">
                        </div>
                        <div class="col-span-2 flex gap-2">
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                                Ajouter
                            </button>
                            <button type="button" @click="showAddItem = false"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                {{-- Liste par pièce --}}
                @if($itemsByRoom->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">
                    Aucun élément pour l'instant. Ajoutez des pièces et équipements.
                </div>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($itemsByRoom as $room => $items)
                    <div>
                        <div class="px-5 py-2 bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wide">
                            {{ $room }}
                        </div>
                        @foreach($items as $item)
                        @php
                            $condColors = ['new' => 'emerald', 'good' => 'blue', 'fair' => 'amber', 'damaged' => 'red'];
                            $condLabels = ['new' => 'Neuf', 'good' => 'Bon', 'fair' => 'Passable', 'damaged' => 'Abîmé'];
                        @endphp
                        <div class="px-5 py-3 flex items-start justify-between gap-4"
                            x-data="{ editing: false, cond: '{{ $item->condition }}', notes: '{{ addslashes($item->notes ?? '') }}', estimate: '{{ $item->repair_estimate ?? '' }}' }">
                            <div class="flex-1">
                                <div class="font-medium text-sm text-gray-900">{{ $item->element }}</div>
                                <div x-show="!editing" class="text-xs text-gray-400 mt-0.5">{{ $item->notes ?: '—' }}</div>
                                <div x-show="editing" x-cloak class="mt-2 grid grid-cols-2 gap-2">
                                    <select x-model="cond"
                                        class="px-2 py-1 border rounded text-xs focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="new">Neuf</option>
                                        <option value="good">Bon</option>
                                        <option value="fair">Passable</option>
                                        <option value="damaged">Abîmé</option>
                                    </select>
                                    <input type="number" x-model="estimate" placeholder="Coût FCFA"
                                        class="px-2 py-1 border rounded text-xs focus:ring-indigo-500 focus:border-indigo-500">
                                    <input type="text" x-model="notes" placeholder="Notes..."
                                        class="col-span-2 px-2 py-1 border rounded text-xs focus:ring-indigo-500 focus:border-indigo-500">
                                    <button type="button"
                                        @click="updateItem({{ $item->id }}, { condition: cond, notes: notes, repair_estimate: estimate }); editing = false"
                                        class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-medium">
                                        Sauvegarder
                                    </button>
                                    <button type="button" @click="editing = false"
                                        class="px-3 py-1 bg-gray-100 rounded text-xs font-medium">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                @if($item->repair_estimate)
                                <span class="text-xs font-semibold text-red-600">
                                    {{ number_format($item->repair_estimate, 0, ',', ' ') }} FCFA
                                </span>
                                @endif
                                <span x-show="!editing"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold
                                    {{ $item->condition === 'new' ? 'bg-emerald-100 text-emerald-700' :
                                       ($item->condition === 'good' ? 'bg-blue-100 text-blue-700' :
                                       ($item->condition === 'fair' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')) }}">
                                    {{ $condLabels[$item->condition] ?? '—' }}
                                </span>
                                @if(in_array($inspection->status, ['draft', 'in_progress']))
                                <button type="button" @click="editing = !editing"
                                    class="text-xs text-indigo-500 hover:text-indigo-700">
                                    ✏️
                                </button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Observations --}}
            @if($inspection->general_observations)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-gray-800 mb-2">Observations générales</h2>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $inspection->general_observations }}</p>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">

            {{-- Résumé --}}
            @php
                $totalRepair = $inspection->items->whereNotNull('repair_estimate')->sum('repair_estimate');
                $damaged = $inspection->items->where('condition', 'damaged')->count();
                $total = $inspection->items->count();
            @endphp
            @if($total > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-3">Résumé</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Éléments inspectés</span>
                        <span class="font-medium">{{ $total }}</span>
                    </div>
                    @if($damaged > 0)
                    <div class="flex justify-between">
                        <span class="text-red-500">Éléments abîmés</span>
                        <span class="font-medium text-red-600">{{ $damaged }}</span>
                    </div>
                    @endif
                    @if($totalRepair > 0)
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <span class="text-gray-700 font-medium">Coût estimé total</span>
                        <span class="font-bold text-red-600">{{ number_format($totalRepair, 0, ',', ' ') }} FCFA</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Signatures --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-3">Signatures</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex gap-2 items-center">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs
                            {{ $inspection->owner_signed_at ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400' }}">
                            {{ $inspection->owner_signed_at ? '✓' : '?' }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-800">Propriétaire</div>
                            <div class="text-xs text-gray-400">{{ $inspection->owner_signed_at ? 'Signé le ' . $inspection->owner_signed_at->format('d/m/Y') : 'En attente' }}</div>
                        </div>
                    </div>
                    @if($inspection->tenant)
                    <div class="flex gap-2 items-center">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs
                            {{ $inspection->tenant_signed_at ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400' }}">
                            {{ $inspection->tenant_signed_at ? '✓' : '?' }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-800">Locataire</div>
                            <div class="text-xs text-gray-400">{{ $inspection->tenant_signed_at ? 'Signé le ' . $inspection->tenant_signed_at->format('d/m/Y') : 'En attente' }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Comparer --}}
            @if($inspection->type === 'check_out')
            <a href="{{ route('owner.property-inspections.compare', $inspection->residence) }}"
                class="block w-full py-2.5 bg-indigo-50 text-indigo-700 rounded-xl text-sm font-semibold text-center hover:bg-indigo-100 transition">
                📊 Comparer entrée / sortie
            </a>
            @endif
        </div>
    </div>
</div>

<script>
function inspectionApp() {
    return {
        showAddItem: false,
        newItem: { room: '', element: '', condition: 'good', notes: '', repair_estimate: '' },

        async addItem() {
            const response = await fetch('{{ route('owner.property-inspections.items.add', $inspection) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(this.newItem),
            });
            if (response.ok) {
                window.location.reload();
            }
        },

        async updateItem(itemId, data) {
            const response = await fetch(`{{ url('owner/property-inspections/' . $inspection->id . '/items') }}/${itemId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });
            if (response.ok) {
                window.location.reload();
            }
        }
    }
}
</script>
@endsection
