@extends('layouts.app')

@section('title', 'Désabonnement newsletter - REZI')

@section('content')
    <div class="min-h-[60vh] flex items-center justify-center px-4 py-16">
        <div class="max-w-md w-full text-center">
            @if ($success)
                {{-- Succès --}}
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $message }}</h1>
                @if (isset($email))
                    <p class="text-gray-500 mb-6">L'adresse <strong>{{ $email }}</strong> ne recevra plus nos emails.
                    </p>
                @endif

                @if (isset($token))
                    <p class="text-sm text-gray-400 mb-4">Désabonné par erreur ?</p>
                    <form action="{{ route('newsletter.resubscribe') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button type="submit"
                            class="px-5 py-2.5 bg-orange-500 text-white font-medium rounded-xl hover:bg-orange-600 transition-colors">
                            Se réabonner
                        </button>
                    </form>
                @endif
            @else
                {{-- Erreur --}}
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $message }}</h1>
                <p class="text-gray-500 mb-6">Ce lien est peut-être expiré ou incorrect.</p>
            @endif

            <div class="mt-8">
                <a href="{{ route('home') }}" class="text-sm text-orange-500 hover:text-orange-600 font-medium">
                    ← Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
@endsection
