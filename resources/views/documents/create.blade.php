@extends('layouts.app')

@section('title', 'Ajouter un document')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="{{ route('documents.index') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Retour aux documents
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Ajouter un document</h1>
        <p class="text-gray-600 mt-1">Partagez un document avec vos voyageurs</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6" x-data="documentForm()">
        <form @submit.prevent="submitForm" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du document <span class="text-red-500">*</span></label>
                <input type="text" id="name" x-model="form.name" class="input-field" placeholder="Ex: Règlement intérieur" required>
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                <select id="type" x-model="form.type" class="input-field" required>
                    <option value="">Sélectionner un type</option>
                    @foreach($types as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if($residences->isNotEmpty())
            <div>
                <label for="residence_id" class="block text-sm font-medium text-gray-700 mb-1">Résidence associée</label>
                <select id="residence_id" x-model="form.residence_id" class="input-field">
                    <option value="">Aucune (document général)</option>
                    @foreach($residences as $residence)
                    <option value="{{ $residence->id }}">{{ $residence->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label for="access_type" class="block text-sm font-medium text-gray-700 mb-1">Accès <span class="text-red-500">*</span></label>
                <select id="access_type" x-model="form.access_type" class="input-field" required>
                    <option value="private">Privé (moi uniquement)</option>
                    <option value="conversation">Conversations (partagé dans les messages)</option>
                    <option value="public">Public (visible par tous)</option>
                </select>
            </div>

            <div>
                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration</label>
                <input type="datetime-local" id="expires_at" x-model="form.expires_at" class="input-field">
                <p class="text-xs text-gray-500 mt-1">Laissez vide pour un accès permanent</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fichier <span class="text-red-500">*</span></label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary-400 transition-colors">
                    <input type="file" id="file" @change="handleFile" class="hidden" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                    <label for="file" class="cursor-pointer">
                        <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <p class="text-sm text-gray-600">Cliquez pour sélectionner un fichier</p>
                        <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX, JPG, PNG — max 20 Mo</p>
                    </label>
                    <template x-if="fileName">
                        <p class="mt-2 text-sm font-medium text-primary-600" x-text="fileName"></p>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t">
                <a href="{{ route('documents.index') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary" :disabled="loading">
                    <span x-show="!loading">Enregistrer</span>
                    <span x-show="loading" class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        Envoi…
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function documentForm() {
    return {
        form: {
            name: '',
            type: '',
            residence_id: '',
            access_type: 'private',
            expires_at: '',
        },
        file: null,
        fileName: '',
        loading: false,
        handleFile(e) {
            this.file = e.target.files[0];
            this.fileName = this.file ? this.file.name : '';
        },
        async submitForm() {
            if (!this.file) {
                alert('Veuillez sélectionner un fichier.');
                return;
            }
            this.loading = true;
            const formData = new FormData();
            formData.append('file', this.file);
            Object.entries(this.form).forEach(([key, val]) => {
                if (val) formData.append(key, val);
            });
            try {
                const res = await fetch('{{ route("documents.store") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = '{{ route("documents.index") }}';
                } else {
                    alert(data.error || 'Erreur lors de l\'envoi.');
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
