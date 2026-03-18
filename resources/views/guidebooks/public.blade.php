@extends('layouts.guest')

@section('title', $guidebook->title . ' — Guide')

@section('content')
<div class="min-h-screen bg-gray-50">
    {{-- Header --}}
    @if($guidebook->cover_image)
    <div class="h-64 md:h-80 bg-cover bg-center relative" style="background-image: url('{{ Storage::url($guidebook->cover_image) }}')">
        <div class="absolute inset-0 bg-linear-to-b from-black/30 to-black/60"></div>
        <div class="absolute bottom-0 left-0 right-0 p-6 md:p-10">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold text-white">{{ $guidebook->title }}</h1>
                <p class="text-lg text-white/80 mt-2">{{ $guidebook->residence?->name }}</p>
            </div>
        </div>
    </div>
    @else
    <div class="bg-gray-900 py-12 md:py-16">
        <div class="max-w-4xl mx-auto px-6">
            <h1 class="text-3xl md:text-4xl font-bold text-white">{{ $guidebook->title }}</h1>
            <p class="text-lg text-white/80 mt-2">{{ $guidebook->residence?->name }}</p>
        </div>
    </div>
    @endif

    {{-- Content --}}
    <div class="max-w-4xl mx-auto px-6 py-10">
        {{-- Welcome Message --}}
        @if($guidebook->welcome_message)
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-8">
            <h2 class="text-lg font-bold text-gray-900 mb-3">👋 Bienvenue</h2>
            <div class="prose prose-sm max-w-none text-gray-600">
                {!! nl2br(e($guidebook->welcome_message)) !!}
            </div>
        </div>
        @endif

        {{-- Essential Info --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            @if($guidebook->wifi_name)
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">WiFi</p>
                        <p class="font-semibold text-gray-900">{{ $guidebook->wifi_name }}</p>
                        <p class="text-sm text-gray-600 font-mono">{{ $guidebook->wifi_password }}</p>
                    </div>
                </div>
            </div>
            @endif
            @if($guidebook->check_in_instructions)
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">Check-in</p>
                        <p class="text-sm text-gray-600">{{ $guidebook->check_in_instructions }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Sections --}}
        @foreach($guidebook->sections as $section)
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">
                @if($section->icon) <span class="mr-2">{{ $section->icon }}</span> @endif
                {{ $section->title }}
            </h2>
            <div class="prose prose-sm max-w-none text-gray-600">
                {!! nl2br(e($section->content)) !!}
            </div>
        </div>
        @endforeach

        {{-- Recommendations --}}
        @if($guidebook->recommendations->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">🏪 Recommandations locales</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($guidebook->recommendations as $rec)
                <div class="border border-gray-100 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">{{ $rec->emoji ?? '📍' }}</span>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $rec->name }}</p>
                            <p class="text-xs text-gray-500 uppercase">{{ $rec->category }}</p>
                            @if($rec->description)
                            <p class="text-sm text-gray-600 mt-1">{{ $rec->description }}</p>
                            @endif
                            @if($rec->address)
                            <p class="text-xs text-gray-400 mt-1">📍 {{ $rec->address }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- House Rules --}}
        @if($guidebook->house_rules)
        <div class="bg-amber-50 rounded-2xl border border-amber-200 p-6 mb-6">
            <h2 class="text-lg font-bold text-amber-900 mb-3">📋 Règlement</h2>
            <div class="prose prose-sm max-w-none text-amber-800">
                {!! nl2br(e($guidebook->house_rules)) !!}
            </div>
        </div>
        @endif

        {{-- Emergency Contact --}}
        @if($guidebook->emergency_contact)
        <div class="bg-red-50 rounded-2xl border border-red-200 p-6">
            <h2 class="text-lg font-bold text-red-900 mb-2">🆘 Contact d'urgence</h2>
            <p class="text-lg font-mono font-bold text-red-800">{{ $guidebook->emergency_contact }}</p>
        </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="border-t border-gray-200 py-6 text-center text-sm text-gray-400">
        Guide créé avec <a href="{{ url('/') }}" class="text-gray-600 hover:underline">REZI</a>
    </div>
</div>
@endsection
