@extends('layouts.app')

@section('title', 'Templates de messages')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="templatesManager({{ alpine_encode(['csrfToken' => csrf_token()]) }})">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Templates de messages</h1>
            <p class="text-gray-600 mt-1">Créez des réponses rapides pour gagner du temps</p>
        </div>
        <button @click="showCreateModal = true"
                class="px-4 py-2 bg-[#CC5A00] text-white rounded-lg hover:bg-[#A34700] flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nouveau template
        </button>
    </div>

    <!-- System Templates -->
    @if($systemTemplates->isNotEmpty())
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Templates système</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($systemTemplates as $template)
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-gray-300">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="font-medium text-gray-900">{{ $template->name }}</h3>
                                <span class="inline-block px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
                                    {{ $categories[$template->category] ?? $template->category }}
                                </span>
                            </div>
                            <button @click="duplicateTemplate({{ $template->id }})"
                                    class="text-gray-400 hover:text-gray-600 p-1"
                                    title="Dupliquer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="text-sm text-gray-600 line-clamp-2">{{ $template->content }}</p>
                        @if($template->shortcut)
                            <p class="text-xs text-gray-400 mt-2">Raccourci: /{{ $template->shortcut }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- User Templates -->
    <div>
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Mes templates</h2>

        @if($userTemplates->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun template</h3>
                <p class="text-gray-500 mb-6">Créez votre premier template pour répondre plus vite</p>
                <button @click="showCreateModal = true"
                        class="inline-flex items-center px-4 py-2 bg-[#CC5A00] text-white rounded-lg hover:bg-[#A34700]">
                    Créer un template
                </button>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($userTemplates as $template)
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-[#F16A00] group">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="font-medium text-gray-900">{{ $template->name }}</h3>
                                <span class="inline-block px-2 py-0.5 text-xs bg-[#FFE7D1] text-[#A34700] rounded-full">
                                    {{ $categories[$template->category] ?? $template->category }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="editTemplate({{ json_encode($template) }})"
                                        class="text-gray-400 hover:text-gray-600 p-1"
                                        title="Modifier">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button @click="deleteTemplate({{ $template->id }})"
                                        class="text-gray-400 hover:text-red-600 p-1"
                                        title="Supprimer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 line-clamp-2">{{ $template->content }}</p>
                        <div class="flex items-center justify-between mt-3 text-xs text-gray-400">
                            @if($template->shortcut)
                                <span>/{{ $template->shortcut }}</span>
                            @else
                                <span></span>
                            @endif
                            <span>{{ $template->usage_count }} utilisation(s)</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showCreateModal || showEditModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="closeModal()"
         role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="closeModal()"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full">
                <form @submit.prevent="saveTemplate()">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900" x-text="showEditModal ? 'Modifier le template' : 'Nouveau template'"></h3>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                            <input type="text" x-model="form.name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00]"
                                   placeholder="Ex: Bienvenue">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                            <select x-model="form.category" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00]">
                                @foreach($categories as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contenu</label>
                            <textarea x-model="form.content" required rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00]"
                                      placeholder="Bonjour {user_name}, merci de votre intérêt pour..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">
                                Variables disponibles: {user_name}, {residence_name}, {owner_name}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Raccourci (optionnel)</label>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400">/</span>
                                <input type="text" x-model="form.shortcut"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00]"
                                       placeholder="bienvenue"
                                       pattern="[a-z0-9_]+">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Lettres minuscules et underscores uniquement</p>
                        </div>
                    </div>

                    <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                        <button type="button" @click="closeModal()"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Annuler
                        </button>
                        <button type="submit"
                                :disabled="saving"
                                class="px-4 py-2 bg-[#CC5A00] text-white rounded-lg hover:bg-[#A34700] disabled:opacity-50">
                            <span x-show="!saving" x-text="showEditModal ? 'Enregistrer' : 'Créer'"></span>
                            <span x-show="saving">Enregistrement...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@endpush
@endsection
