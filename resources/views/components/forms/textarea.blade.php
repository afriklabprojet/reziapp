@props([
    'rows' => 4,
    'maxLength' => null,
    'showCount' => false,
    'placeholder' => '',
    'resize' => 'vertical', // none, vertical, horizontal, both
])

@php
    $id = $attributes->get('id') ?? $attributes->get('name') ?? 'textarea-' . uniqid();
    
    $resizeClasses = [
        'none' => 'resize-none',
        'vertical' => 'resize-y',
        'horizontal' => 'resize-x',
        'both' => 'resize',
    ];
    $resizeClass = $resizeClasses[$resize] ?? $resizeClasses['vertical'];
@endphp

<div x-data="{ charCount: 0 }">
    <textarea
        id="{{ $id }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if($maxLength) maxlength="{{ $maxLength }}" @endif
        @if($showCount) x-on:input="charCount = $event.target.value.length" @endif
        {{ $attributes->merge(['class' => "w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 focus:outline-none transition-colors text-sm placeholder-gray-400 $resizeClass"]) }}
    >{{ $slot }}</textarea>
    
    @if($showCount && $maxLength)
        <div class="mt-1 text-right">
            <span class="text-xs text-gray-400">
                <span x-text="charCount">0</span> / {{ $maxLength }}
            </span>
        </div>
    @endif
</div>
