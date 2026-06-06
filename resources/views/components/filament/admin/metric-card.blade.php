@props([
    'label',
    'value',
    'icon' => null,
    'accent' => 'orange',
    'meta' => null,
    'note' => null,
])

@php
    $accents = [
        'orange' => ['badge' => 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-400/20', 'icon' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-300'],
        'emerald' => ['badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/20', 'icon' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'],
        'sky' => ['badge' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-400/20', 'icon' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300'],
        'rose' => ['badge' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-400/20', 'icon' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300'],
        'amber' => ['badge' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/20', 'icon' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300'],
        'slate' => ['badge' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700', 'icon' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'],
        'violet' => ['badge' => 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-400/20', 'icon' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300'],
    ];

    $tone = $accents[$accent] ?? $accents['orange'];
@endphp

<article {{ $attributes->class(['rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10']) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $value }}</p>
        </div>

        @if ($icon)
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl {{ $tone['icon'] }}">
                <x-dynamic-component :component="$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>

    @if ($meta)
        <div class="mt-5 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $tone['badge'] }}">
            {{ $meta }}
        </div>
    @endif

    @if ($note)
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ $note }}</p>
    @endif
</article>
