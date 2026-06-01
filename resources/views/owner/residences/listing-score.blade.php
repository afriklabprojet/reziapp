@extends('layouts.owner')

@section('title', 'Score qualité — ' . $residence->title)

@section('owner-content')
<div class="max-w-2xl space-y-6" x-data="listingScoreApp()">

    <nav class="text-sm text-gray-400">
        <a href="{{ route('owner.residences.index') }}" class="hover:text-[#F16A00]">Résidences</a>
        <span class="mx-2">›</span>
        <a href="{{ route('owner.residences.show', $residence) }}" class="hover:text-[#F16A00]">{{ $residence->title }}</a>
        <span class="mx-2">›</span>
        <span class="text-gray-700">Score qualité</span>
    </nav>

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">⭐ Score qualité de l'annonce</h1>
        <button type="button" @click="recompute"
            :disabled="computing"
            class="px-4 py-2 bg-[#F16A00] text-white rounded-xl text-sm font-semibold hover:bg-[#CC5A00] transition disabled:opacity-50">
            <span x-show="!computing">🔄 Recalculer</span>
            <span x-show="computing" x-cloak>Calcul...</span>
        </button>
    </div>

    <p class="text-gray-500 text-sm">{{ $residence->title }}</p>

    {{-- Score principal --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
        @php
            $score = $residence->listing_score ?? 0;
            $color = $score >= 80 ? 'emerald' : ($score >= 60 ? 'blue' : ($score >= 40 ? 'amber' : 'red'));
            $label = $score >= 80 ? 'Excellent' : ($score >= 60 ? 'Très bien' : ($score >= 40 ? 'Bien' : ($score > 0 ? 'À améliorer' : 'Non calculé')));
        @endphp

        <div class="relative w-40 h-40 mx-auto mb-4">
            <svg class="w-40 h-40 transform -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#f3f4f6" stroke-width="10"/>
                <circle cx="50" cy="50" r="45" fill="none"
                    stroke="{{ $color === 'emerald' ? '#10b981' : ($color === 'blue' ? '#3b82f6' : ($color === 'amber' ? '#f59e0b' : '#ef4444')) }}"
                    stroke-width="10"
                    stroke-dasharray="{{ ($score / 100) * 283 }} 283"
                    stroke-linecap="round"
                    x-bind:stroke-dasharray="currentScore ? (currentScore / 100 * 283) + ' 283' : '{{ ($score / 100) * 283 }} 283'"
                    />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <div class="text-4xl font-bold text-gray-900" x-text="currentScore !== null ? currentScore : '{{ $score }}'">{{ $score }}</div>
                <div class="text-xs text-gray-400">/ 100</div>
            </div>
        </div>

        <div class="text-xl font-semibold text-{{ $color }}-600" x-text="currentLabel">{{ $label }}</div>
        @if($residence->listing_score_computed_at)
        <div class="text-xs text-gray-400 mt-2">Calculé {{ $residence->listing_score_computed_at->diffForHumans() }}</div>
        @endif
    </div>

    {{-- Détail par critère --}}
    @if($residence->listing_score_breakdown)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
        <h2 class="font-semibold text-gray-800">Détail par critère</h2>
        @php $breakdown = $residence->listing_score_breakdown; @endphp
        @foreach($breakdown as $criterion => $data)
        @php
            $pct = ($data['score'] / $data['max']) * 100;
            $barColor = $pct >= 80 ? 'emerald' : ($pct >= 50 ? 'blue' : 'red');
        @endphp
        <div>
            <div class="flex justify-between text-sm mb-1">
                <span class="font-medium text-gray-700">{{ $data['label'] ?? $criterion }}</span>
                <span class="text-{{ $barColor }}-600 font-semibold">{{ $data['score'] }}/{{ $data['max'] }}</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
                <div class="h-2 rounded-full bg-{{ $barColor }}-500 transition-all duration-500"
                    style="width: {{ $pct }}%"></div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Conseils d'amélioration --}}
    @if(isset($tips) && count($tips) > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-3">
        <h2 class="font-semibold text-gray-800">💡 Conseils pour améliorer votre score</h2>
        @foreach($tips as $tip)
        <div class="flex items-start gap-3 p-3 bg-[#FFF4EB] rounded-xl">
            <div class="w-8 h-8 bg-[#FFE7D1] rounded-lg flex items-center justify-center shrink-0">
                @switch($tip['priority'] ?? 'low')
                    @case('high') <span class="text-red-500 text-sm">🔴</span> @break
                    @case('medium') <span class="text-amber-500 text-sm">🟡</span> @break
                    @default <span class="text-blue-500 text-sm">🔵</span>
                @endswitch
            </div>
            <div>
                <div class="font-medium text-sm text-gray-800">{{ $tip['message'] }}</div>
                @if(isset($tip['action']))
                <div class="text-xs text-gray-500 mt-0.5">{{ $tip['action'] }}</div>
                @endif
                @if(isset($tip['points']))
                <div class="text-xs text-[#CC5A00] mt-1 font-medium">+{{ $tip['points'] }} pts potentiels</div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <a href="{{ route('owner.residences.edit', $residence) }}"
        class="block w-full py-3 bg-[#F16A00] text-white rounded-xl font-semibold text-center hover:bg-[#CC5A00] transition">
        ✏️ Modifier l'annonce pour améliorer le score
    </a>
</div>

<script>
function listingScoreApp() {
    return {
        computing: false,
        currentScore: null,
        currentLabel: '{{ $label }}',

        async recompute() {
            this.computing = true;
            try {
                const response = await fetch('{{ route('owner.listing-score.compute', $residence) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();
                if (data.score !== undefined) {
                    this.currentScore = data.score;
                    this.currentLabel = data.label;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.computing = false;
                window.location.reload();
            }
        }
    }
}
</script>
@endsection
