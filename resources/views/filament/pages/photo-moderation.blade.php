<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-warning-100 dark:bg-warning-900 rounded-lg">
                        <x-heroicon-o-eye class="w-6 h-6 text-warning-600 dark:text-warning-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">À vérifier</p>
                        <p class="text-2xl font-bold text-warning-600">{{ \App\Models\Photo::where('moderation_status', 'review')->count() }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-danger-100 dark:bg-danger-900 rounded-lg">
                        <x-heroicon-o-x-circle class="w-6 h-6 text-danger-600 dark:text-danger-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Rejetées IA</p>
                        <p class="text-2xl font-bold text-danger-600">{{ \App\Models\Photo::where('moderation_status', 'rejected')->count() }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-success-100 dark:bg-success-900 rounded-lg">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 dark:text-success-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Approuvées</p>
                        <p class="text-2xl font-bold text-success-600">{{ \App\Models\Photo::where('moderation_status', 'approved')->count() }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gray-100 dark:bg-gray-900 rounded-lg">
                        <x-heroicon-o-photo class="w-6 h-6 text-gray-600 dark:text-gray-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total photos</p>
                        <p class="text-2xl font-bold text-gray-600">{{ \App\Models\Photo::count() }}</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
