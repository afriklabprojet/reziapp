@props([
    'label' => null,
    'checked' => false,
    'disabled' => false,
    'toggle' => false, // Switch style
])

@php
    $id = $attributes->get('id') ?? $attributes->get('name') ?? 'checkbox-' . uniqid();
@endphp

<label class="inline-flex items-center gap-3 cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
    @if($toggle)
        {{-- Toggle switch style --}}
        <div class="relative" x-data="{ checked: {{ $checked ? 'true' : 'false' }} }">
            <input 
                type="checkbox" 
                id="{{ $id }}"
                x-model="checked"
                {{ $disabled ? 'disabled' : '' }}
                {{ $attributes->merge(['class' => 'sr-only peer']) }}
            >
            <div 
                @click="if (!{{ $disabled ? 'true' : 'false' }}) checked = !checked"
                class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-orange-300 peer-checked:bg-orange-500 transition-colors"
            ></div>
            <div 
                class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"
                :class="checked ? 'translate-x-5' : 'translate-x-0'"
            ></div>
        </div>
    @else
        {{-- Standard checkbox style --}}
        <input 
            type="checkbox" 
            id="{{ $id }}"
            {{ $checked ? 'checked' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge(['class' => 'w-5 h-5 text-orange-500 bg-white border-gray-300 rounded focus:ring-orange-500 focus:ring-2 transition-colors']) }}
        >
    @endif
    
    @if($label || $slot->isNotEmpty())
        <span class="text-sm text-gray-700 select-none">
            @if($label)
                {{ $label }}
            @else
                {{ $slot }}
            @endif
        </span>
    @endif
</label>
