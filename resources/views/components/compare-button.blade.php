{{-- Add to Compare Button Component --}}
{{-- Usage: <x-compare-button :residence="$residence" /> --}}

@props(['residence', 'size' => 'md'])

@php
    $compareIds = session('compare_residences', []);
    $isInCompare = in_array($residence->id, $compareIds);
    $isFull = count($compareIds) >= 4;
    
    $sizeClasses = match($size) {
        'sm' => 'p-1.5',
        'lg' => 'p-3',
        default => 'p-2',
    };
    
    $iconSize = match($size) {
        'sm' => 'w-4 h-4',
        'lg' => 'w-6 h-6',
        default => 'w-5 h-5',
    };
@endphp

<button
    x-data="{ 
        inCompare: {{ $isInCompare ? 'true' : 'false' }},
        loading: false,
        toggle() {
            if (this.loading) return;
            this.loading = true;
            
            if (this.inCompare) {
                Livewire.dispatch('removeFromCompare', { residenceId: {{ $residence->id }} });
                this.inCompare = false;
            } else {
                Livewire.dispatch('addToCompare', { residenceId: {{ $residence->id }} });
                this.inCompare = true;
            }
            
            setTimeout(() => this.loading = false, 300);
        }
    }"
    @compare-updated.window="$event.detail.residenceIds && (inCompare = $event.detail.residenceIds.includes({{ $residence->id }}))"
    @click.prevent.stop="toggle()"
    :class="{ 'bg-orange-100 text-orange-600': inCompare, 'bg-white/90 text-gray-600 hover:bg-gray-100': !inCompare }"
    class="rounded-lg backdrop-blur-sm transition-all duration-200 {{ $sizeClasses }} shadow-sm border border-gray-200/50"
    :title="inCompare ? 'Retirer de la comparaison' : 'Ajouter à la comparaison'"
    {{ $attributes }}
>
    <svg x-show="!loading" :class="{ 'text-orange-600': inCompare }" class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
    </svg>
    <svg x-show="loading" class="{{ $iconSize }} animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
</button>
