{{-- Composant Bouton Favoris --}}
@props([
    'residenceId',
    'size' => 'md', // sm, md, lg
    'showText' => false,
    'class' => '',
])

@php
    $sizeClasses = match($size) {
        'sm' => 'w-8 h-8',
        'lg' => 'w-12 h-12',
        default => 'w-10 h-10',
    };
    $iconSizes = match($size) {
        'sm' => 'w-4 h-4',
        'lg' => 'w-6 h-6',
        default => 'w-5 h-5',
    };
@endphp

<div 
    x-data="favoriteButton(@js(['residenceId' => $residenceId, 'isAuthenticated' => auth()->check()]))"
    {{ $attributes->merge(['class' => $class]) }}
>
    <button 
        @click="toggle()"
        :disabled="loading"
        class="{{ $sizeClasses }} rounded-full flex items-center justify-center transition-all duration-300 
               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500
               {{ $showText ? 'px-4 w-auto! gap-2' : '' }}"
        :class="{
            'bg-red-500 text-white shadow-lg shadow-red-500/30': isFavorite,
            'bg-white/90 backdrop-blur text-gray-600 hover:text-red-500 shadow-md hover:shadow-lg': !isFavorite,
            'opacity-50 cursor-wait': loading
        }"
        :title="isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
    >
        {{-- Icône Cœur avec animation --}}
        <svg 
            class="{{ $iconSizes }} transition-transform duration-300"
            :class="{ 'scale-110': isFavorite, 'animate-heartbeat': justToggled }"
            fill="currentColor" 
            :fill="isFavorite ? 'currentColor' : 'none'"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
        >
            <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
            />
        </svg>

        @if($showText)
            <span 
                x-text="isFavorite ? 'Favori' : 'Ajouter'" 
                class="text-sm font-medium"
            ></span>
        @endif
    </button>

    {{-- Toast notification --}}
    <div 
        x-show="showToast"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed bottom-20 left-1/2 -translate-x-1/2 z-50 md:bottom-8"
    >
        <div 
            class="px-4 py-3 rounded-xl shadow-lg flex items-center gap-2"
            :class="isFavorite ? 'bg-red-500 text-white' : 'bg-gray-800 text-white'"
        >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <span x-text="toastMessage" class="text-sm font-medium"></span>
        </div>
    </div>
</div>

<style>
@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    25% { transform: scale(1.2); }
    50% { transform: scale(1); }
    75% { transform: scale(1.1); }
}
.animate-heartbeat {
    animation: heartbeat 0.6s ease-in-out;
}
</style>
