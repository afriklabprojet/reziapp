@extends('layouts.app')

@section('title', 'Nouvelle demande d\'aide')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('support.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour au centre d'aide
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Nouvelle demande d'aide</h1>
        <p class="text-gray-600 mt-1">Décrivez votre problème et nous vous répondrons rapidement</p>
    </div>

    <!-- Booking Context -->
    @if($booking)
        <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
            <p class="text-sm text-gray-500 mb-2">Concernant la réservation:</p>
            <div class="flex items-center space-x-4">
                @if($booking->residence->mainPhoto)
                    <img loading="lazy" src="{{ storage_url($booking->residence->mainPhoto->path) }}" 
                         alt="{{ $booking->residence->name }}"
                         class="w-16 h-16 object-cover rounded-lg">
                @endif
                <div>
                    <h3 class="font-medium text-gray-900">{{ $booking->residence->title }}</h3>
                    <p class="text-sm text-gray-600">
                        {{ $booking->check_in->format('d M') }} → {{ $booking->check_out->format('d M Y') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Form -->
    <form action="{{ route('support.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        
        @if($booking)
            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
        @endif

        <!-- Category -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie *</label>
            <select name="category" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                <option value="">Sélectionnez une catégorie</option>
                @foreach($categories as $value => $label)
                    <option value="{{ $value }}" {{ old('category') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('category')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Subject -->
        <div>
            <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                Sujet *
            </label>
            <input type="text" 
                   name="subject" 
                   id="subject"
                   value="{{ old('subject') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]"
                   placeholder="Résumez votre demande en quelques mots..."
                   maxlength="255"
                   required>
            @error('subject')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Message -->
        <div>
            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                Message *
            </label>
            <textarea name="message" 
                      id="message" 
                      rows="6"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]"
                      placeholder="Décrivez votre problème en détail..."
                      required>{{ old('message') }}</textarea>
            @error('message')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Priority (optional) -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Urgence (optionnel)
            </label>
            <div class="grid grid-cols-2 gap-3">
                @foreach($priorities as $value => $label)
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ old('priority', 'medium') === $value ? 'border-[#F16A00] bg-[#FFF4EB]' : '' }}">
                        <input type="radio" 
                               name="priority" 
                               value="{{ $value }}" 
                               class="w-4 h-4 text-[#CC5A00] border-gray-300 focus:ring-[#F16A00]"
                               {{ old('priority', 'medium') === $value ? 'checked' : '' }}>
                        <span class="ml-3 text-gray-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Attachments -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Pièces jointes (optionnel)
            </label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center"
                 x-data="{ files: [] }">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="mt-2 text-sm text-gray-600">
                    <label class="text-[#CC5A00] hover:text-[#A34700] cursor-pointer">
                        <span>Téléverser des fichiers</span>
                        <input type="file" 
                               name="attachments[]" 
                               multiple 
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                               class="sr-only"
                               x-on:change="files = [...$event.target.files]">
                    </label>
                </p>
                <p class="text-xs text-gray-500 mt-1">PNG, JPG, PDF jusqu'à 10 Mo (max 5 fichiers)</p>
                
                <template x-if="files.length > 0">
                    <div class="mt-4 space-y-2">
                        <template x-for="(file, index) in files" :key="index">
                            <div class="flex items-center justify-between bg-gray-50 px-3 py-2 rounded text-sm">
                                <span x-text="file.name" class="truncate"></span>
                                <span x-text="(file.size / 1024 / 1024).toFixed(2) + ' Mo'" class="text-gray-500"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
            @error('attachments.*')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-blue-800">
                    Notre équipe répond généralement sous 24 heures. Vous recevrez une notification par email.
                </p>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex space-x-4">
            <a href="{{ route('support.index') }}" 
               class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-center">
                Annuler
            </a>
            <button type="submit" 
                    class="flex-1 px-6 py-3 bg-[#CC5A00] text-white rounded-lg font-medium hover:bg-[#A34700] transition-colors">
                Envoyer
            </button>
        </div>
    </form>
</div>
@endsection
