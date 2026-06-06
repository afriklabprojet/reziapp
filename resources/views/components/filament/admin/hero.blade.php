@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
    'icon' => null,
    'tone' => 'orange',
])

@php
    $toneStyles = [
        'orange' => 'background: linear-gradient(135deg, #f16a00 0%, #ff8a1f 34%, #0f172a 100%); color: #ffffff;',
        'slate' => 'background: linear-gradient(135deg, #0f172a 0%, #1e293b 45%, #334155 100%); color: #ffffff;',
        'emerald' => 'background: linear-gradient(135deg, #10b981 0%, #34d399 34%, #0f172a 100%); color: #ffffff;',
        'blue' => 'background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 38%, #0f172a 100%); color: #ffffff;',
        'rose' => 'background: linear-gradient(135deg, #f43f5e 0%, #ec4899 38%, #0f172a 100%); color: #ffffff;',
        'red' => 'background: linear-gradient(135deg, #ef4444 0%, #f43f5e 38%, #0f172a 100%); color: #ffffff;',
    ];

    $heroStyle = $toneStyles[$tone] ?? $toneStyles['orange'];
    $metaStyle = 'background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.18); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);';
@endphp

<section {{ $attributes->class(['overflow-hidden rounded-3xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10']) }}>
    <div class="relative isolate overflow-hidden" style="{{ $heroStyle }}">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-70" style="background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.22), transparent 28%),
            radial-gradient(circle at bottom left, rgba(255, 255, 255, 0.10), transparent 30%);"></div>
        <div class="grid gap-6 px-6 py-6 lg:grid-cols-[minmax(0,1fr),auto] lg:px-8 lg:py-8">
            <div class="relative z-10 space-y-4">
                @if ($eyebrow)
                    <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium uppercase tracking-[0.2em] text-white/90 ring-1 ring-white/15" style="background: rgba(255, 255, 255, 0.14);">
                        @if ($icon)
                            <x-dynamic-component :component="$icon" class="h-3.5 w-3.5" />
                        @endif
                        <span>{{ $eyebrow }}</span>
                    </div>
                @endif

                <div class="space-y-2">
                    <h2 class="text-2xl font-semibold tracking-tight lg:text-3xl">{{ $title }}</h2>

                    @if ($subtitle)
                        <p class="max-w-3xl text-sm leading-6 text-white/80 lg:text-base">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            @if (isset($actions) || isset($meta))
                <div class="relative z-10 grid gap-3 self-start">
                    @isset($actions)
                        <div class="flex flex-wrap items-center justify-start gap-2 lg:justify-end">
                            {{ $actions }}
                        </div>
                    @endisset

                    @isset($meta)
                        <div class="grid gap-3 rounded-2xl p-4 backdrop-blur-sm sm:grid-cols-2 lg:grid-cols-1" style="{{ $metaStyle }}">
                            {{ $meta }}
                        </div>
                    @endisset
                </div>
            @endif
        </div>
    </div>
</section>
