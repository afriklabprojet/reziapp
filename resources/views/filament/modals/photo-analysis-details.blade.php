<div class="space-y-4 p-4">
    {{-- Photo Preview --}}
    <div class="text-center">
        <img src="{{ asset('storage/' . $photo->path) }}" alt="Photo" class="max-h-64 mx-auto rounded-lg shadow">
    </div>

    {{-- Moderation Status --}}
    <div class="grid grid-cols-2 gap-3">
        <div>
            <span class="text-xs text-gray-500">Statut IA</span>
            <div class="font-semibold">
                @switch($photo->moderation_status)
                    @case('approved') <span class="text-success-600">✅ Approuvé</span> @break
                    @case('review') <span class="text-warning-600">🔍 À vérifier</span> @break
                    @case('rejected') <span class="text-danger-600">❌ Rejeté</span> @break
                    @case('pending') <span class="text-gray-500">⏳ En attente</span> @break
                    @default <span class="text-gray-400">{{ $photo->moderation_status }}</span>
                @endswitch
            </div>
        </div>
        <div>
            <span class="text-xs text-gray-500">Qualité</span>
            <div class="font-semibold {{ ($photo->quality_score ?? 0) >= 70 ? 'text-success-600' : (($photo->quality_score ?? 0) >= 40 ? 'text-warning-600' : 'text-danger-600') }}">
                {{ $photo->quality_score ?? '—' }}/100
            </div>
        </div>
    </div>

    {{-- Reason --}}
    @if($photo->moderation_reason)
        <div>
            <span class="text-xs text-gray-500">Raison de modération</span>
            <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded text-sm">{{ $photo->moderation_reason }}</div>
        </div>
    @endif

    {{-- Room & Tags --}}
    <div class="grid grid-cols-2 gap-3">
        <div>
            <span class="text-xs text-gray-500">Pièce détectée</span>
            <div class="font-medium">{{ $photo->room_type ?? '—' }}</div>
        </div>
        <div>
            <span class="text-xs text-gray-500">Photo immobilière</span>
            <div>{{ $photo->is_property_photo ? '✅ Oui' : '❌ Non' }}</div>
        </div>
    </div>

    @if($photo->tags && count($photo->tags))
        <div>
            <span class="text-xs text-gray-500">Tags détectés</span>
            <div class="flex flex-wrap gap-1 mt-1">
                @foreach($photo->tags as $tag)
                    <span class="px-2 py-0.5 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-xs rounded-full">{{ $tag }}</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Quality Issues --}}
    @if($photo->quality_issues && count($photo->quality_issues))
        <div>
            <span class="text-xs text-gray-500">Problèmes de qualité</span>
            <ul class="list-disc list-inside text-sm text-danger-600 mt-1">
                @foreach($photo->quality_issues as $issue)
                    <li>{{ $issue }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- SafeSearch Data --}}
    @if($photo->safe_search_data)
        <div>
            <span class="text-xs text-gray-500">SafeSearch</span>
            <div class="grid grid-cols-3 gap-2 mt-1 text-xs">
                @foreach($photo->safe_search_data as $key => $value)
                    <div class="bg-gray-50 dark:bg-gray-800 p-1.5 rounded text-center">
                        <div class="text-gray-400">{{ ucfirst($key) }}</div>
                        <div class="font-medium {{ in_array($value, ['LIKELY', 'VERY_LIKELY']) ? 'text-danger-600' : 'text-success-600' }}">
                            {{ $value }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Image Hash --}}
    @if($photo->image_hash)
        <div>
            <span class="text-xs text-gray-500">Hash perceptuel</span>
            <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded block mt-1">{{ $photo->image_hash }}</code>
        </div>
    @endif
</div>
