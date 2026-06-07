@extends('layouts.owner')

@section('title', 'Portfolio multi-résidences — Rezi Studio Meublé Faya')

@section('owner-content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Portfolio</h1>
        <p class="text-sm text-gray-500 mt-1">Vue d'ensemble de toutes vos résidences</p>
    </div>

    {{-- Global Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Résidences</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['total_residences'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Revenus total</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($summary['total_revenue'], 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-400">FCFA</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Dépenses total</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($summary['total_expenses'], 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-400">FCFA</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Taux d'occupation moy.</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ round($summary['avg_occupancy'] ?? 0) }}%</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Maintenance ouverte</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ $summary['total_maintenance'] }}</p>
        </div>
    </div>

    {{-- Per Residence Cards --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse($portfolioData as $data)
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="font-bold text-gray-900">{{ $data['residence']->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $data['residence']->commune ?? '' }}</p>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{ $data['residence']->is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $data['residence']->is_available ? 'Disponible' : 'Indisponible' }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="p-3 bg-green-50 rounded-xl">
                    <p class="text-xs text-green-600 font-medium">Revenus</p>
                    <p class="text-lg font-bold text-green-700">{{ number_format($data['month_revenue'], 0, ',', ' ') }}</p>
                </div>
                <div class="p-3 bg-red-50 rounded-xl">
                    <p class="text-xs text-red-600 font-medium">Dépenses</p>
                    <p class="text-lg font-bold text-red-700">{{ number_format($data['month_expenses'], 0, ',', ' ') }}</p>
                </div>
            </div>

            <div class="space-y-2">
                {{-- Occupancy --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-gray-500">Taux d'occupation</span>
                        <span class="text-xs font-semibold text-gray-700">{{ round($data['occupancy_rate']) }}%</span>
                    </div>
                    <div class="bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-blue-500 h-full rounded-full" style="width: {{ min(round($data['occupancy_rate']), 100) }}%"></div>
                    </div>
                </div>

                {{-- Stats line --}}
                <div class="flex items-center gap-4 text-xs text-gray-500 pt-1">
                    <span>🔧 {{ $data['open_maintenance'] }} maintenance</span>
                    <span>🧹 {{ $data['pending_cleaning'] }} ménage</span>
                    <span class="font-semibold {{ $data['net_income'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $data['net_income'] >= 0 ? '+' : '' }}{{ number_format($data['net_income'], 0, ',', ' ') }} FCFA net
                    </span>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" /></svg>
            <p class="text-gray-400 font-medium">Aucune résidence dans votre portfolio</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
