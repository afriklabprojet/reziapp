@extends('layouts.app')

@section('title', 'Accepter l\'invitation de co-hôte')

@section('content')
<div class="max-w-lg mx-auto px-4 py-12">
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="p-6 bg-primary-50 border-b text-center">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Invitation de co-hôte</h1>
            <p class="text-gray-600 mt-1">Vous êtes invité(e) à devenir co-hôte</p>
        </div>

        <div class="p-6 space-y-4">
            <div>
                <span class="text-sm text-gray-500">Résidence</span>
                <p class="font-semibold text-gray-900">{{ $cohost->residence->name ?? '—' }}</p>
            </div>

            <div>
                <span class="text-sm text-gray-500">Invité par</span>
                <p class="font-medium text-gray-900">{{ $cohost->owner->name ?? '—' }}</p>
            </div>

            <div>
                <span class="text-sm text-gray-500">Votre rôle</span>
                <p class="font-medium text-gray-900">{{ $cohost->name }}</p>
            </div>

            @if($cohost->expires_at)
            <div>
                <span class="text-sm text-gray-500">Expire le</span>
                <p class="font-medium text-gray-900">{{ $cohost->expires_at->format('d/m/Y à H:i') }}</p>
            </div>
            @endif

            {{-- Permissions accordées --}}
            <div class="pt-4 border-t">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Permissions accordées</h3>
                <div class="grid grid-cols-2 gap-2">
                    @php
                        $permissions = [
                            'can_respond_messages' => 'Répondre aux messages',
                            'can_manage_calendar' => 'Gérer le calendrier',
                            'can_manage_pricing' => 'Gérer les tarifs',
                            'can_edit_listing' => 'Modifier l\'annonce',
                            'can_accept_bookings' => 'Accepter les réservations',
                            'can_view_earnings' => 'Voir les revenus',
                        ];
                    @endphp
                    @foreach($permissions as $key => $label)
                    <div class="flex items-center gap-2 text-sm">
                        @if($cohost->$key)
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-gray-700">{{ $label }}</span>
                        @else
                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span class="text-gray-400">{{ $label }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            @if($cohost->commission_percent)
            <div class="pt-4 border-t">
                <span class="text-sm text-gray-500">Commission</span>
                <p class="font-bold text-primary-600 text-lg">{{ $cohost->commission_percent }}%</p>
            </div>
            @endif
        </div>

        <div class="p-6 border-t bg-gray-50">
            @auth
            <form action="{{ route('cohosts.accept.process', $cohost->invitation_token) }}" method="POST" class="flex items-center gap-3">
                @csrf
                <button type="submit" class="btn-primary flex-1">Accepter l'invitation</button>
                <a href="{{ url('/') }}" class="btn-secondary">Refuser</a>
            </form>
            @else
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-3">Connectez-vous pour accepter cette invitation</p>
                <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="btn-primary">Se connecter</a>
                <a href="{{ route('register') }}" class="btn-secondary ml-2">Créer un compte</a>
            </div>
            @endauth
        </div>
    </div>
</div>
@endsection
