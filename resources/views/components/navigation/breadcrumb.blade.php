@props([
    'items' => [], // Array of ['label' => 'Name', 'url' => '/path'] or just strings
])

<nav {{ $attributes->merge(['class' => 'flex items-center text-sm']) }} aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        {{-- Home link --}}
        <li>
            <a href="{{ route('home') }}" class="text-gray-400 hover:text-orange-500 transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                <span class="sr-only">Accueil</span>
            </a>
        </li>
        
        @foreach($items as $index => $item)
            <li class="flex items-center">
                {{-- Separator --}}
                <svg class="w-4 h-4 text-gray-300 mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                
                @php
                    $isLast = $index === count($items) - 1;
                    $label = is_array($item) ? $item['label'] : $item;
                    $url = is_array($item) ? ($item['url'] ?? null) : null;
                @endphp
                
                @if($isLast)
                    <span class="text-gray-700 font-medium" aria-current="page">
                        {{ $label }}
                    </span>
                @elseif($url)
                    <a href="{{ $url }}" class="text-gray-500 hover:text-orange-500 transition-colors">
                        {{ $label }}
                    </a>
                @else
                    <span class="text-gray-500">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
