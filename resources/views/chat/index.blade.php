@extends('layouts.client')

@section('title', 'Messages — ReziApp')

@section('client-content')
    <div x-data="{ search: '', showNewChat: false, residenceSearch: '', selectedResidence: null, newChatMessage: '' }">
        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sm:p-6 mb-5">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-[#FFE7D1] flex items-center justify-center">
                        <svg class="w-5.5 h-5.5 text-[#CC5A00]" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Messages</h1>
                        @if ($stats['unread_messages'] > 0)
                            <p class="text-xs text-[#CC5A00] font-medium mt-0.5">
                                {{ $stats['unread_messages'] }} non lu{{ $stats['unread_messages'] > 1 ? 's' : '' }}
                            </p>
                        @else
                            <p class="text-xs text-gray-500 mt-0.5">{{ $stats['total_conversations'] }}
                                conversation{{ $stats['total_conversations'] > 1 ? 's' : '' }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    {{-- New conversation button --}}
                    @if ($residences->count())
                        <button @click="showNewChat = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold rounded-lg bg-[#F16A00] text-white hover:bg-[#CC5A00] transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span class="hidden sm:inline">Nouvelle</span>
                        </button>
                    @endif
                    {{-- Archive toggle --}}
                    <a href="{{ route('chat.index', ['archived' => !$archived]) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors
                          {{ $archived ? 'bg-[#F16A00] text-white hover:bg-[#CC5A00]' : 'text-gray-600 bg-gray-100 hover:bg-gray-200' }}">
                        @if ($archived)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                            </svg>
                            Retour
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                            </svg>
                            <span class="hidden sm:inline">Archives</span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- Search --}}
            <div class="relative mt-4">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8" />
                    <path stroke-linecap="round" d="m21 21-4.35-4.35" />
                </svg>
                <input type="text" x-model="search" placeholder="Rechercher par nom…"
                    class="w-full pl-10 pr-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#FF8A1F] transition-all">
            </div>
        </div>

        @if ($conversations->isEmpty())
            {{-- Empty --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 sm:p-16 text-center">
                <div class="w-20 h-20 bg-[#FFF4EB] rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-[#FF8A1F]" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">
                    {{ $archived ? 'Aucune archive' : 'Aucune conversation' }}
                </h3>
                <p class="text-sm text-gray-500 max-w-xs mx-auto mb-6">
                    {{ $archived ? 'Vos conversations archivées apparaîtront ici.' : 'Explorez les résidences et contactez un propriétaire pour démarrer une conversation.' }}
                </p>
                @if (!$archived)
                    <a href="{{ route('home') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#F16A00] text-white text-sm font-semibold rounded-lg hover:bg-[#CC5A00] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        Explorer les résidences
                    </a>
                @endif
            </div>
        @else
            {{-- Conversations --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                @foreach ($conversations as $conversation)
                    @php
                        $other = $conversation->getOtherParticipant(auth()->user());
                        $unread = $conversation->unreadMessagesFor(auth()->user());
                        $lastMsg = $conversation->lastMessage;
                    @endphp
                    <a href="{{ route('chat.show', $conversation) }}"
                        x-show="!search || '{{ strtolower(addslashes($other->name)) }}'.includes(search.toLowerCase())"
                        class="flex items-center gap-3.5 px-4 py-3.5 sm:px-5 border-b border-gray-50 last:border-b-0 hover:bg-[#FFF4EB]/40 active:bg-[#FFF4EB]/70 transition-colors relative group">

                        {{-- Avatar --}}
                        <div class="relative shrink-0">
                            @if ($other->avatar)
                                <img src="{{ $other->getAvatarUrl() }}" alt="{{ $other->name }}"
                                    class="w-12 h-12 rounded-full object-cover">
                            @else
                                <div
                                    class="w-12 h-12 rounded-full bg-linear-to-br from-[#FF8A1F] to-[#CC5A00] flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                    {{ strtoupper(mb_substr($other->name, 0, 1)) }}{{ strtoupper(mb_substr(explode(' ', $other->name)[1] ?? '', 0, 1)) }}
                                </div>
                            @endif
                            @if ($conversation->isPinned())
                                <div
                                    class="absolute -top-1 -right-1 w-4.5 h-4.5 bg-amber-400 rounded-full flex items-center justify-center ring-2 ring-white">
                                    <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-0.5">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span
                                        class="font-semibold text-[15px] truncate {{ $unread > 0 ? 'text-gray-950' : 'text-gray-900' }}">{{ $other->name }}</span>
                                    @if ($other->isOwner())
                                        <span
                                            class="shrink-0 px-1.5 py-px rounded text-[9px] font-bold uppercase tracking-wider bg-[#FFE7D1] text-[#CC5A00]">Hôte</span>
                                    @endif
                                </div>
                                <span
                                    class="text-[11px] ml-3 shrink-0 {{ $unread > 0 ? 'text-[#CC5A00] font-semibold' : 'text-gray-400' }}">
                                    {{ $conversation->last_message_at?->diffForHumans(short: true) ?? '' }}
                                </span>
                            </div>

                            @if ($conversation->residence)
                                <p class="text-[11px] text-gray-400 truncate mb-0.5 flex items-center gap-1">
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                    </svg>
                                    {{ Str::limit($conversation->residence->name ?? ($conversation->residence->title ?? ''), 45) }}
                                </p>
                            @endif

                            <div class="flex items-center justify-between gap-2">
                                @if ($lastMsg)
                                    <p
                                        class="text-sm truncate {{ $unread > 0 ? 'text-gray-800 font-medium' : 'text-gray-500' }}">
                                        @if ($lastMsg->sender_id === auth()->id())
                                            @if ($lastMsg->read_at)
                                                <svg class="w-3.5 h-3.5 text-blue-500 inline-block -mt-0.5 mr-0.5"
                                                    fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                            @else
                                                <svg class="w-3.5 h-3.5 text-gray-400 inline-block -mt-0.5 mr-0.5"
                                                    fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                            @endif
                                        @endif
                                        {{ Str::limit($lastMsg->content, 50) }}
                                    </p>
                                @else
                                    <p class="text-sm text-gray-400 italic">Nouvelle conversation</p>
                                @endif

                                <div class="flex items-center gap-1.5 shrink-0">
                                    @if ($conversation->isMuted())
                                        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17.25 9.75 19.5 12m0 0 2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-6 4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" />
                                        </svg>
                                    @endif
                                    @if ($unread > 0)
                                        <span
                                            class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 text-[10px] font-bold text-white bg-[#F16A00] rounded-full">
                                            {{ $unread > 99 ? '99+' : $unread }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if (method_exists($conversations, 'hasPages') && $conversations->hasPages())
                <div class="mt-5">{{ $conversations->links() }}</div>
            @endif
        @endif

        {{-- ==================== New Conversation Modal ==================== --}}
        @if ($residences->count())
            <div x-show="showNewChat" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="display:none">
                {{-- Overlay --}}
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                    @click="showNewChat = false; selectedResidence = null; residenceSearch = ''; newChatMessage = ''">
                </div>

                {{-- Modal --}}
                <div x-show="showNewChat" x-transition.scale.95 @click.stop
                    class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">

                    {{-- Modal header --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-[#FFE7D1] flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#CC5A00]" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-gray-900">Nouvelle conversation</h3>
                        </div>
                        <button
                            @click="showNewChat = false; selectedResidence = null; residenceSearch = ''; newChatMessage = ''"
                            class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('chat.start') }}">
                        @csrf
                        <input type="hidden" name="residence_id" :value="selectedResidence">

                        <div class="px-5 py-4 space-y-4">
                            {{-- Step 1: Choose residence --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    @if (auth()->user()->isOwner())
                                        Choisir une résidence
                                    @else
                                        Contacter le propriétaire d'une résidence
                                    @endif
                                </label>
                                <div class="relative mb-2">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <circle cx="11" cy="11" r="8" />
                                        <path stroke-linecap="round" d="m21 21-4.35-4.35" />
                                    </svg>
                                    <input type="text" x-model="residenceSearch"
                                        placeholder="Rechercher une résidence…"
                                        class="w-full pl-10 pr-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#FF8A1F] transition-all">
                                </div>
                                <div
                                    class="max-h-48 overflow-y-auto rounded-xl border border-gray-100 divide-y divide-gray-50">
                                    @foreach ($residences as $residence)
                                        <label
                                            x-show="!residenceSearch || '{{ strtolower(addslashes($residence->name)) }}'.includes(residenceSearch.toLowerCase()) || '{{ strtolower(addslashes($residence->commune ?? '')) }}'.includes(residenceSearch.toLowerCase())"
                                            class="flex items-center gap-3 px-3.5 py-3 cursor-pointer transition-colors"
                                            :class="selectedResidence == {{ $residence->id }} ? 'bg-[#FFF4EB]' :
                                                'hover:bg-gray-50'">
                                            <input type="radio" name="residence_radio" value="{{ $residence->id }}"
                                                @click="selectedResidence = {{ $residence->id }}"
                                                :checked="selectedResidence == {{ $residence->id }}"
                                                class="text-[#F16A00] focus:ring-[#F16A00]/30 border-gray-300">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate">
                                                    {{ $residence->name }}</p>
                                                <p class="text-[11px] text-gray-400 flex items-center gap-1">
                                                    @if ($residence->commune)
                                                        <svg class="w-3 h-3 shrink-0" fill="none"
                                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                        </svg>
                                                        {{ $residence->commune }}
                                                    @endif
                                                    @if (!auth()->user()->isOwner() && $residence->owner)
                                                        <span class="mx-1">·</span>
                                                        <span>{{ $residence->owner->name }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                            <div x-show="selectedResidence == {{ $residence->id }}" class="shrink-0">
                                                <svg class="w-5 h-5 text-[#F16A00]" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Step 2: Optional message --}}
                            <div x-show="selectedResidence" x-transition>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Message (optionnel)</label>
                                <textarea name="message" x-model="newChatMessage" rows="3"
                                    placeholder="Bonjour, je suis intéressé(e) par votre résidence…"
                                    class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl resize-none placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#FF8A1F] focus:bg-white transition-all"></textarea>
                            </div>
                        </div>

                        {{-- Modal footer --}}
                        <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                            <button type="button"
                                @click="showNewChat = false; selectedResidence = null; residenceSearch = ''; newChatMessage = ''"
                                class="px-4 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-colors">
                                Annuler
                            </button>
                            <button type="submit" :disabled="!selectedResidence"
                                class="px-5 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200"
                                :class="selectedResidence ? 'bg-[#F16A00] text-white hover:bg-[#CC5A00] shadow-sm' :
                                    'bg-gray-200 text-gray-400 cursor-not-allowed'">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                    </svg>
                                    Démarrer
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
