<div class="space-y-6 p-4">

    {{-- Infos utilisateur --}}
    <div class="grid grid-cols-2 gap-4 rounded-lg bg-gray-50 dark:bg-gray-800 p-4 text-sm">
        <div>
            <p class="text-gray-500 dark:text-gray-400">Utilisateur</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $record->user?->name ?? '—' }}</p>
            <p class="text-gray-500">{{ $record->user?->email ?? '—' }}</p>
        </div>
        <div>
            <p class="text-gray-500 dark:text-gray-400">Document</p>
            <p class="font-semibold text-gray-900 dark:text-white">
                {{ match($record->document_type ?? '') {
                    'cni'              => 'Carte nationale d\'identité',
                    'passport'         => 'Passeport',
                    'driver_license'   => 'Permis de conduire',
                    'residence_permit' => 'Titre de séjour',
                    default            => $record->document_type ?? '—',
                } }}
            </p>
            @php $docNum = $record->document_number; @endphp
            @if($docNum && strlen($docNum) <= 30)
                <p class="text-gray-500">N° {{ $docNum }}</p>
            @elseif($docNum)
                <p class="text-gray-400 italic text-xs">Numéro invalide en base</p>
            @endif
        </div>
        @if($record->first_name || $record->last_name)
        <div>
            <p class="text-gray-500 dark:text-gray-400">Nom sur le document</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $record->first_name }} {{ $record->last_name }}</p>
        </div>
        @endif
        @if($record->birth_date)
        <div>
            <p class="text-gray-500 dark:text-gray-400">Date de naissance</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $record->birth_date?->format('d/m/Y') }}</p>
        </div>
        @endif
        @if($record->face_match_score !== null)
        <div>
            <p class="text-gray-500 dark:text-gray-400">Score Face Match</p>
            <p class="font-semibold {{ $record->face_match_passed ? 'text-green-600' : 'text-red-600' }}">
                {{ number_format($record->face_match_score, 1) }}%
                {{ $record->face_match_passed ? '✓' : '✗' }}
            </p>
        </div>
        @endif
    </div>

    {{-- Photos des documents --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        @php
            $photos = [
                ['url' => $frontUrl,  'label' => 'Recto du document',   'field' => $record->document_front],
                ['url' => $backUrl,   'label' => 'Verso du document',   'field' => $record->document_back],
                ['url' => $selfieUrl, 'label' => 'Selfie avec document','field' => $record->selfie_photo],
            ];
        @endphp

        @foreach($photos as $photo)
        <div class="flex flex-col items-center gap-2">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $photo['label'] }}</p>
            @if($photo['url'])
                <a href="{{ $photo['url'] }}" target="_blank" class="block w-full group">
                    <img
                        src="{{ $photo['url'] }}"
                        alt="{{ $photo['label'] }}"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 object-contain max-h-60 cursor-zoom-in hover:opacity-90 transition bg-gray-100 dark:bg-gray-700"
                        loading="lazy"
                        onerror="this.parentElement.parentElement.innerHTML='<div class=\'flex h-40 w-full flex-col items-center justify-center rounded-lg border-2 border-dashed border-[#FFD0A3] dark:border-[#A34700] bg-[#FFF4EB] dark:bg-[#6e0826]/20 gap-2\'><svg class=\'w-8 h-8 text-[#FF8A1F]\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z\'></path></svg><p class=\'text-xs text-[#CC5A00] dark:text-[#FF8A1F] text-center px-2\'>Fichier non disponible sur ce serveur</p></div>'"
                    >
                </a>
                <a href="{{ $photo['url'] }}" target="_blank"
                   class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                    Ouvrir en plein écran ↗
                </a>
            @else
                <div class="flex h-40 w-full items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                    <p class="text-sm text-gray-400">Aucune photo</p>
                </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Notes admin si présentes --}}
    @if($record->rejection_reason)
    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
        <p class="text-sm font-medium text-red-700 dark:text-red-400">Motif de rejet :</p>
        <p class="text-sm text-red-600 dark:text-red-300 mt-1">{{ $record->rejection_reason }}</p>
    </div>
    @endif

    @if($record->admin_notes)
    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
        <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Notes admin :</p>
        <p class="text-sm text-amber-600 dark:text-amber-300 mt-1">{{ $record->admin_notes }}</p>
    </div>
    @endif

</div>
