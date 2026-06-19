@extends('layouts.owner')

@section('title', $sequence->name . ' — Rezi App')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('owner.sequences.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                Séquences
            </a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $sequence->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $sequence->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $sequence->is_active ? 'Active' : 'Inactive' }}</span>
                · {{ \App\Models\MessageSequence::TRIGGERS[$sequence->trigger_event] ?? $sequence->trigger_event }}
            </p>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('owner.sequences.toggle', $sequence) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 {{ $sequence->is_active ? 'bg-[#FFF4EB] text-[#A34700] hover:bg-[#FFE7D1]' : 'bg-green-50 text-green-700 hover:bg-green-100' }} font-semibold rounded-xl transition-all text-sm">
                    {{ $sequence->is_active ? 'Désactiver' : 'Activer' }}
                </button>
            </form>
            <a href="{{ route('owner.sequences.edit', $sequence) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all text-sm">
                Modifier
            </a>
        </div>
    </div>

    {{-- Steps --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Étapes de la séquence</h2>

        <div class="space-y-4">
            @forelse($sequence->steps as $step)
            <div class="border border-gray-100 rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-700">{{ $step->step_order }}</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ \App\Models\MessageSequenceStep::CHANNELS[$step->channel] ?? $step->channel }}</p>
                            <p class="text-xs text-gray-500">{{ $step->delay_hours }}h {{ \App\Models\MessageSequenceStep::DELAY_REFERENCES[$step->delay_reference] ?? $step->delay_reference }}</p>
                            @if($step->subject)
                            <p class="text-xs text-gray-600 mt-2 font-medium">{{ $step->subject }}</p>
                            @endif
                            <p class="text-xs text-gray-500 mt-1 whitespace-pre-wrap">{{ Str::limit($step->message, 200) }}</p>
                        </div>
                    </div>
                    <form action="{{ route('owner.sequences.remove-step', [$sequence, $step]) }}" method="POST"  data-confirm='Supprimer cette étape ?'>
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-4">Aucune étape configurée</p>
            @endforelse
        </div>

        {{-- Add Step Form --}}
        <form action="{{ route('owner.sequences.add-step', $sequence) }}" method="POST" class="mt-6 pt-6 border-t border-gray-100 space-y-4">
            @csrf
            <p class="text-sm font-semibold text-gray-900">Ajouter une étape</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Délai (heures)</label>
                    <input type="number" name="delay_hours" min="0" value="0" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Référence</label>
                    <select name="delay_reference" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                        @foreach(\App\Models\MessageSequenceStep::DELAY_REFERENCES as $k => $l)
                        <option value="{{ $k }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Canal</label>
                    <select name="channel" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                        @foreach(\App\Models\MessageSequenceStep::CHANNELS as $k => $l)
                        <option value="{{ $k }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Sujet (email uniquement)</label>
                <input type="text" name="subject" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" placeholder="Bienvenue à {residence_name}">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Message *</label>
                <textarea name="message" rows="4" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" placeholder="Bonjour {guest_name}..."></textarea>
                <p class="text-xs text-gray-400 mt-1">Variables: {guest_name}, {residence_name}, {check_in_date}, {check_out_date}, {owner_name}, {booking_ref}</p>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 text-sm">Ajouter l'étape</button>
        </form>
    </div>
</div>
@endsection
