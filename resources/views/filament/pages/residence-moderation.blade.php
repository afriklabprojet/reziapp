<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-warning-100 dark:bg-warning-900 rounded-lg">
                        <x-heroicon-o-clock class="w-6 h-6 text-warning-600 dark:text-warning-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">En attente</p>
                        <p class="text-2xl font-bold text-warning-600">{{ $pendingCount }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-success-100 dark:bg-success-900 rounded-lg">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 dark:text-success-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Approuvées aujourd'hui</p>
                        <p class="text-2xl font-bold text-success-600">{{ $todayApproved }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-danger-100 dark:bg-danger-900 rounded-lg">
                        <x-heroicon-o-x-circle class="w-6 h-6 text-danger-600 dark:text-danger-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refusées aujourd'hui</p>
                        <p class="text-2xl font-bold text-danger-600">{{ $todayRejected }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-info-100 dark:bg-info-900 rounded-lg">
                        <x-heroicon-o-pencil-square class="w-6 h-6 text-info-600 dark:text-info-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Modifications demandées</p>
                        <p class="text-2xl font-bold text-info-600">{{ $changesRequested }}</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Table --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-home class="w-5 h-5"/>
                    Annonces en attente de validation
                </div>
            </x-slot>
            
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
