@extends('layouts.app')

@section('title', 'Ouvrir un litige')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ url()->previous() }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Signaler un problème</h1>
        <p class="text-gray-600 mt-1">Notre équipe examinera votre demande et vous répondra sous 48h</p>
    </div>

    <!-- Booking Context -->
    @if($booking)
        <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
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
    <form action="{{ route('disputes.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        
        @if($booking)
            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
        @endif
        
        @if($cancellation)
            <input type="hidden" name="cancellation_id" value="{{ $cancellation->id }}">
        @endif

        <!-- Type -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type de problème *</label>
            <div class="space-y-2">
                @foreach($types as $value => $label)
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ old('type') === $value ? 'border-[#ff385c] bg-[#fff0f3]' : '' }}">
                        <input type="radio" 
                               name="type" 
                               value="{{ $value }}" 
                               class="w-4 h-4 text-[#e00b41] border-gray-300 focus:ring-[#ff385c]"
                               {{ old('type') === $value ? 'checked' : '' }}
                               required>
                        <span class="ml-3 text-gray-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Reason (short) -->
        <div>
            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                Résumé du problème *
            </label>
            <input type="text" 
                   name="reason" 
                   id="reason"
                   value="{{ old('reason') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]"
                   placeholder="Décrivez brièvement le problème..."
                   maxlength="255"
                   required>
            @error('reason')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Detailed Description -->
        <div>
            <label for="detailed_description" class="block text-sm font-medium text-gray-700 mb-2">
                Description détaillée *
            </label>
            <textarea name="detailed_description" 
                      id="detailed_description" 
                      rows="6"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]"
                      placeholder="Expliquez en détail ce qui s'est passé, quand, et ce que vous attendez comme résolution..."
                      required>{{ old('detailed_description') }}</textarea>
            <p class="mt-1 text-sm text-gray-500">Soyez aussi précis que possible pour nous aider à traiter votre demande rapidement.</p>
            @error('detailed_description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Evidence Upload -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Pièces justificatives (optionnel)
            </label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center" 
                 x-data="{ files: [] }"
                 x-on:dragover.prevent="$el.classList.add('border-[#ff385c]')"
                 x-on:dragleave.prevent="$el.classList.remove('border-[#ff385c]')"
                 x-on:drop.prevent="
                     $el.classList.remove('border-[#ff385c]');
                     files = [...$event.dataTransfer.files];
                     $refs.fileInput.files = $event.dataTransfer.files;
                 ">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="mt-2 text-sm text-gray-600">
                    <label class="text-[#e00b41] hover:text-[#b5083a] cursor-pointer">
                        <span>Téléverser des fichiers</span>
                        <input type="file" 
                               name="evidence[]" 
                               multiple 
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                               class="sr-only"
                               x-ref="fileInput"
                               x-on:change="files = [...$event.target.files]">
                    </label>
                    ou glisser-déposer
                </p>
                <p class="text-xs text-gray-500 mt-1">PNG, JPG, PDF jusqu'à 10 Mo</p>
                
                <!-- File List -->
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
            @error('evidence.*')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Info Box -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-medium">Ce qui se passe ensuite :</p>
                    <ol class="mt-2 list-decimal list-inside space-y-1">
                        <li>Notre équipe examine votre demande sous 24h</li>
                        <li>Nous contactons l'autre partie si nécessaire</li>
                        <li>Une décision est rendue dans un délai de 7 jours</li>
                        <li>Vous êtes notifié par email à chaque étape</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex space-x-4">
            <a href="{{ url()->previous() }}" 
               class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-center">
                Annuler
            </a>
            <button type="submit" 
                    class="flex-1 px-6 py-3 bg-[#e00b41] text-white rounded-lg font-medium hover:bg-[#b5083a] transition-colors">
                Soumettre le litige
            </button>
        </div>
    </form>
</div>
@endsection
