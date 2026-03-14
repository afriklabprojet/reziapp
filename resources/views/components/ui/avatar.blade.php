@props([
    'src' => null,
    'alt' => '',
    'size' => 'md',     // xs, sm, md, lg, xl
    'initials' => null, // Afficher les initiales si pas d'image
    'status' => null,   // online, offline, away, busy
    'rounded' => true,  // true = cercle, false = carré arrondi
])

@php
    $sizes = [
        'xs' => 'w-6 h-6 text-xs',
        'sm' => 'w-8 h-8 text-sm',
        'md' => 'w-10 h-10 text-base',
        'lg' => 'w-12 h-12 text-lg',
        'xl' => 'w-16 h-16 text-xl',
        '2xl' => 'w-20 h-20 text-2xl',
    ];

    $statusColors = [
        'online' => 'bg-green-500',
        'offline' => 'bg-gray-400',
        'away' => 'bg-yellow-500',
        'busy' => 'bg-red-500',
    ];

    $statusSizes = [
        'xs' => 'w-1.5 h-1.5',
        'sm' => 'w-2 h-2',
        'md' => 'w-2.5 h-2.5',
        'lg' => 'w-3 h-3',
        'xl' => 'w-3.5 h-3.5',
        '2xl' => 'w-4 h-4',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $roundedClass = $rounded ? 'rounded-full' : 'rounded-xl';
    $statusColor = $status ? ($statusColors[$status] ?? $statusColors['offline']) : null;
    $statusSize = $statusSizes[$size] ?? $statusSizes['md'];

    // Générer les initiales à partir de alt si non fournies
    if (!$initials && $alt) {
        $words = explode(' ', $alt);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
    }
@endphp

<div {{ $attributes->merge(['class' => 'relative inline-flex']) }}>
    @if($src)
        <img loading="lazy" src="{{ $src }}" 
            alt="{{ $alt }}"
            class="{{ $sizeClass }} {{ $roundedClass }} object-cover ring-2 ring-white"
            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
        >
        {{-- Fallback pour erreur de chargement --}}
        <div class="{{ $sizeClass }} {{ $roundedClass }} bg-linear-to-br from-orange-400 to-orange-600 items-center justify-center text-white font-semibold ring-2 ring-white" style="display: none;">
            {{ $initials ?? '?' }}
        </div>
    @elseif($initials)
        <div class="{{ $sizeClass }} {{ $roundedClass }} bg-linear-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-semibold ring-2 ring-white">
            {{ $initials }}
        </div>
    @else
        {{-- Default avatar icon --}}
        <div class="{{ $sizeClass }} {{ $roundedClass }} bg-gray-200 flex items-center justify-center text-gray-500 ring-2 ring-white">
            <svg class="w-1/2 h-1/2" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
        </div>
    @endif

    {{-- Status indicator --}}
    @if($status)
        <span class="absolute bottom-0 right-0 {{ $statusSize }} {{ $statusColor }} rounded-full ring-2 ring-white"></span>
    @endif
</div>
