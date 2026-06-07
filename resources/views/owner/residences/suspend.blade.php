@extends('layouts.owner')

@section('title', 'Suspendre l\'annonce - ' . $residence->name)

@section('owner-content')
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                    <a href="{{ route('owner.residences.show', $residence) }}" class="hover:text-[#F16A00]">← Retour à
                        l'annonce</a>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900">Suspendre l'annonce</h1>
                <p class="mt-1 text-gray-600">{{ $residence->name }}</p>
            </div>

            @if ($residence->is_suspended)
                <!-- Annonce déjà suspendue -->
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-amber-800">Annonce suspendue</h3>
                            <p class="text-amber-700 mt-1">
                                Depuis le {{ $residence->suspended_at->format('d/m/Y à H:i') }}
                            </p>
                            @if ($residence->suspension_reason)
                                <p class="text-amber-700 mt-1">
                                    <strong>Raison :</strong> {{ $residence->suspension_reason }}
                                </p>
                            @endif
                            @if ($residence->resume_at)
                                <p class="text-amber-700 mt-1">
                                    <strong>Reprise prévue :</strong> {{ $residence->resume_at->format('d/m/Y') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Formulaire de réactivation -->
                <form action="{{ route('owner.residences.resume', $residence) }}" method="POST"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    @csrf
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Réactiver l'annonce</h2>
                    <p class="text-gray-600 mb-6">Votre annonce sera à nouveau visible sur Rezi Studio Meublé Faya.</p>

                    <button type="submit"
                        class="w-full px-6 py-3 bg-[#F16A00] text-white rounded-xl hover:bg-[#CC5A00] transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Réactiver maintenant
                    </button>
                </form>
            @else
                <!-- Formulaire de suspension -->
                <form action="{{ route('owner.residences.suspend', $residence) }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Pourquoi suspendre ?</h2>

                        <div class="space-y-3">
                            @foreach ([
            'occupied' => '🏠 Logement occupé',
            'renovation' => '🔧 Travaux / Rénovation',
            'vacation' => '🌴 Vacances / Absence',
            'personal' => '👤 Raisons personnelles',
            'seasonal' => '📅 Fermeture saisonnière',
            'other' => '📝 Autre raison',
        ] as $value => $label)
                                <label
                                    class="flex items-center gap-3 p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 has-checked:border-[#F16A00] has-checked:bg-[#FFF4EB]">
                                    <input type="radio" name="suspension_reason" value="{{ $value }}"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 focus:ring-[#F16A00]"
                                        {{ old('suspension_reason') === $value ? 'checked' : '' }}>
                                    <span class="font-medium text-gray-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('suspension_reason')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Date de reprise (optionnel)</h2>
                        <p class="text-gray-600 mb-4">Si vous connaissez la date de reprise, l'annonce sera automatiquement
                            réactivée.</p>

                        <input type="date" name="resume_at" value="{{ old('resume_at') }}"
                            min="{{ now()->addDay()->format('Y-m-d') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Note (optionnel)</h2>
                        <textarea name="suspension_note" rows="3" placeholder="Notes pour vous rappeler les détails..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">{{ old('suspension_note') }}</textarea>
                    </div>

                    <!-- Avertissement -->
                    <div class="bg-blue-50 rounded-2xl p-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <h3 class="font-semibold text-blue-800">Ce qui va se passer</h3>
                                <ul class="text-blue-700 text-sm mt-2 space-y-1">
                                    <li>• Votre annonce ne sera plus visible dans les recherches</li>
                                    <li>• Les clients ne pourront plus vous contacter via cette annonce</li>
                                    <li>• Vos données et photos seront conservées</li>
                                    <li>• Vous pourrez réactiver à tout moment</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-4">
                        <a href="{{ route('owner.residences.show', $residence) }}"
                            class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 text-center rounded-xl hover:bg-gray-200 transition">
                            Annuler
                        </a>
                        <button type="submit"
                            class="flex-1 px-6 py-3 bg-amber-500 text-white rounded-xl hover:bg-amber-600 transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Suspendre l'annonce
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
