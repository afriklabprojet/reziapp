{{-- Composant Image Lazy Loading Optimisé --}}
@props([
    'src',
    'alt' => '',
    'width' => null,
    'height' => null,
    'class' => '',
    'placeholder' => 'blur', // blur, skeleton, none
    'sizes' => '100vw',
    'srcset' => null,
    'aspectRatio' => null, // 16/9, 4/3, 1/1, etc
])

@php
    $uniqueId = 'img-' . Str::random(8);
    
    // Calculer le style aspect-ratio
    $aspectStyle = $aspectRatio ? "aspect-ratio: {$aspectRatio};" : '';
    
    // Générer srcset automatique si pas fourni (pour images locales)
    if (!$srcset && Str::startsWith($src, '/') && !Str::contains($src, 'placeholder')) {
        $basePath = pathinfo($src, PATHINFO_DIRNAME);
        $filename = pathinfo($src, PATHINFO_FILENAME);
        $extension = pathinfo($src, PATHINFO_EXTENSION);
        
        // Vérifier si des versions optimisées existent
        $srcset = collect([320, 640, 768, 1024, 1280])
            ->filter(fn($w) => file_exists(public_path("{$basePath}/{$filename}-{$w}w.{$extension}")))
            ->map(fn($w) => "{$basePath}/{$filename}-{$w}w.{$extension} {$w}w")
            ->join(', ');
        
        if (empty($srcset)) {
            $srcset = null;
        }
    }
    
    // Placeholder blur data (si fichier existe)
    $blurPlaceholder = null;
    if ($placeholder === 'blur') {
        $blurPath = str_replace('.', '-blur.', $src);
        if (file_exists(public_path($blurPath))) {
            $blurPlaceholder = $blurPath;
        }
    }
@endphp

<div 
    x-data="lazyImage('{{ $src }}', '{{ $blurPlaceholder }}')"
    x-init="observe()"
    class="relative overflow-hidden {{ $class }}"
    style="{{ $aspectStyle }}"
>
    {{-- Placeholder Blur --}}
    @if($placeholder === 'blur')
    <div 
        x-show="!loaded"
        class="absolute inset-0 bg-gray-200 animate-pulse"
        :class="{ 'blur-sm': blurSrc }"
        :style="blurSrc ? `background-image: url(${blurSrc}); background-size: cover;` : ''"
    ></div>
    @endif

    {{-- Placeholder Skeleton --}}
    @if($placeholder === 'skeleton')
    <div 
        x-show="!loaded"
        class="absolute inset-0 bg-linear-to-r from-gray-200 via-gray-100 to-gray-200 animate-shimmer"
    ></div>
    @endif

    {{-- Image réelle --}}
    <img loading="lazy" x-ref="img"
        :src="loaded ? actualSrc : ''"
        data-src="{{ $src }}"
        alt="{{ $alt }}"
        @if($width) width="{{ $width }}" @endif
        @if($height) height="{{ $height }}" @endif
        @if($srcset) srcset="{{ $srcset }}" @endif
        sizes="{{ $sizes }}"
        loading="lazy"
        decoding="async"
        @load="onLoad()"
        @error="onError()"
        class="w-full h-full object-cover transition-opacity duration-300"
        :class="{ 'opacity-0': !loaded, 'opacity-100': loaded }"
    >

    {{-- Error State --}}
    <div 
        x-show="error"
        class="absolute inset-0 flex flex-col items-center justify-center bg-gray-100 text-gray-400"
    >
        <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="text-sm">Image non disponible</span>
    </div>
</div>

@once
@push('scripts')

<style>
@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}
.animate-shimmer {
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}
</style>
@endpush
@endonce
