{{--
    Composant Carte de fidélité Rezi App (style Genius/Booking.com)
    Usage : <x-loyalty-card :user="auth()->user()" />
    Usage dans profil : <x-loyalty-card :user="$user" :show-progress="true" />
--}}
@props([
    'user'         => null,
    'showProgress' => true,
    'compact'      => false,
])

@php
    if (! $user) {
        return;
    }

    $tiers = \App\Models\User::LOYALTY_TIERS;
    $tier  = $user->loyalty_tier ?? 'standard';
    $cfg   = $tiers[$tier];

    $colors = [
        'gray'   => ['bg' => 'bg-gray-100',   'text' => 'text-gray-700',   'border' => 'border-gray-300',  'progress' => 'bg-gray-400'],
        'amber'  => ['bg' => 'bg-amber-50',   'text' => 'text-amber-800',  'border' => 'border-amber-300', 'progress' => 'bg-amber-500'],
        'slate'  => ['bg' => 'bg-slate-100',  'text' => 'text-slate-700',  'border' => 'border-slate-400', 'progress' => 'bg-slate-500'],
        'yellow' => ['bg' => 'bg-yellow-50',  'text' => 'text-yellow-800', 'border' => 'border-yellow-400','progress' => 'bg-yellow-500'],
        'violet' => ['bg' => 'bg-violet-50',  'text' => 'text-violet-800', 'border' => 'border-violet-400','progress' => 'bg-violet-600'],
    ];
    $c = $colors[$cfg['color']] ?? $colors['gray'];

    // Prochain palier
    $tierKeys   = array_keys($tiers);
    $currentIdx = array_search($tier, $tierKeys, true);
    $nextTier   = $tierKeys[$currentIdx + 1] ?? null;
    $nextCfg    = $nextTier ? $tiers[$nextTier] : null;

    $bookings       = $user->loyalty_bookings_count ?? 0;
    $bookingsToNext = $nextCfg ? max(0, $nextCfg['min_bookings'] - $bookings) : 0;
    $progressPct    = 100;
    if ($nextCfg) {
        $range       = $nextCfg['min_bookings'] - $cfg['min_bookings'];
        $done        = $bookings - $cfg['min_bookings'];
        $progressPct = $range > 0 ? min(100, (int) round($done / $range * 100)) : 100;
    }
@endphp

@if ($compact)
    {{-- Version compacte : badge inline --}}
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $c['bg'] }} {{ $c['text'] }} border {{ $c['border'] }}">
        <span>{{ $cfg['icon'] }}</span>
        {{ $cfg['label'] }}
        @if ($cfg['discount'] > 0)
            <span class="opacity-75">· {{ $cfg['discount'] }}% remise</span>
        @endif
    </span>
@else
    {{-- Version complète : carte --}}
    <div class="rounded-2xl border {{ $c['border'] }} {{ $c['bg'] }} p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <span class="text-3xl">{{ $cfg['icon'] }}</span>
                <div>
                    <p class="font-bold text-base {{ $c['text'] }}">{{ $cfg['label'] }}</p>
                    <p class="text-xs {{ $c['text'] }} opacity-70">Programme fidélité Rezi App</p>
                </div>
            </div>
            @if ($cfg['discount'] > 0)
                <div class="text-right">
                    <p class="text-2xl font-bold {{ $c['text'] }}">{{ $cfg['discount'] }}%</p>
                    <p class="text-xs {{ $c['text'] }} opacity-70">de remise</p>
                </div>
            @endif
        </div>

        {{-- Statistiques --}}
        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="text-center bg-white/60 rounded-xl py-2">
                <p class="text-lg font-bold {{ $c['text'] }}">{{ $bookings }}</p>
                <p class="text-xs {{ $c['text'] }} opacity-70">Séjours</p>
            </div>
            <div class="text-center bg-white/60 rounded-xl py-2">
                <p class="text-lg font-bold {{ $c['text'] }}">{{ $user->loyalty_nights_count ?? 0 }}</p>
                <p class="text-xs {{ $c['text'] }} opacity-70">Nuits</p>
            </div>
            <div class="text-center bg-white/60 rounded-xl py-2">
                <p class="text-lg font-bold {{ $c['text'] }}">{{ $user->loyalty_points ?? 0 }}</p>
                <p class="text-xs {{ $c['text'] }} opacity-70">Points</p>
            </div>
        </div>

        @if ($showProgress && $nextCfg)
            {{-- Barre de progression vers le prochain palier --}}
            <div class="mb-1">
                <div class="flex justify-between text-xs {{ $c['text'] }} opacity-70 mb-1">
                    <span>{{ $cfg['label'] }}</span>
                    <span>{{ $nextCfg['label'] }} ({{ $nextCfg['min_bookings'] }} séjours)</span>
                </div>
                <div class="h-2 bg-white/50 rounded-full overflow-hidden">
                    <div class="{{ $c['progress'] }} h-full rounded-full transition-all duration-500"
                         style="width: {{ $progressPct }}%"></div>
                </div>
                <p class="text-xs {{ $c['text'] }} opacity-60 mt-1">
                    @if ($bookingsToNext > 0)
                        Plus que {{ $bookingsToNext }} séjour{{ $bookingsToNext > 1 ? 's' : '' }} pour atteindre {{ $nextCfg['label'] }} ({{ $nextCfg['discount'] }}% de remise)
                    @else
                        Prochain palier atteint !
                    @endif
                </p>
            </div>
        @elseif ($showProgress && ! $nextCfg)
            <p class="text-xs {{ $c['text'] }} opacity-70 text-center font-medium">
                🎉 Palier maximum atteint — profitez de {{ $cfg['discount'] }}% sur chaque réservation !
            </p>
        @endif
    </div>
@endif
