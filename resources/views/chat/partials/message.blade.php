@php
    $isOwn = $message->sender_id === auth()->id();
    $isSystem = $message->isSystem();
    $isAutoReply = $message->isAutoReply();
    $themeColor = $conversation->theme_color ?? 'orange';
    $themeBg = match($themeColor) {
        'blue' => 'bg-blue-500', 'green' => 'bg-emerald-500', 'purple' => 'bg-purple-500',
        'pink' => 'bg-pink-500', 'red' => 'bg-red-500', 'yellow' => 'bg-amber-500', 'teal' => 'bg-teal-500',
        default => 'bg-orange-500',
    };
    $themeReplyBg = match($themeColor) {
        'blue' => 'bg-blue-600/30', 'green' => 'bg-emerald-600/30', 'purple' => 'bg-purple-600/30',
        'pink' => 'bg-pink-600/30', 'red' => 'bg-red-600/30', 'yellow' => 'bg-amber-600/30', 'teal' => 'bg-teal-600/30',
        default => 'bg-orange-600/30',
    };
    $themeRing = match($themeColor) {
        'blue' => 'ring-blue-200', 'green' => 'ring-emerald-200', 'purple' => 'ring-purple-200',
        'pink' => 'ring-pink-200', 'red' => 'ring-red-200', 'yellow' => 'ring-amber-200', 'teal' => 'ring-teal-200',
        default => 'ring-orange-200',
    };
@endphp

@if ($isSystem)
    {{-- System Message --}}
    <div class="flex justify-center my-3">
        <div class="px-3 py-1.5 bg-gray-100/80 rounded-full text-[11px] text-gray-500 font-medium">
            {{ $message->content }}
        </div>
    </div>
@else
    <div class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }} group msg-row" id="msg-{{ $message->id }}"
        @if ($isOwn) @contextmenu.prevent="showContextMenu($event, {{ $message->id }}, true, {{ Js::from(Str::limit($message->content ?? '', 200)) }})" @endif>
        <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] lg:max-w-[65%]">
            {{-- Avatar (other user — left side) --}}
            @if (!$isOwn)
                <div class="shrink-0 mb-5">
                    @if ($message->sender?->avatar)
                        <img src="{{ $message->sender->getAvatarUrl() }}" alt=""
                            class="w-7 h-7 rounded-full object-cover ring-2 ring-white shadow-sm">
                    @else
                        <div
                            class="w-7 h-7 rounded-full bg-linear-to-br from-gray-300 to-gray-400 flex items-center justify-center text-white text-[10px] font-bold ring-2 ring-white shadow-sm">
                            {{ strtoupper(mb_substr($message->sender->name ?? '?', 0, 1)) }}
                        </div>
                    @endif
                </div>
            @endif

            <div class="space-y-0.5 {{ $isOwn ? 'items-end' : 'items-start' }}">
                {{-- Auto-reply badge --}}
                @if ($isAutoReply)
                    <div
                        class="flex items-center gap-1 px-2 py-0.5 mb-1 text-[10px] font-medium text-amber-700 bg-amber-50 rounded-full w-fit {{ $isOwn ? 'ml-auto' : '' }}">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                        </svg>
                        Réponse auto
                    </div>
                @endif

                {{-- Bubble --}}
                <div
                    class="{{ $isOwn
                        ? $themeBg . ' text-white rounded-2xl rounded-br-md'
                        : 'bg-white border border-gray-100 text-gray-900 rounded-2xl rounded-bl-md shadow-sm' }}">

                    {{-- Reply context --}}
                    @if (!empty($message->metadata['reply_to_content']))
                        <div
                            class="mx-2.5 mt-2.5 mb-0 px-2.5 py-1.5 rounded-lg border-l-2
                            {{ $isOwn ? $themeReplyBg . ' border-white/50' : 'bg-gray-50 border-orange-400' }}">
                            <p class="text-[11px] {{ $isOwn ? 'text-white/70' : 'text-gray-500' }} truncate">
                                {{ Str::limit($message->metadata['reply_to_content'], 80) }}
                            </p>
                        </div>
                    @endif

                    {{-- Image attachment --}}
                    @if ($message->type === 'image' && !empty($message->attachments))
                        @foreach ($message->attachments as $attachment)
                            <div class="p-1.5 {{ !empty($message->content) ? 'pb-0' : '' }}">
                                <img src="{{ Storage::url($attachment['path']) }}"
                                    alt="{{ $attachment['name'] ?? 'Image' }}"
                                    class="rounded-xl max-h-72 w-auto cursor-pointer hover:opacity-90 transition-opacity"
                                    onclick="window.open(this.src, '_blank')" loading="lazy">
                            </div>
                        @endforeach
                    @endif

                    {{-- File / Document attachment --}}
                    @if (in_array($message->type, ['file', 'document']) && !empty($message->attachments))
                        @foreach ($message->attachments as $attachment)
                            <div class="mx-2.5 mt-2.5 {{ !empty($message->content) ? 'mb-0' : 'mb-2.5' }}">
                                <a href="{{ Storage::url($attachment['path']) }}" target="_blank"
                                    class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl transition-colors
                                          {{ $isOwn ? 'bg-orange-600/30 hover:bg-orange-600/40' : 'bg-gray-50 hover:bg-gray-100' }}">
                                    <div
                                        class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0
                                        {{ $isOwn ? 'bg-white/20' : 'bg-orange-100' }}">
                                        <svg class="w-5 h-5 {{ $isOwn ? 'text-white' : 'text-orange-600' }}"
                                            fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-medium truncate {{ $isOwn ? 'text-white' : 'text-gray-800' }}">
                                            {{ $attachment['name'] ?? 'Document' }}
                                        </p>
                                        @if (!empty($attachment['size']))
                                            <p class="text-[11px] {{ $isOwn ? 'text-white/60' : 'text-gray-400' }}">
                                                {{ number_format($attachment['size'] / 1024, 0) }} Ko
                                            </p>
                                        @endif
                                    </div>
                                    <svg class="w-4 h-4 shrink-0 {{ $isOwn ? 'text-white/60' : 'text-gray-400' }}"
                                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                </a>
                            </div>
                        @endforeach
                    @endif

                    {{-- Text content --}}
                    @if (!empty($message->content))
                        <div
                            class="px-3.5 py-2 {{ $message->type !== 'text' && !empty($message->attachments) ? 'pt-1.5' : '' }}">
                            <p class="msg-text text-[14.5px] leading-relaxed whitespace-pre-wrap wrap-break-word">{{ $message->content }}</p>
                            @if ($message->isEdited())
                                <span class="edited-badge text-[10px] {{ $isOwn ? 'text-white/60' : 'text-gray-400' }} ml-1">(modifié)</span>
                            @endif
                        </div>
                    @endif

                    {{-- GIF message --}}
                    @if ($message->type === 'gif' && !empty($message->metadata['gif_url']))
                        <div class="p-1.5">
                            <img src="{{ $message->metadata['gif_url'] }}" alt="GIF"
                                class="rounded-xl max-h-64 w-auto" loading="lazy"
                                style="max-width: {{ min($message->metadata['width'] ?? 250, 300) }}px">
                            <span class="text-[9px] {{ $isOwn ? 'text-white/40' : 'text-gray-300' }} px-1">GIF</span>
                        </div>
                    @endif

                    {{-- Voice message --}}
                    @if ($message->type === 'voice' && !empty($message->attachments))
                        @php $voiceAttach = $message->attachments[0]; @endphp
                        <div class="px-3 py-2.5 flex items-center gap-3 min-w-[200px]">
                            <button onclick="this.closest('.voice-player').querySelector('audio').paused ? this.closest('.voice-player').querySelector('audio').play() : this.closest('.voice-player').querySelector('audio').pause()"
                                class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 {{ $isOwn ? 'bg-white/20 hover:bg-white/30' : 'bg-orange-100 hover:bg-orange-200' }} transition-colors">
                                <svg class="w-4 h-4 {{ $isOwn ? 'text-white' : 'text-orange-600' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </button>
                            <div class="flex-1 voice-player">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1 bg-white/20 rounded-full overflow-hidden">
                                        <div class="h-full {{ $isOwn ? 'bg-white/60' : 'bg-orange-400' }} rounded-full" style="width: 0%"></div>
                                    </div>
                                    <span class="text-[11px] {{ $isOwn ? 'text-white/70' : 'text-gray-500' }} shrink-0 tabular-nums">
                                        {{ gmdate('i:s', $voiceAttach['duration'] ?? 0) }}
                                    </span>
                                </div>
                                <audio preload="none" src="{{ route('messages.voice-stream', $message) }}"></audio>
                            </div>
                        </div>
                    @endif

                    {{-- Link preview --}}
                    @if (!empty($message->link_preview))
                        @php $lp = $message->link_preview; @endphp
                        <a href="{{ $lp['url'] }}" target="_blank" rel="noopener"
                            class="block mx-2 mb-2 rounded-xl overflow-hidden border {{ $isOwn ? 'border-white/20' : 'border-gray-200' }} hover:opacity-90 transition-opacity">
                            @if (!empty($lp['image']))
                                <img src="{{ $lp['image'] }}" alt="" class="w-full h-32 object-cover" loading="lazy">
                            @endif
                            <div class="px-3 py-2 {{ $isOwn ? 'bg-white/10' : 'bg-gray-50' }}">
                                <p class="text-xs font-bold {{ $isOwn ? 'text-white' : 'text-gray-900' }} truncate">{{ $lp['title'] }}</p>
                                @if (!empty($lp['description']))
                                    <p class="text-[10px] {{ $isOwn ? 'text-white/70' : 'text-gray-500' }} line-clamp-2 mt-0.5">{{ $lp['description'] }}</p>
                                @endif
                                <p class="text-[9px] {{ $isOwn ? 'text-white/50' : 'text-gray-400' }} mt-1 uppercase">{{ $lp['domain'] ?? '' }}</p>
                            </div>
                        </a>
                    @endif
                </div>

                {{-- Reactions display --}}
                @if ($message->reactions && $message->reactions->count() > 0)
                    <div class="flex flex-wrap gap-1 px-1 -mt-1 {{ $isOwn ? 'justify-end' : '' }}">
                        @foreach ($message->getGroupedReactions() as $reaction)
                            <button @click="toggleReaction({{ $message->id }}, '{{ $reaction['emoji'] }}')"
                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 text-xs rounded-full border transition-all hover:scale-105
                                {{ in_array(auth()->id(), $reaction['users']) ? 'bg-orange-50 border-orange-300 text-orange-700' : 'bg-white border-gray-200 text-gray-600' }}">
                                <span>{{ $reaction['emoji'] }}</span>
                                @if ($reaction['count'] > 1)
                                    <span class="text-[10px] font-medium">{{ $reaction['count'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Meta (time + read) --}}
                <div class="msg-meta flex items-center gap-1.5 px-1 {{ $isOwn ? 'justify-end' : '' }}">
                    <span class="text-[10px] text-gray-400">
                        {{ $message->created_at->format('H:i') }}
                    </span>
                    @if ($isOwn)
                        <span class="msg-status-icon" data-own="true">
                        @if ($message->read_at)
                            <svg class="w-3.5 h-3.5 text-blue-500" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2 13l4 4L14 7M10 13l4 4L22 7" />
                            </svg>
                        @elseif($message->delivered_at)
                            <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2 13l4 4L14 7M10 13l4 4L22 7" />
                            </svg>
                        @else
                            <svg class="w-3 h-3 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        @endif
                        </span>
                    @endif
                </div>

                {{-- Hover actions: react + reply --}}
                <div class="hidden group-hover:flex items-center gap-1 {{ $isOwn ? 'justify-end' : '' }}">
                    {{-- Quick reactions --}}
                    <div class="flex items-center gap-0.5 bg-white border border-gray-100 rounded-full px-1 py-0.5 shadow-sm">
                        @foreach (['👍', '❤️', '😂', '😮', '😢', '😡'] as $emoji)
                            <button @click="toggleReaction({{ $message->id }}, '{{ $emoji }}')"
                                class="w-6 h-6 flex items-center justify-center text-sm rounded-full hover:bg-gray-100 hover:scale-125 transition-all"
                                title="{{ $emoji }}">{{ $emoji }}</button>
                        @endforeach
                    </div>
                    {{-- Reply --}}
                    <button @click="setReplyTo({{ $message->id }}, @js(Str::limit($message->content, 60)))"
                        class="flex items-center gap-1 px-2 py-0.5 text-[10px] text-gray-400 hover:text-orange-500 rounded-full hover:bg-orange-50 transition-all"
                        title="Répondre">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                        </svg>
                        Répondre
                    </button>
                </div>
            </div>

            {{-- Avatar (own user — right side) --}}
            @if ($isOwn)
                <div class="shrink-0 mb-5">
                    @if (auth()->user()->avatar)
                        <img src="{{ auth()->user()->getAvatarUrl() }}" alt=""
                            class="w-7 h-7 rounded-full object-cover ring-2 ring-orange-200 shadow-sm">
                    @else
                        <div
                            class="w-7 h-7 rounded-full bg-linear-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white text-[10px] font-bold ring-2 ring-orange-200 shadow-sm">
                            {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif
