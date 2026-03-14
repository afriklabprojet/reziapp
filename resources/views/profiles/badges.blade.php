@extends('layouts.app')

@section('title', 'Badges de ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- En-tête -->
    <div class="mb-8">
        <a href="{{ route('profile.public', $user) }}" class="text-orange-500 hover:text-orange-600 text-sm font-medium flex items-center gap-1 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour au profil
        </a>
        
        <div class="flex items-center gap-4">
            <img loading="lazy" src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Badges de {{ $user->name }}</h1>
                <p class="text-gray-500">{{ $activeBadges->count() }} badge(s) obtenus</p>
            </div>
        </div>
    </div>

    <!-- Badges obtenus -->
    @if($activeBadges->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Badges obtenus</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($activeBadges as $badge)
                    <div class="flex items-start gap-4 p-4 rounded-lg border-2 
                        @if($badge->color === 'gold') border-yellow-300 bg-yellow-50
                        @elseif($badge->color === 'blue') border-blue-300 bg-blue-50
                        @elseif($badge->color === 'green') border-green-300 bg-green-50
                        @elseif($badge->color === 'purple') border-purple-300 bg-purple-50
                        @elseif($badge->color === 'orange') border-orange-300 bg-orange-50
                        @elseif($badge->color === 'teal') border-teal-300 bg-orange-50
                        @else border-gray-300 bg-gray-50
                        @endif">
                        <div class="shrink-0 w-12 h-12 rounded-full flex items-center justify-center
                            @if($badge->color === 'gold') bg-yellow-200 text-yellow-700
                            @elseif($badge->color === 'blue') bg-blue-200 text-blue-700
                            @elseif($badge->color === 'green') bg-green-200 text-green-700
                            @elseif($badge->color === 'purple') bg-purple-200 text-purple-700
                            @elseif($badge->color === 'orange') bg-orange-200 text-orange-700
                            @elseif($badge->color === 'teal') bg-teal-200 text-teal-700
                            @else bg-gray-200 text-gray-700
                            @endif">
                            @if($badge->icon === 'star')
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @elseif($badge->icon === 'bolt')
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($badge->icon === 'check-badge')
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($badge->icon === 'home')
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                </svg>
                            @elseif($badge->icon === 'pencil')
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                </svg>
                            @elseif($badge->icon === 'user-check')
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">{{ $badge->name }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $badge->description }}</p>
                            <p class="text-xs text-gray-500 mt-2">
                                Obtenu le {{ $badge->earned_at->translatedFormat('d F Y') }}
                                @if($badge->expires_at)
                                    · Expire le {{ $badge->expires_at->translatedFormat('d F Y') }}
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Badges à obtenir -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Tous les badges</h2>
        
        <div class="space-y-6">
            @foreach($allBadgeTypes as $type => $info)
                @php
                    $hasBadge = $activeBadges->where('badge_type', $type)->first();
                    $progress = $badgeProgress[$type] ?? null;
                @endphp
                
                <div class="flex items-start gap-4 p-4 rounded-lg border {{ $hasBadge ? 'border-orange-200 bg-orange-50' : 'border-gray-200 bg-gray-50' }}">
                    <div class="shrink-0 w-12 h-12 rounded-full flex items-center justify-center {{ $hasBadge ? 'bg-orange-200 text-orange-600' : 'bg-gray-200 text-gray-400' }}">
                        @if($info['icon'] === 'star')
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @elseif($info['icon'] === 'bolt')
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                            </svg>
                        @elseif($info['icon'] === 'check-badge')
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @elseif($info['icon'] === 'home')
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                            </svg>
                        @elseif($info['icon'] === 'pencil')
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                            </svg>
                        @elseif($info['icon'] === 'user-check')
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold {{ $hasBadge ? 'text-orange-600' : 'text-gray-900' }}">{{ $info['name'] }}</h3>
                            @if($hasBadge)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-200 text-orange-700">
                                    Obtenu
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ $info['description'] }}</p>
                        
                        @if($progress && !$hasBadge)
                            <div class="mt-3 space-y-2">
                                @foreach($progress as $criterion => $data)
                                    <div>
                                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                                            <span>{{ ucfirst($criterion) }}</span>
                                            <span>{{ $data['current'] }} / {{ $data['required'] }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-orange-500 h-1.5 rounded-full transition-all" style="width: {{ $data['percentage'] }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @auth
        @if(Auth::id() === $user->id)
            <div class="mt-6 text-center">
                <form action="{{ route('profile.badges.refresh') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Actualiser mes badges
                    </button>
                </form>
            </div>
        @endif
    @endauth
</div>
@endsection
