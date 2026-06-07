@extends('layouts.owner')

@section('title', $smartLock->name . ' — Rezi App')

@section('owner-content')
<div class="space-y-6">
    <div>
        <a href="{{ route('owner.smart-locks.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Serrures
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $smartLock->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $smartLock->residence?->name }} · {{ \App\Models\SmartLock::PROVIDERS[$smartLock->provider] ?? $smartLock->provider }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Codes d'accès</h2>
                <div class="space-y-3">
                    @forelse($smartLock->codes as $code)
                    <div class="border border-gray-100 rounded-xl p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-lg font-bold text-gray-900">{{ $code->code }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $code->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $code->status }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">{{ $code->guest_name }}</p>
                                <p class="text-xs text-gray-400">{{ $code->valid_from?->format('d/m/Y') }} - {{ $code->valid_until?->format('d/m/Y') }}</p>
                            </div>
                            @if($code->status === 'active')
                            <form action="{{ route('owner.smart-locks.revoke-code', $code) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-xs font-semibold hover:bg-red-100">Révoquer</button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 text-center py-4">Aucun code généré</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Générer un code</h2>
                <form action="{{ route('owner.smart-locks.generate-code', $smartLock) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Nom du voyageur *</label>
                        <input type="text" name="guest_name" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" placeholder="Jean Dupont">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Valide du *</label>
                        <input type="date" name="valid_from" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Valide jusqu'au *</label>
                        <input type="date" name="valid_until" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" value="{{ now()->addDays(3)->format('Y-m-d') }}">
                    </div>
                    <button type="submit" class="w-full px-4 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm">Générer le code</button>
                </form>
            </div>

            <form action="{{ route('owner.smart-locks.destroy', $smartLock) }}" method="POST" onsubmit="return confirm('Supprimer cette serrure ?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full text-sm text-red-500 hover:text-red-700 text-center">Supprimer cette serrure</button>
            </form>
        </div>
    </div>
</div>
@endsection
