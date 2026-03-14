@extends('layouts.owner')

@section('title', 'Réponses automatiques - REZI')

@section('owner-content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900">Réponses automatiques</h1>
                <p class="mt-1 text-sm text-gray-500">Gagnez du temps avec des messages prédéfinis</p>
            </div>
            <a href="{{ route('owner.auto-replies.create') }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-500 text-white text-sm font-semibold rounded-xl hover:bg-orange-600 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvelle réponse
            </a>
        </div>

        {{-- Message flash --}}
        @if (session('success'))
            <div
                class="mb-6 flex items-center gap-3 bg-orange-50 border border-orange-200 text-orange-700 px-4 py-3 rounded-xl text-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Onglets par type --}}
        <div x-data="{ activeTab: 'all' }" class="space-y-6">
            <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
                @php
                    $tabs = [
                        'all' => ['label' => 'Toutes', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
                        'first_contact' => [
                            'label' => 'Premier contact',
                            'icon' =>
                                'M7 11.5V14m0 0V8.5M7 14h6m0 0v2.5M13 14V8.5M13 14h4m-4 0V8.5m4 5.5V8.5M3 20a6 6 0 0112 0v1H3v-1z',
                        ],
                        'keywords' => ['label' => 'Mots-clés', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                        'schedule' => ['label' => 'Horaires', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'manual' => [
                            'label' => 'Manuelles',
                            'icon' =>
                                'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
                        ],
                    ];
                @endphp
                @foreach ($tabs as $key => $tab)
                    <button @click="activeTab = '{{ $key }}'"
                        :class="activeTab === '{{ $key }}' ? 'bg-orange-500 text-white shadow-sm' :
                            'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}" />
                        </svg>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>

            {{-- Liste des réponses --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                @forelse($autoReplies as $reply)
                    <div x-show="activeTab === 'all' || activeTab === '{{ $reply->trigger_type }}'"
                        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="p-5 border-b border-gray-100 last:border-0 hover:bg-gray-50/50 transition group">
                        <div class="flex items-start gap-4">
                            {{-- Icône type --}}
                            @php
                                $typeConfig = match ($reply->trigger_type) {
                                    'first_contact' => [
                                        'bg' => 'bg-blue-50',
                                        'text' => 'text-blue-600',
                                        'icon' =>
                                            'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                                    ],
                                    'keywords' => [
                                        'bg' => 'bg-purple-50',
                                        'text' => 'text-purple-600',
                                        'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                                    ],
                                    'schedule' => [
                                        'bg' => 'bg-amber-50',
                                        'text' => 'text-amber-600',
                                        'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                                    ],
                                    'manual' => [
                                        'bg' => 'bg-gray-50',
                                        'text' => 'text-gray-600',
                                        'icon' =>
                                            'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
                                    ],
                                    default => [
                                        'bg' => 'bg-gray-50',
                                        'text' => 'text-gray-600',
                                        'icon' =>
                                            'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
                                    ],
                                };
                            @endphp
                            <div
                                class="w-11 h-11 rounded-xl {{ $typeConfig['bg'] }} flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 {{ $typeConfig['text'] }}" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="{{ $typeConfig['icon'] }}" />
                                </svg>
                            </div>

                            {{-- Contenu --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-bold text-sm text-gray-900">{{ $reply->name }}</h3>
                                    @if ($reply->is_active)
                                        <span
                                            class="px-2 py-0.5 bg-orange-100 text-orange-600 text-[11px] font-semibold rounded-full">Actif</span>
                                    @else
                                        <span
                                            class="px-2 py-0.5 bg-gray-100 text-gray-500 text-[11px] font-semibold rounded-full">Inactif</span>
                                    @endif
                                </div>

                                {{-- Conditions --}}
                                <div class="text-xs text-gray-500 mb-1.5">
                                    @if ($reply->trigger_type === 'keywords' && $reply->trigger_conditions)
                                        <span class="text-gray-400">Mots-clés :</span>
                                        @foreach ($reply->trigger_conditions['keywords'] ?? [] as $keyword)
                                            <span
                                                class="inline-block px-2 py-0.5 bg-purple-50 text-purple-700 rounded text-[11px] font-medium">{{ $keyword }}</span>
                                        @endforeach
                                    @elseif($reply->trigger_type === 'schedule' && $reply->trigger_conditions)
                                        <span class="text-gray-400">Plage :</span>
                                        {{ $reply->trigger_conditions['start_time'] ?? '00:00' }} —
                                        {{ $reply->trigger_conditions['end_time'] ?? '23:59' }}
                                    @elseif($reply->trigger_type === 'first_contact')
                                        Envoyé au premier message d'un client
                                    @else
                                        Réponse rapide en un clic
                                    @endif
                                </div>

                                {{-- Aperçu --}}
                                <p class="text-gray-600 text-sm line-clamp-2">{{ $reply->message }}</p>

                                {{-- Résidence + stats --}}
                                <div class="flex flex-wrap items-center gap-3 mt-2.5 text-[11px] text-gray-400">
                                    @if ($reply->residence)
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                            {{ $reply->residence->name }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                                            </svg>
                                            Toutes les résidences
                                        </span>
                                    @endif
                                    <span>{{ $reply->usage_count }}
                                        utilisation{{ $reply->usage_count > 1 ? 's' : '' }}</span>
                                    @if ($reply->last_used_at)
                                        <span>Dernière : {{ $reply->last_used_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-1 opacity-60 group-hover:opacity-100 transition">
                                <form action="{{ route('owner.auto-replies.toggle', $reply) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="p-2 rounded-lg hover:bg-gray-100 transition"
                                        title="{{ $reply->is_active ? 'Désactiver' : 'Activer' }}">
                                        @if ($reply->is_active)
                                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        @endif
                                    </button>
                                </form>

                                <a href="{{ route('owner.auto-replies.edit', $reply) }}"
                                    class="p-2 rounded-lg hover:bg-gray-100 transition" title="Modifier">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>

                                <form action="{{ route('owner.auto-replies.destroy', $reply) }}" method="POST"
                                    onsubmit="return confirm('Supprimer cette réponse ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg hover:bg-red-50 transition"
                                        title="Supprimer">
                                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Aucune réponse automatique</h3>
                        <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                            Créez des réponses prédéfinies pour répondre plus vite aux questions fréquentes.
                        </p>
                        <a href="{{ route('owner.auto-replies.create') }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-500 text-white text-sm font-semibold rounded-xl hover:bg-orange-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Créer ma première réponse
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Templates suggérés --}}
        @if ($autoReplies->isEmpty())
            <div class="mt-8">
                <h2 class="text-base font-bold text-gray-900 mb-4">Templates suggérés</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php
                        $templates = [
                            [
                                'name' => 'Message de bienvenue',
                                'type' => 'first_contact',
                                'bg' => 'bg-blue-50',
                                'text' => 'text-blue-600',
                                'icon' =>
                                    'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                                'message' =>
                                    'Bonjour et merci pour votre intérêt ! Je suis ravi de vous répondre. N\'hésitez pas à me poser toutes vos questions.',
                            ],
                            [
                                'name' => 'Horaires check-in',
                                'type' => 'keywords',
                                'bg' => 'bg-purple-50',
                                'text' => 'text-purple-600',
                                'icon' =>
                                    'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
                                'message' =>
                                    'Le check-in est prévu à partir de 14h00. Je peux m\'adapter si vous arrivez plus tôt ou plus tard !',
                            ],
                            [
                                'name' => 'Réponse absence',
                                'type' => 'schedule',
                                'bg' => 'bg-amber-50',
                                'text' => 'text-amber-600',
                                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                                'message' =>
                                    'Merci pour votre message ! Je suis actuellement indisponible mais je vous répondrai dès que possible.',
                            ],
                            [
                                'name' => 'Disponibilité',
                                'type' => 'manual',
                                'bg' => 'bg-gray-50',
                                'text' => 'text-gray-600',
                                'icon' =>
                                    'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                                'message' =>
                                    'Oui, le logement est disponible pour vos dates ! Voulez-vous que je vous envoie plus de détails ?',
                            ],
                        ];
                    @endphp
                    @foreach ($templates as $template)
                        <div
                            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-orange-300 transition group">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-9 h-9 rounded-lg {{ $template['bg'] }} flex items-center justify-center">
                                    <svg class="w-4 h-4 {{ $template['text'] }}" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $template['icon'] }}" />
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-sm text-gray-900">{{ $template['name'] }}</h3>
                            </div>
                            <p class="text-xs text-gray-500 mb-3 line-clamp-2">{{ $template['message'] }}</p>
                            <a href="{{ route('owner.auto-replies.create', ['template' => $template['type']]) }}"
                                class="text-xs text-orange-500 hover:text-orange-600 font-semibold group-hover:underline">
                                Utiliser ce template →
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
