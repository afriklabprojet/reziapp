@extends('layouts.client')

@section('title', $conversation->getOtherParticipant(auth()->user())->name . ' — Messages')

@section('client-content')
    @php
        $other = $conversation->getOtherParticipant(auth()->user());
        $lastMsg = $messages->last();
    @endphp

    <div x-data="chatShow({
        conversationId: {{ $conversation->id }},
        lastMessageId: {{ $lastMsg?->id ?? 0 }},
        firstMessageId: {{ $messages->first()?->id ?? 0 }},
        currentUserId: {{ auth()->id() }},
        isPinned: {{ $conversation->isPinned() ? 'true' : 'false' }},
        isMuted: {{ $conversation->isMuted() ? 'true' : 'false' }},
        isArchived: {{ $conversation->isArchived() ? 'true' : 'false' }},
        hasMoreMessages: {{ ($hasMoreMessages ?? false) ? 'true' : 'false' }},
        chatIndexUrl: '{{ route('chat.index') }}',
        ownAvatarUrl: '{{ auth()->user()->getAvatarUrl() }}',
        ownInitial: '{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}',
        otherAvatarUrl: '{{ $other->getAvatarUrl() }}',
        otherInitial: '{{ strtoupper(mb_substr($other->name, 0, 1)) }}',
        themeColor: '{{ $conversation->theme_color ?? 'orange' }}',
    })"
        @keydown.escape="showTemplates = false; showDocuments = false; showQuickReplies = false; showEmojiPicker = false; showGifPicker = false; showSearchPanel = false; showThemePicker = false"
        @keydown.ctrl.f.prevent="showSearchPanel = !showSearchPanel"
        @keydown.meta.f.prevent="showSearchPanel = !showSearchPanel"
        class="flex h-[calc(100dvh-8rem)] md:h-[calc(100vh-10rem)] min-h-120 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

        {{-- ==================== SIDEBAR (desktop) ==================== --}}
        <div class="hidden lg:flex flex-col w-80 border-r border-gray-100 bg-gray-50/50">
            {{-- Sidebar header --}}
            <div class="flex items-center gap-2 px-4 py-3.5 border-b border-gray-100">
                <a href="{{ route('chat.index') }}"
                    class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 hover:text-[#CC5A00] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                    </svg>
                    Messages
                </a>
            </div>

            {{-- Conversation list --}}
            <div class="flex-1 overflow-y-auto">
                @foreach ($conversations as $conv)
                    @php
                        $convOther = $conv->getOtherParticipant(auth()->user());
                        $convUnread = $conv->unreadMessagesFor(auth()->user());
                        $convLast = $conv->lastMessage;
                        $isActive = $conv->id === $conversation->id;
                    @endphp
                    <a href="{{ route('chat.show', $conv) }}"
                        class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 transition-all
                          {{ $isActive ? 'bg-[#FFF4EB] border-l-2 border-l-orange-500' : 'hover:bg-gray-100/60' }}">
                        <div class="relative shrink-0">
                            @if ($convOther->avatar)
                                <img src="{{ $convOther->getAvatarUrl() }}" class="w-10 h-10 rounded-full object-cover" alt="Avatar">
                            @else
                                <div
                                    class="w-10 h-10 rounded-full bg-linear-to-br from-[#FF8A1F] to-[#CC5A00] flex items-center justify-center text-white font-bold text-xs">
                                    {{ strtoupper(mb_substr($convOther->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-sm font-semibold truncate {{ $isActive ? 'text-[#A34700]' : ($convUnread > 0 ? 'text-gray-950' : 'text-gray-800') }}">
                                    {{ $convOther->name }}
                                </span>
                                <span
                                    class="text-[10px] shrink-0 ml-2 {{ $convUnread > 0 ? 'text-[#CC5A00] font-semibold' : 'text-gray-400' }}">
                                    {{ $conv->last_message_at?->diffForHumans(short: true) ?? '' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <p
                                    class="text-xs truncate {{ $convUnread > 0 ? 'text-gray-700 font-medium' : 'text-gray-500' }}">
                                    {{ Str::limit($convLast?->content ?? 'Nouvelle conversation', 35) }}
                                </p>
                                @if ($convUnread > 0)
                                    <span
                                        class="shrink-0 inline-flex items-center justify-center min-w-4 h-4 px-1 text-[9px] font-bold text-white bg-[#F16A00] rounded-full">
                                        {{ $convUnread > 99 ? '99+' : $convUnread }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ==================== MAIN CHAT ==================== --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Chat header --}}
            <div class="flex items-center gap-3 px-4 sm:px-5 py-3 border-b border-gray-100 bg-white">
                {{-- Back (mobile) --}}
                <a href="{{ route('chat.index') }}"
                    class="lg:hidden shrink-0 -ml-1 p-1 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </a>

                {{-- User info --}}
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    @if ($other->avatar)
                        <img src="{{ $other->getAvatarUrl() }}" alt=""
                            class="w-10 h-10 rounded-full object-cover ring-2 ring-orange-100">
                    @else
                        <div
                            class="w-10 h-10 rounded-full bg-linear-to-br from-[#FF8A1F] to-[#CC5A00] flex items-center justify-center text-white font-bold text-sm ring-2 ring-orange-100">
                            {{ strtoupper(mb_substr($other->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-gray-900 truncate">{{ $other->name }}</h2>
                        @if (isset($otherLastSeen) && $otherLastSeen)
                            @if ($otherLastSeen->diffInMinutes(now()) < 5)
                                <p class="text-[11px] text-green-500 font-medium flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                    En ligne
                                </p>
                            @else
                                <p class="text-[11px] text-gray-400">Vu {{ $otherLastSeen->diffForHumans() }}</p>
                            @endif
                        @elseif ($conversation->residence)
                            <p class="text-[11px] text-gray-400 truncate flex items-center gap-1">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                                {{ Str::limit($conversation->residence->name ?? ($conversation->residence->title ?? ''), 40) }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-0.5 shrink-0" x-data="{ showMenu: false }">
                    {{-- Search in conversation --}}
                    <button @click="showSearchPanel = !showSearchPanel"
                        class="p-2 rounded-lg transition-colors"
                        :class="showSearchPanel ? 'text-[#CC5A00] bg-[#FFF4EB]' : 'text-gray-400 hover:text-[#F16A00] hover:bg-[#FFF4EB]'"
                        title="Rechercher (Ctrl+F)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </button>

                    {{-- Documents --}}
                    @if ($conversation->sharedDocuments->count())
                        <button @click="showDocuments = !showDocuments"
                            class="p-2 text-gray-400 hover:text-[#F16A00] hover:bg-[#FFF4EB] rounded-lg transition-colors"
                            title="Documents partagés">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </button>
                    @endif

                    {{-- Menu --}}
                    <div class="relative">
                        <button @click="showMenu = !showMenu"
                            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                            </svg>
                        </button>
                        <div x-show="showMenu" @click.outside="showMenu = false" x-transition
                            class="absolute right-0 top-full mt-1 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1.5 z-50">
                            <button @click="togglePin(); showMenu = false"
                                class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                                </svg>
                                <span x-text="isPinned ? 'Désépingler' : 'Épingler'"></span>
                            </button>
                            <button @click="toggleMute(); showMenu = false"
                                class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17.25 9.75 19.5 12m0 0 2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-6 4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" />
                                </svg>
                                <span x-text="isMuted ? 'Réactiver' : 'Désactiver les notif.'"></span>
                            </button>
                            {{-- Theme picker --}}
                            <div class="border-t border-gray-100 my-1"></div>
                            <div class="px-3.5 py-2">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Thème</p>
                                <div class="flex items-center gap-1.5">
                                    @foreach (\App\Models\Conversation::THEME_COLORS as $colorName => $colorData)
                                        <button @click="changeTheme('{{ $colorName }}'); showMenu = false"
                                            class="w-6 h-6 rounded-full border-2 transition-transform hover:scale-110"
                                            :class="themeColor === '{{ $colorName }}' ? 'border-gray-800 scale-110' : 'border-transparent'"
                                            style="background-color: {{ $colorData['hex'] }}"
                                            title="{{ ucfirst($colorName) }}"></button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="border-t border-gray-100 my-1"></div>
                            <button @click="archive(); showMenu = false"
                                class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                </svg>
                                <span x-text="isArchived ? 'Désarchiver' : 'Archiver'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========= Search in conversation panel ========= --}}
            <div x-show="showSearchPanel" x-transition
                class="border-b border-gray-100 bg-white px-4 sm:px-5 py-2.5">
                <div class="flex items-center gap-2">
                    <div class="flex-1 relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input id="conversation-search" type="text" x-model="searchQuery" @input.debounce.400ms="searchInConversation()"
                            @keydown.escape="showSearchPanel = false"
                            placeholder="Rechercher dans la conversation…"
                            aria-label="Rechercher dans la conversation"
                            class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#FFD0A3] focus:border-[#FF8A1F] focus:bg-white transition-all">
                    </div>
                    <span x-show="searchResults.length > 0" class="text-xs text-gray-400 shrink-0" x-text="searchResults.length + ' résultat(s)'"></span>
                    <button @click="showSearchPanel = false; searchQuery = ''; searchResults = []"
                        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div x-show="searchResults.length > 0" class="mt-2 max-h-40 overflow-y-auto space-y-1">
                    <template x-for="result in searchResults" :key="result.id">
                        <button @click="scrollToMessage(result.id)" class="w-full text-left flex items-center gap-2 px-3 py-1.5 text-sm rounded-lg hover:bg-[#FFF4EB] transition-colors">
                            <span class="text-[10px] text-gray-400 shrink-0" x-text="result.date"></span>
                            <span class="truncate" :class="result.is_own ? 'text-[#CC5A00]' : 'text-gray-700'" x-text="result.content"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- ========= Messages area ========= --}}
            <div id="messages-container" x-ref="messagesContainer" class="flex-1 overflow-y-auto px-4 sm:px-6 py-4"
                style="background-color: #f8f7f4; background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22><circle cx=%2230%22 cy=%2230%22 r=%221%22 fill=%22%23e5e2db%22 fill-opacity=%220.3%22/></svg>');">

                {{-- Load more sentinel --}}
                <div x-show="loadingMore" class="flex justify-center py-3">
                    <span class="inline-block w-5 h-5 border-2 border-gray-300 border-t-orange-500 rounded-full animate-spin"></span>
                </div>
                <div x-show="hasMoreMessages && !loadingMore" class="flex justify-center py-2">
                    <button @click="loadOlderMessages()" class="text-xs text-[#F16A00] hover:text-[#CC5A00] font-medium">Charger les messages précédents</button>
                </div>

                {{-- Date grouped messages --}}
                <div class="space-y-4 messages-list">
                    @php $prevDate = null; @endphp
                    @foreach ($messages as $message)
                        @php $currentDate = $message->created_at->format('Y-m-d'); @endphp
                        @if ($currentDate !== $prevDate)
                            <div class="flex justify-center my-4">
                                <span
                                    class="px-3 py-1 text-[11px] font-medium text-gray-500 bg-white/90 rounded-full shadow-sm border border-gray-100/60 backdrop-blur-sm">
                                    @if ($message->created_at->isToday())
                                        Aujourd'hui
                                    @elseif($message->created_at->isYesterday())
                                        Hier
                                    @else
                                        {{ $message->created_at->translatedFormat('j F Y') }}
                                    @endif
                                </span>
                            </div>
                            @php $prevDate = $currentDate; @endphp
                        @endif

                        @include('chat.partials.message', ['message' => $message])
                    @endforeach
                </div>

                {{-- Typing indicator --}}
                <div x-show="isTyping" x-transition class="flex items-center gap-2 mt-3 pl-9">
                    <div class="px-3.5 py-2.5 bg-white border border-gray-100 rounded-2xl rounded-bl-md shadow-sm">
                        <div class="flex gap-1">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"
                                style="animation-delay:0ms"></span>
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"
                                style="animation-delay:150ms"></span>
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"
                                style="animation-delay:300ms"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========= Documents panel ========= --}}
            @if ($conversation->sharedDocuments->count())
                <div x-show="showDocuments" x-transition
                    class="border-t border-gray-100 bg-linear-to-b from-gray-50/60 to-white px-4 sm:px-5 py-3 max-h-44 overflow-y-auto">
                    <div class="flex items-center justify-between mb-2.5">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-blue-100 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor"
                                    stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-gray-700">
                                Documents partagés
                                <span
                                    class="ml-1 text-xs font-medium text-gray-400">({{ $conversation->sharedDocuments->count() }})</span>
                            </h4>
                        </div>
                        <button @click="showDocuments = false"
                            class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($conversation->sharedDocuments as $doc)
                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                class="group flex items-center gap-2.5 p-2.5 rounded-xl hover:bg-[#FFF4EB]/60 bg-white border border-gray-100 hover:border-[#FFD0A3] shadow-sm transition-all">
                                <div
                                    class="w-9 h-9 rounded-lg bg-[#FFE7D1] group-hover:bg-[#FFD0A3] flex items-center justify-center shrink-0 transition-colors">
                                    <svg class="w-4 h-4 text-[#CC5A00]" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm font-medium text-gray-700 group-hover:text-[#A34700] truncate transition-colors">
                                        {{ $doc->name ?? ($doc->title ?? 'Document') }}</p>
                                    <p class="text-xs text-gray-400">{{ $doc->created_at->diffForHumans() }}</p>
                                </div>
                                <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-[#FF8A1F] shrink-0 transition-colors"
                                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ========= Templates panel ========= --}}
            <div x-show="showTemplates" x-transition
                class="border-t border-gray-100 bg-linear-to-b from-[#FFF4EB]/40 to-white px-4 sm:px-5 py-3 max-h-40 overflow-y-auto">
                <div class="flex items-center justify-between mb-2.5">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md bg-[#FFE7D1] flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-[#CC5A00]" fill="none" stroke="currentColor"
                                stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-gray-700">Réponses rapides</h4>
                    </div>
                    <button @click="showTemplates = false"
                        class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($templates as $template)
                        <button @click="useTemplate({{ $template->id }}, @js($template->content))"
                            class="group inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-600 bg-white hover:bg-[#FFF4EB] hover:text-[#A34700] rounded-xl border border-gray-200/80 hover:border-[#FFD0A3] shadow-sm hover:shadow transition-all active:scale-95">
                            <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-[#F16A00] transition-colors shrink-0"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                            </svg>
                            {{ Str::limit($template->name ?? $template->content, 28) }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- ========= Quick Replies (Auto-replies manuelles) ========= --}}
            @if (isset($quickReplies) && $quickReplies->count())
                <div x-show="showQuickReplies" x-transition
                    class="border-t border-gray-100 bg-linear-to-b from-blue-50/40 to-white px-4 sm:px-5 py-3 max-h-44 overflow-y-auto">
                    <div class="flex items-center justify-between mb-2.5">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-blue-100 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor"
                                    stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-gray-700">Réponses auto</h4>
                        </div>
                        <button @click="showQuickReplies = false"
                            class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-1.5">
                        @foreach ($quickReplies as $qr)
                            <button @click="useQuickReply({{ $qr->id }}, @js(
    $qr->formatMessage([
        'guest_name' => $other->name ?? 'Cher client',
        'residence_name' => $conversation->residence?->name ?? 'notre résidence',
        'owner_name' => auth()->user()->name ?? 'Le propriétaire',
        'checkin_time' => '14h00',
        'checkout_time' => '11h00',
    ]),
))"
                                class="w-full text-left group flex items-start gap-3 px-3 py-2.5 text-sm text-gray-600 bg-white hover:bg-blue-50 hover:text-blue-700 rounded-xl border border-gray-200/80 hover:border-blue-300 shadow-sm hover:shadow transition-all">
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-500 transition-colors shrink-0 mt-0.5"
                                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                                </svg>
                                <div class="min-w-0">
                                    <span class="font-semibold text-xs text-gray-900 block">{{ $qr->name }}</span>
                                    <span
                                        class="text-xs text-gray-400 line-clamp-1">{{ Str::limit($qr->message, 60) }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ========= Reply preview ========= --}}
            <div x-show="replyTo" x-transition.scale.y.origin.bottom
                class="border-t border-[#FFD0A3]/60 bg-linear-to-r from-[#FFF4EB] to-amber-50/50 px-4 sm:px-5 py-2.5">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-8 bg-linear-to-b from-[#FF8A1F] to-[#F16A00] rounded-full shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#F16A00] shrink-0" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                            </svg>
                            <p class="text-xs font-bold text-[#CC5A00] uppercase tracking-wider">Réponse à</p>
                        </div>
                        <p class="text-sm text-gray-600 truncate mt-0.5" x-text="replyToContent"></p>
                    </div>
                    <button @click="cancelReply()"
                        class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-white/80 transition-all active:scale-90">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- ========= Input bar ========= --}}
            <div class="border-t border-gray-100 bg-linear-to-t from-white via-white to-gray-50/50 px-3 sm:px-4 py-3">
                {{-- GIF Picker Panel --}}
                <div x-show="showGifPicker" x-cloak x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
                    @click.outside="showGifPicker = false"
                    class="mb-2 bg-white border border-gray-200 rounded-2xl shadow-lg overflow-hidden">
                    <div class="flex items-center gap-2 px-3 py-2.5 border-b border-gray-100">
                        <svg class="w-4 h-4 text-purple-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                        <input id="gif-search" type="text" x-model="gifSearchQuery" @input.debounce.500ms="searchGifs()" placeholder="Rechercher un GIF…"
                            aria-label="Rechercher un GIF"
                            class="flex-1 text-sm bg-gray-50 border-none rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-purple-300 focus:bg-white placeholder:text-gray-400">
                    </div>
                    <div class="p-2 max-h-56 overflow-y-auto">
                        <div x-show="gifLoading" class="flex justify-center py-6">
                            <span class="inline-block w-5 h-5 border-2 border-gray-300 border-t-purple-500 rounded-full animate-spin"></span>
                        </div>
                        <div x-show="!gifLoading && gifResults.length === 0" class="text-center py-6 text-sm text-gray-400">
                            <span x-text="gifSearchQuery ? 'Aucun GIF trouvé' : 'Tapez pour rechercher des GIFs'"></span>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-1.5">
                            <template x-for="gif in gifResults" :key="gif.id">
                                <button @click="sendGif(gif)" class="relative rounded-xl overflow-hidden aspect-square hover:opacity-80 hover:scale-105 transition-all">
                                    <img :src="gif.preview" :alt="'GIF'" class="w-full h-full object-cover" loading="lazy">
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Voice Recording Indicator --}}
                <div x-show="isRecording" x-transition class="mb-2 flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-2xl">
                    <span class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                    <span class="text-sm font-medium text-red-700 flex-1">Enregistrement en cours…</span>
                    <span class="text-xs text-red-500 tabular-nums" x-text="recordingDuration"></span>
                    <button @click="cancelRecording()" class="p-1 text-red-400 hover:text-red-600 rounded-lg hover:bg-red-100 transition-colors" title="Annuler">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                    <button @click="stopRecording()" class="p-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors" title="Envoyer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                    </button>
                </div>

                {{-- Emoji Picker Panel --}}
                <div x-show="showEmojiPicker" x-cloak x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
                    @click.outside="showEmojiPicker = false"
                    class="mb-2 bg-white border border-gray-200 rounded-2xl shadow-lg overflow-hidden">
                    {{-- Emoji category tabs --}}
                    <div class="flex items-center gap-1 px-3 py-2 border-b border-gray-100 bg-gray-50/50">
                        <template x-for="(cat, idx) in emojiCategories" :key="idx">
                            <button @click="emojiTab = idx" class="p-1.5 rounded-lg text-lg transition-all"
                                :class="emojiTab === idx ? 'bg-[#FFE7D1] scale-110' : 'hover:bg-gray-100'"
                                :title="cat.name" x-text="cat.icon">
                            </button>
                        </template>
                    </div>
                    {{-- Emoji grid --}}
                    <div class="p-2 max-h-48 overflow-y-auto scrollbar-thin">
                        <div class="grid grid-cols-8 sm:grid-cols-10 gap-0.5">
                            <template x-for="emoji in emojiCategories[emojiTab]?.emojis || []" :key="emoji">
                                <button @click="insertEmoji(emoji)"
                                    class="w-9 h-9 flex items-center justify-center text-xl rounded-lg hover:bg-[#FFF4EB] hover:scale-125 active:scale-95 transition-all cursor-pointer"
                                    x-text="emoji">
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    {{-- Left actions group --}}
                    <div class="flex items-center shrink-0 pb-0.5">
                        {{-- Emoji --}}
                        <button
                            @click="showEmojiPicker = !showEmojiPicker; showTemplates = false; showQuickReplies = false"
                            class="p-2 rounded-xl transition-all active:scale-90"
                            :class="showEmojiPicker ? 'text-[#CC5A00] bg-[#FFF4EB]' :
                                'text-gray-400 hover:text-[#F16A00] hover:bg-[#FFF4EB]'"
                            title="Emojis">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                            </svg>
                        </button>

                        {{-- Attach --}}
                        <label
                            class="group relative p-2 text-gray-400 hover:text-[#F16A00] rounded-xl cursor-pointer transition-all hover:bg-[#FFF4EB] active:scale-90"
                            title="Joindre un fichier">
                            <svg class="w-5 h-5 transition-transform group-hover:rotate-[-15deg]" fill="none"
                                stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                            </svg>
                            <input id="file-upload-hidden" type="file" class="hidden" @change="uploadFile($event)"
                                   accept="image/*,.pdf,.doc,.docx,.txt"
                                   aria-label="Sélectionner un fichier à envoyer">
                                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
                        </label>

                        {{-- GIF --}}
                        <button @click="showGifPicker = !showGifPicker; showEmojiPicker = false; showTemplates = false; showQuickReplies = false"
                            class="p-2 rounded-xl transition-all active:scale-90"
                            :class="showGifPicker ? 'text-purple-600 bg-purple-50' : 'text-gray-400 hover:text-purple-500 hover:bg-purple-50'"
                            title="GIF">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M11.5 9H13v6h-1.5zM9 9H6c-.6 0-1 .5-1 1v4c0 .5.4 1 1 1h3c.6 0 1-.5 1-1v-2H8.5v1.5h-2v-3H10V10c0-.5-.4-1-1-1zm10 1.5V9h-4.5v6H16v-2h2v-1.5h-2v-1z"/></svg>
                        </button>

                        {{-- Voice --}}
                        <button @click="startRecording()"
                            x-show="!isRecording"
                            class="p-2 text-gray-400 hover:text-red-500 rounded-xl hover:bg-red-50 transition-all active:scale-90"
                            title="Message vocal">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                            </svg>
                        </button>

                        {{-- Templates --}}
                        @if ($templates->count())
                            <button @click="showTemplates = !showTemplates; showQuickReplies = false"
                                class="p-2 rounded-xl transition-all active:scale-90"
                                :class="showTemplates ? 'text-[#CC5A00] bg-[#FFF4EB]' :
                                    'text-gray-400 hover:text-[#F16A00] hover:bg-[#FFF4EB]'"
                                title="Réponses rapides">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                                </svg>
                            </button>
                        @endif

                        {{-- Quick Replies (Auto-replies manuelles) --}}
                        @if (isset($quickReplies) && $quickReplies->count())
                            <button @click="showQuickReplies = !showQuickReplies; showTemplates = false"
                                class="p-2 rounded-xl transition-all active:scale-90"
                                :class="showQuickReplies ? 'text-blue-600 bg-blue-50' :
                                    'text-gray-400 hover:text-blue-500 hover:bg-blue-50'"
                                title="Réponses automatiques">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- Message input card --}}
                    <div
                        class="flex-1 flex items-end bg-white border border-gray-200 rounded-2xl shadow-sm focus-within:border-[#FF8A1F] focus-within:ring-2 focus-within:ring-[#F16A00]/10 focus-within:shadow-md transition-all overflow-hidden">
                        <label for="message-input" class="sr-only">Votre message</label>
                        <textarea id="message-input" x-ref="messageInput" x-model="newMessage" @input="autoResize($el); emitTyping()"
                            @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()" placeholder="Votre message…" rows="1"
                            aria-label="Tapez votre message ici"
                            class="w-full px-4 py-3 text-base bg-transparent border-none resize-none placeholder:text-gray-400 focus:outline-none focus:ring-0"
                            style="max-height: 120px"></textarea>
                    </div>

                    {{-- Send button --}}
                    <div class="shrink-0 pb-0.5">
                        <button @click="sendMessage()" :disabled="!newMessage.trim() || sending"
                            class="relative p-2.5 rounded-xl transition-all duration-200"
                            :class="newMessage.trim() && !sending ?
                                'bg-linear-to-br from-[#F16A00] to-[#CC5A00] text-white shadow-md shadow-none hover:shadow-lg hover:shadow-none hover:from-[#CC5A00] hover:to-[#A34700] active:scale-90' :
                                'bg-gray-100 text-gray-300 cursor-not-allowed'">
                            <svg x-show="!sending" class="w-5 h-5 transition-transform"
                                :class="newMessage.trim() ? 'translate-x-0.5' : ''" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                            <svg x-show="sending" x-cloak class="w-5 h-5 animate-spin" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Context menu for edit/delete --}}
                <div x-show="contextMenuVisible" x-transition
                    class="fixed z-[100] bg-white rounded-xl shadow-xl border border-gray-200 py-1 w-44"
                    :style="`left: ${contextMenuX}px; top: ${contextMenuY}px`"
                    @click.outside="contextMenuVisible = false">
                    <button @click="startEditMessage()"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        Modifier
                    </button>
                    <button @click="confirmDeleteMessage()"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        Supprimer
                    </button>
                </div>

                {{-- Inline edit overlay --}}
                <div x-show="editingMessageId" x-transition class="border-t border-blue-200 bg-blue-50/60 px-4 py-2">
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <label class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Modification</label>
                            <input type="text" x-model="editContent" @keydown.enter="saveEditMessage()" @keydown.escape="cancelEditMessage()"
                                class="w-full px-3 py-1.5 text-sm bg-white border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-300 focus:border-blue-400">
                        </div>
                        <button @click="saveEditMessage()" class="p-1.5 text-blue-600 hover:bg-blue-100 rounded-lg">✓</button>
                        <button @click="cancelEditMessage()" class="p-1.5 text-gray-400 hover:bg-gray-100 rounded-lg">✕</button>
                    </div>
                </div>

                {{-- Helper text --}}
                <div class="flex items-center justify-center gap-4 mt-2.5 select-none">
                    <span class="text-xs text-gray-400 flex items-center gap-1.5">
                        <kbd
                            class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono font-semibold text-gray-400 bg-gray-50 border border-gray-200 rounded-md">↵</kbd>
                        Envoyer
                    </span>
                    <span class="w-1 h-1 bg-gray-200 rounded-full"></span>
                    <span class="text-xs text-gray-400 flex items-center gap-1.5">
                        <kbd
                            class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono font-semibold text-gray-400 bg-gray-50 border border-gray-200 rounded-md">⇧</kbd>
                        +
                        <kbd
                            class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono font-semibold text-gray-400 bg-gray-50 border border-gray-200 rounded-md">↵</kbd>
                        Saut de ligne
                    </span>
                </div>
            </div>
        </div>
    </div>
@endsection
