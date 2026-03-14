@extends('layouts.app')

@section('title', $metaTitle ?? $page->title)
@section('meta_description', $metaDescription ?? '')

@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            {{-- Header --}}
            <div class="bg-linear-to-r from-orange-500 to-orange-600 px-8 py-10 text-white">
                <h1 class="text-3xl md:text-4xl font-bold">{{ $page->title }}</h1>
                @if($page->updated_at)
                <p class="mt-2 text-orange-100 text-sm">
                    Dernière mise à jour : {{ $page->updated_at->format('d/m/Y') }}
                </p>
                @endif
            </div>

            {{-- Content --}}
            <div class="px-8 py-10">
                <div class="prose prose-lg max-w-none prose-headings:text-gray-900 prose-p:text-gray-700 prose-a:text-orange-600 prose-a:no-underline hover:prose-a:underline prose-strong:text-gray-900 prose-ul:text-gray-700 prose-ol:text-gray-700">
                    {!! $page->content !!}
                </div>
            </div>
        </div>

        {{-- Back to home --}}
        <div class="mt-8 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center text-orange-600 hover:text-orange-700 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour à l'accueil
            </a>
        </div>
    </div>
</div>
@endsection
