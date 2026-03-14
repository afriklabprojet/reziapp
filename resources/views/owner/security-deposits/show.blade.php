@extends('layouts.owner')

@section('title', 'Dépôt ' . $deposit->reference)

@section('owner-content')
<div class="space-y-6">

    <nav class="text-sm text-gray-400 flex items-center gap-2">
        <a href="{{ route('owner.security-deposits.index') }}" class="hover:text-amber-600">Dépôts de garantie</a>
        <span>›</span>
        <span class="text-gray-700 font-mono">{{ $deposit->reference }}</span>
    </nav>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">💰 {{ $deposit->reference }}</h1>
            <span class="inline-flex mt-2 px-3 py-1 rounded-full text-sm font-semibold bg-{{ $deposit->status_color }}-100 text-{{ $deposit->status_color }}-700">
                {{ $deposit->status_label }}
                @if($deposit->is_overdue) ⚠️ En retard @endif
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-5">

            {{-- Résumé --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Locataire</div>
                        <div class="font-semibold text-gray-900">{{ $deposit->tenant->name }}</div>
                        <div class="text-xs text-gray-400">{{ $deposit->tenant->email }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Montant total</div>
                        <div class="text-xl font-bold text-amber-500">{{ number_format($deposit->amount, 0, ',', ' ') }} FCFA</div>
                    </div>
                    @if($deposit->retained_amount > 0)
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Retenu</div>
                        <div class="text-xl font-bold text-red-500">{{ number_format($deposit->retained_amount, 0, ',', ' ') }} FCFA</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Détails --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Détails du dépôt</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Résidence</div>
                        <div class="font-medium">{{ $deposit->residence->title }}</div>
                    </div>
                    @if($deposit->leaseContract)
                    <div>
                        <div class="text-gray-500">Contrat lié</div>
                        <a href="{{ route('owner.lease-contracts.show', $deposit->leaseContract) }}"
                           class="font-medium text-emerald-600 hover:underline">
                            {{ $deposit->leaseContract->reference }}
                        </a>
                    </div>
                    @endif
                    @if($deposit->paid_at)
                    <div>
                        <div class="text-gray-500">Encaissé le</div>
                        <div class="font-medium">{{ $deposit->paid_at->format('d/m/Y') }}</div>
                    </div>
                    @endif
                    @if($deposit->return_deadline)
                    <div>
                        <div class="text-gray-500">Délai légal de restitution</div>
                        <div class="font-medium {{ $deposit->is_overdue ? 'text-red-600' : '' }}">
                            {{ $deposit->return_deadline->format('d/m/Y') }}
                            @if($deposit->is_overdue)
                                <span class="text-xs">({{ $deposit->return_deadline->diffForHumans() }})</span>
                            @endif
                        </div>
                    </div>
                    @endif
                    @if($deposit->payment_method)
                    <div>
                        <div class="text-gray-500">Mode de paiement</div>
                        <div class="font-medium">{{ $deposit->payment_method }}</div>
                    </div>
                    @endif
                    @if($deposit->returned_at)
                    <div>
                        <div class="text-gray-500">Restitué le</div>
                        <div class="font-medium text-emerald-600">{{ $deposit->returned_at->format('d/m/Y') }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Déductions --}}
            @if($deposit->deduction_items && count($deposit->deduction_items) > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Déductions</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 border-b">
                            <th class="pb-2">Description</th>
                            <th class="pb-2 text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deposit->deduction_items as $item)
                        <tr class="border-b border-gray-50">
                            <td class="py-2">{{ $item['description'] ?? '—' }}</td>
                            <td class="py-2 text-right font-medium">{{ number_format($item['amount'] ?? 0, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        @endforeach
                        <tr class="font-semibold">
                            <td class="pt-3">Total déduit</td>
                            <td class="pt-3 text-right text-red-600">{{ number_format($deposit->retained_amount, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Colonne droite --}}
        <div class="space-y-4">

            {{-- Actions --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
                <h3 class="font-semibold text-gray-800">Actions</h3>

                @if($deposit->status === 'pending')
                <form method="POST" action="{{ route('owner.security-deposits.mark-paid', $deposit) }}">
                    @csrf
                    <button type="submit"
                        class="w-full py-2.5 bg-amber-500 text-white rounded-xl text-sm font-semibold hover:bg-amber-600 transition">
                        ✅ Marquer comme encaissé
                    </button>
                </form>
                @endif

                @if(in_array($deposit->status, ['held', 'pending_return']))

                {{-- Restitution totale --}}
                <form method="POST" action="{{ route('owner.security-deposits.return-full', $deposit) }}"
                    x-data="{ confirm: false }">
                    @csrf
                    <button type="button" @click="confirm = !confirm"
                        class="w-full py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition">
                        🔄 Restituer intégralement
                    </button>
                    <div x-show="confirm" x-cloak class="mt-2 p-3 bg-emerald-50 rounded-xl text-xs text-emerald-700">
                        <p>Restituer <strong>{{ number_format($deposit->amount, 0, ',', ' ') }} FCFA</strong> ?</p>
                        <button type="submit" class="mt-2 w-full py-1.5 bg-emerald-600 text-white rounded-lg font-semibold">
                            Confirmer
                        </button>
                    </div>
                </form>

                {{-- Restitution partielle --}}
                <div x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                        class="w-full py-2.5 bg-amber-100 text-amber-700 rounded-xl text-sm font-semibold hover:bg-amber-200 transition">
                        ⚡ Restitution partielle
                    </button>
                    <div x-show="open" x-cloak class="mt-3">
                        <form method="POST" action="{{ route('owner.security-deposits.return-partial', $deposit) }}"
                            class="space-y-3 p-4 bg-gray-50 rounded-xl border">
                            @csrf
                            <div>
                                <label class="text-xs text-gray-600 mb-1 block">Montant à restituer (FCFA)</label>
                                <input type="number" name="returned_amount"
                                    max="{{ $deposit->amount }}" min="0" step="500"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-amber-500 focus:border-amber-500"
                                    placeholder="Ex: 150000">
                            </div>
                            <div>
                                <label class="text-xs text-gray-600 mb-1 block">Raison des déductions</label>
                                <textarea name="deduction_reason" rows="2"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-amber-500 focus:border-amber-500"
                                    placeholder="Dégradations constatées..."></textarea>
                            </div>
                            <button type="submit"
                                class="w-full py-2 bg-amber-500 text-white rounded-lg text-sm font-semibold hover:bg-amber-600 transition">
                                Valider la restitution
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
