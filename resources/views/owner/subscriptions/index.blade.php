@extends('layouts.owner')

@section('title', 'Commission ReziApp - ReziApp')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @foreach (['success' => 'green', 'error' => 'red', 'info' => 'blue'] as $flashType => $flashColor)
        @if(session($flashType))
            <div class="mb-6 rounded-lg border p-4 {{ $flashColor === 'green' ? 'bg-green-50 border-green-200 text-green-700' : ($flashColor === 'red' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-blue-50 border-blue-200 text-blue-700') }}">
                <p class="text-sm font-medium">{{ session($flashType) }}</p>
            </div>
        @endif
    @endforeach

    <div class="mb-8 rounded-3xl bg-linear-to-br from-gray-950 via-gray-900 to-orange-900 px-6 py-8 text-white shadow-2xl">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <span class="inline-flex rounded-full border border-orange-300/30 bg-orange-400/10 px-4 py-1 text-sm font-semibold text-orange-200">
                    Aucun abonnement mensuel
                </span>
                <h1 class="mt-4 text-3xl font-bold sm:text-4xl">ReziApp prélève {{ rtrim(rtrim(number_format($commissionRate, 2, ',', ' '), '0'), ',') }}% sur chaque réservation propriétaire</h1>
                <p class="mt-3 text-base text-white/75 sm:text-lg">
                    Le modèle économique est simple: pas d'abonnement SaaS, pas de forfait annuel, pas de plan à choisir. La plateforme retient uniquement une commission sur le montant total encaissé pour chaque réservation confirmée.
                </p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-5 py-4 backdrop-blur">
                <p class="text-sm font-medium text-white/70">Exemple</p>
                <p class="mt-2 text-2xl font-bold">{{ number_format($exampleBookingAmount, 0, ',', ' ') }} FCFA</p>
                <p class="text-sm text-white/70">montant total réservation</p>
                <div class="mt-3 h-px bg-white/10"></div>
                <p class="mt-3 text-lg font-semibold text-orange-200">Commission ReziApp: {{ number_format($exampleCommissionAmount, 0, ',', ' ') }} FCFA</p>
            </div>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Réservations payées</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalReservations, 0, ',', ' ') }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Volume total encaissé</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalReservationVolume, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-orange-700">Commission ReziApp</p>
            <p class="mt-2 text-3xl font-bold text-orange-900">{{ number_format($totalCommission, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-700">Net propriétaire</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ number_format($totalOwnerRevenue, 0, ',', ' ') }} FCFA</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Dernières commissions calculées</h2>
                    <p class="mt-1 text-sm text-gray-500">Chaque ligne reprend le montant total de la réservation et la retenue ReziApp appliquée côté propriétaire.</p>
                </div>
                <a href="{{ route('owner.marketing.subscriptions.history') }}" class="btn-secondary">Voir tout</a>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-500">Référence</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-500">Résidence</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-500">Montant total</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-500">Commission</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-500">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($recentBookings as $booking)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $booking->reference ?? 'RES-'.$booking->id }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $booking->residence->name ?? 'Résidence supprimée' }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">{{ number_format($booking->total_amount, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-3 text-right font-medium text-orange-700">{{ number_format($booking->commission_amount, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ number_format($booking->owner_net_amount, 0, ',', ' ') }} FCFA</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-gray-500">Aucune réservation payée pour le moment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold text-gray-900">Comment ça marche</h2>
            <ol class="mt-5 space-y-4 text-sm text-gray-600">
                <li class="flex gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-900 text-xs font-bold text-white">1</span>
                    <span>Le locataire paie sa réservation normalement, sans abonnement SaaS à activer.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white">2</span>
                    <span>ReziApp retient {{ rtrim(rtrim(number_format($commissionRate, 2, ',', ' '), '0'), ',') }}% du montant total de la réservation, côté propriétaire.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-xs font-bold text-white">3</span>
                    <span>Le solde net propriétaire est ensuite visible dans votre espace revenus.</span>
                </li>
            </ol>

            <div class="mt-6 rounded-2xl bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-900">Important</p>
                <p class="mt-2 text-sm text-gray-600">Il n'existe plus de plan mensuel, annuel ou formule d'abonnement à souscrire sur ReziApp pour les propriétaires.</p>
            </div>

            <a href="{{ route('owner.earnings.index') }}" class="btn-primary mt-6 w-full justify-center">Ouvrir mes revenus</a>
        </div>
    </div>
</div>

<style>
    .btn-primary {
        @apply inline-flex items-center px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors;
    }
    .btn-secondary {
        @apply inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors;
    }
</style>
@endsection
