@extends('layouts.client', ['sidebarActive' => 'notifications'])

@section('title', 'Mes notifications - REZI')

@section('client-content')
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
                <p class="text-gray-600 mt-1">
                    @if ($unreadCount > 0)
                        {{ $unreadCount }} notification(s) non lue(s)
                    @else
                        Toutes vos notifications sont lues
                    @endif
                </p>
            </div>

            @if ($unreadCount > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm text-[#e00b41] hover:text-[#b5083a] font-medium">
                        Tout marquer comme lu
                    </button>
                </form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <!-- État vide -->
            <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune notification</h3>
                <p class="text-gray-500">Vous n'avez pas encore de notifications.</p>
            </div>
        @else
            <!-- Liste des notifications -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden divide-y divide-gray-100">
                @foreach ($notifications as $notification)
                    <div class="relative {{ is_null($notification->read_at) ? 'bg-[#fff0f3]' : '' }}">
                        <a href="{{ $notification->action_url ?? '#' }}"
                            class="flex items-start gap-4 p-4 hover:bg-gray-50 transition-colors"
                            @if ($notification->action_url) onclick="markAsRead('{{ $notification->id }}')" @endif>

                            <!-- Icône -->
                            <div class="shrink-0">
                                @php
                                    $iconBg = match ($notification->type) {
                                        'new_message' => 'bg-blue-100 text-blue-600',
                                        'new_review' => 'bg-yellow-100 text-yellow-600',
                                        'review_approved' => 'bg-green-100 text-green-600',
                                        'contact_received' => 'bg-purple-100 text-purple-600',
                                        'residence_approved' => 'bg-green-100 text-green-600',
                                        'residence_rejected' => 'bg-red-100 text-red-600',
                                        'new_favorite' => 'bg-red-100 text-red-600',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <div class="w-10 h-10 {{ $iconBg }} rounded-full flex items-center justify-center">
                                    @switch($notification->type)
                                        @case('new_message')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        @break

                                        @case('new_review')
                                        @case('review_approved')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                            </svg>
                                        @break

                                        @case('contact_received')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        @break

                                        @case('residence_approved')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        @break

                                        @case('residence_rejected')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        @break

                                        @case('new_favorite')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                        @break

                                        @default
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                    @endswitch
                                </div>
                            </div>

                            <!-- Contenu -->
                            <div class="flex-1 min-w-0">
                                <p
                                    class="font-medium text-gray-900 {{ is_null($notification->read_at) ? '' : 'text-gray-700' }}">
                                    {{ $notification->title }}
                                </p>
                                @if ($notification->body)
                                    <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $notification->body }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>

                            <!-- Indicateur non lu -->
                            @if (is_null($notification->read_at))
                                <div class="shrink-0">
                                    <span class="w-2 h-2 bg-[#e00b41] rounded-full block"></span>
                                </div>
                            @endif
                        </a>

                        <!-- Actions -->
                        <div class="absolute top-4 right-4 flex items-center gap-1">
                            @if (is_null($notification->read_at))
                                <form action="{{ route('notifications.read', $notification) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="p-1 text-gray-400 hover:text-[#e00b41] transition-colors"
                                        title="Marquer comme lu" aria-label="Marquer comme lu">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('notifications.destroy', $notification) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-gray-400 hover:text-red-600 transition-colors"
                                    title="Supprimer" aria-label="Supprimer la notification">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
