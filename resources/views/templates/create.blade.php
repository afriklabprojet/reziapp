@extends('layouts.app')

@section('title', 'Créer un modèle de message')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="{{ route('templates.index') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Retour aux modèles
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Créer un modèle de message</h1>
        <p class="text-gray-600 mt-1">Gagnez du temps avec des réponses prédéfinies</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6" x-data="templateForm()">
        <form @submit.prevent="submitForm" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du modèle <span class="text-red-500">*</span></label>
                <input type="text" id="name" x-model="form.name" class="input-field" placeholder="Ex: Message de bienvenue" required>
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Catégorie <span class="text-red-500">*</span></label>
                <select id="category" x-model="form.category" class="input-field" required>
                    <option value="">Sélectionner</option>
                    @foreach($categories as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Contenu du message <span class="text-red-500">*</span></label>
                <textarea id="content" x-model="form.content" class="input-field" rows="6" placeholder="Bonjour {nom}, merci pour votre réservation..." required></textarea>
                <p class="text-xs text-gray-500 mt-1">Variables disponibles : <code class="bg-gray-100 px-1 rounded">{nom}</code>, <code class="bg-gray-100 px-1 rounded">{residence}</code>, <code class="bg-gray-100 px-1 rounded">{date}</code></p>
            </div>

            <div>
                <label for="shortcut" class="block text-sm font-medium text-gray-700 mb-1">Raccourci</label>
                <div class="flex items-center gap-2">
                    <span class="text-gray-500">/</span>
                    <input type="text" id="shortcut" x-model="form.shortcut" class="input-field" placeholder="bienvenue" pattern="[a-z0-9_]+">
                </div>
                <p class="text-xs text-gray-500 mt-1">Tapez <code class="bg-gray-100 px-1 rounded">/raccourci</code> dans une conversation pour insérer ce modèle</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t">
                <a href="{{ route('templates.index') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary" :disabled="loading">
                    <span x-show="!loading">Créer le modèle</span>
                    <span x-show="loading" class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        Création…
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function templateForm() {
    return {
        form: { name: '', category: '', content: '', shortcut: '' },
        loading: false,
        async submitForm() {
            this.loading = true;
            try {
                const res = await fetch('{{ route("templates.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = '{{ route("templates.index") }}';
                } else {
                    alert(data.error || 'Erreur lors de la création.');
                }
            } catch (err) {
                alert('Erreur réseau.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush
@endsection
