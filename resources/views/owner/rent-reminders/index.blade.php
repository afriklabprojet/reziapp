@extends('layouts.owner')

@section('title', 'Relances de paiement — Rezi App')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Relances de paiement</h1>
            <p class="text-sm text-gray-500 mt-1">Gérez les rappels de paiement automatiques</p>
        </div>
        <a href="{{ route('owner.rent-reminders.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Nouvelle relance
        </a>
    </div>

    {{-- Overdue Alert --}}
    @if($overdue['count'] > 0)
    <div class="bg-red-50 border border-red-200 rounded-2xl p-5">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
            </div>
            <div>
                <p class="font-semibold text-red-900">{{ $overdue['count'] }} paiement{{ $overdue['count'] > 1 ? 's' : '' }} de location en retard</p>
                <p class="text-sm text-red-700 mt-0.5">Total impayé : {{ number_format($overdue['total_amount'], 0, ',', ' ') }} FCFA</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Statut</label>
                <select name="status" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Tous</option>
                    @foreach(\App\Models\RentReminder::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Résidence</label>
                <select name="residence_id" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Toutes</option>
                    @foreach($residences as $r)
                        <option value="{{ $r->id }}" {{ request('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-sm font-medium rounded-xl transition-colors">Filtrer</button>
        </form>
    </div>

    {{-- List --}}
    <div class="space-y-3">
        @forelse($reminders as $reminder)
        <div class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                        {{ $reminder->isOverdue() ? 'bg-red-100' : ($reminder->isPaid() ? 'bg-green-100' : 'bg-amber-100') }}">
                        @if($reminder->isPaid())
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        @elseif($reminder->isOverdue())
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        @else
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                        @endif
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $reminder->tenant?->name ?? 'Locataire' }}</p>
                        <p class="text-sm text-gray-500">{{ $reminder->residence?->name ?? '—' }} · Échéance {{ $reminder->due_date->format('d/m/Y') }}</p>
                        @if($reminder->isOverdue())
                            <p class="text-xs text-red-600 font-medium mt-1">En retard de {{ abs($reminder->daysUntilDue()) }} jour{{ abs($reminder->daysUntilDue()) > 1 ? 's' : '' }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-lg font-bold text-gray-900">{{ number_format($reminder->amount, 0, ',', ' ') }} F</span>
                    @if(!$reminder->isPaid())
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('owner.rent-reminders.send', $reminder) }}">
                                @csrf
                                <button class="px-3 py-1.5 text-xs font-semibold bg-[#FFF4EB] text-[#CC5A00] rounded-lg hover:bg-[#FFE7D1] transition-colors">Relancer</button>
                            </form>
                            <form method="POST" action="{{ route('owner.rent-reminders.mark-paid', $reminder) }}">
                                @csrf
                                <button class="px-3 py-1.5 text-xs font-semibold bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors">Payé</button>
                            </form>
                        </div>
                    @else
                        <span class="px-3 py-1.5 text-xs font-semibold bg-green-100 text-green-700 rounded-lg">Payé</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <p class="text-gray-400 font-medium">Aucune relance configurée</p>
        </div>
        @endforelse
    </div>

    @if($reminders->hasPages())
    <div>{{ $reminders->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
