@props([
    'items' => [],      // Array of tabs: ['id' => 'tab1', 'label' => 'Tab 1', 'icon' => '...', 'badge' => 5]
    'active' => null,   // Active tab ID
    'variant' => 'underline', // underline, pills, buttons
])

@php
    $variants = [
        'underline' => [
            'container' => 'border-b border-gray-200',
            'nav' => '-mb-px flex space-x-8',
            'tab' => 'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors',
            'active' => 'border-orange-500 text-orange-600',
            'inactive' => 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
        ],
        'pills' => [
            'container' => '',
            'nav' => 'flex space-x-2',
            'tab' => 'px-4 py-2 rounded-xl font-medium text-sm transition-colors',
            'active' => 'bg-orange-500 text-white',
            'inactive' => 'text-gray-600 hover:bg-gray-100',
        ],
        'buttons' => [
            'container' => 'bg-gray-100 p-1 rounded-xl',
            'nav' => 'flex',
            'tab' => 'flex-1 px-4 py-2 rounded-lg font-medium text-sm transition-colors text-center',
            'active' => 'bg-white text-gray-900 shadow-sm',
            'inactive' => 'text-gray-600 hover:text-gray-900',
        ],
    ];
    
    $config = $variants[$variant] ?? $variants['underline'];
@endphp

<div 
    x-data="{ activeTab: '{{ $active ?? ($items[0]['id'] ?? '') }}' }"
    {{ $attributes->merge(['class' => $config['container']]) }}
>
    {{-- Tab navigation --}}
    <nav class="{{ $config['nav'] }}" role="tablist">
        @foreach($items as $item)
            <button
                type="button"
                role="tab"
                :aria-selected="activeTab === '{{ $item['id'] }}'"
                @click="activeTab = '{{ $item['id'] }}'; $dispatch('tab-change', { tab: '{{ $item['id'] }}' })"
                :class="activeTab === '{{ $item['id'] }}' ? '{{ $config['active'] }}' : '{{ $config['inactive'] }}'"
                class="{{ $config['tab'] }} flex items-center gap-2"
            >
                @if(isset($item['icon']))
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $item['icon'] !!}
                    </svg>
                @endif
                
                <span>{{ $item['label'] }}</span>
                
                @if(isset($item['badge']))
                    <span 
                        class="ml-1 px-2 py-0.5 text-xs rounded-full"
                        :class="activeTab === '{{ $item['id'] }}' ? 'bg-orange-100 text-orange-600' : 'bg-gray-200 text-gray-600'"
                    >
                        {{ $item['badge'] }}
                    </span>
                @endif
            </button>
        @endforeach
    </nav>
    
    {{-- Tab panels --}}
    <div class="mt-4">
        {{ $slot }}
    </div>
</div>
