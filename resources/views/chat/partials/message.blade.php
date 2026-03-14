@php
    $isOwn = $message->sender_id === auth()->id();
    $isSystem = $message->isSystem();
    $isAutoReply = $message->isAutoReply();
@endphp

@if ($isSystem)
    {{-- System Message --}}
    <div class="flex justify-center my-3">
        <div class="px-3 py-1.5 bg-gray-100/80 rounded-full text-[11px] text-gray-500 font-medium">
            {{ $message->content }}
        </div>
    </div>
@else
    <div class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }} group" id="message-{{ $message->id }}">
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
                        ? 'bg-orange-500 text-white rounded-2xl rounded-br-md'
                        : 'bg-white border border-gray-100 text-gray-900 rounded-2xl rounded-bl-md shadow-sm' }}">

                    {{-- Reply context --}}
                    @if (!empty($message->metadata['reply_to_content']))
                        <div
                            class="mx-2.5 mt-2.5 mb-0 px-2.5 py-1.5 rounded-lg border-l-2
                            {{ $isOwn ? 'bg-orange-600/30 border-white/50' : 'bg-gray-50 border-orange-400' }}">
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
                            <p class="text-[14.5px] leading-relaxed whitespace-pre-wrap wrap-break-word">
                                {{ $message->content }}</p>
                        </div>
                    @endif
                </div>

                {{-- Meta (time + read) --}}
                <div class="flex items-center gap-1.5 px-1 {{ $isOwn ? 'justify-end' : '' }}">
                    <span class="text-[10px] text-gray-400">
                        {{ $message->created_at->format('H:i') }}
                    </span>
                    @if ($isOwn)
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
                    @endif
                </div>

                {{-- Reply action (hover) --}}
                <button @click="setReplyTo({{ $message->id }}, @js(Str::limit($message->content, 60)))"
                    class="hidden group-hover:flex items-center gap-1 px-2 py-0.5 text-[10px] text-gray-400 hover:text-orange-500 rounded-full hover:bg-orange-50 transition-all {{ $isOwn ? 'ml-auto' : '' }}"
                    title="Répondre">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                    </svg>
                    Répondre
                </button>
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
