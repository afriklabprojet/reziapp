<x-app-layout>
    @section('title', 'Mes collections - Rezi Studio Meublé Faya')

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Mes collections</h1>
                    <p class="text-gray-600 mt-1">Organisez vos favoris par thèmes</p>
                </div>
                <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                        class="inline-flex items-center gap-2 bg-[#CC5A00] text-white px-4 py-2 rounded-xl hover:bg-[#A34700] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouvelle collection
                </button>
            </div>

            @if($collections->isEmpty())
                <!-- État vide -->
                <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                    <div class="w-16 h-16 bg-[#FFF4EB] rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-[#FF8A1F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune collection</h3>
                    <p class="text-gray-500 mb-4">Créez des collections pour organiser vos favoris par thèmes.</p>
                    <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                            class="inline-flex items-center gap-2 bg-[#CC5A00] text-white px-4 py-2 rounded-xl hover:bg-[#A34700] transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Créer ma première collection
                    </button>
                </div>
            @else
                <!-- Grille des collections -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($collections as $collection)
                    <a href="{{ route('collections.show', $collection) }}" 
                       class="bg-white rounded-2xl shadow-sm overflow-hidden group hover:shadow-md transition-shadow">
                        <!-- Image de couverture -->
                        <div class="relative aspect-video bg-linear-to-br from-[#FFE7D1] to-[#FFD0A3]">
                            @if($collection->getCoverImageUrl())
                                <img loading="lazy" src="{{ $collection->getCoverImageUrl() }}" 
                                     alt="{{ $collection->name }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-12 h-12 text-primary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                            @endif
                            
                            <!-- Badge public/privé -->
                            @if($collection->is_public)
                            <span class="absolute top-3 right-3 bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                                Public
                            </span>
                            @endif
                        </div>

                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 group-hover:text-[#CC5A00] transition-colors">
                                {{ $collection->name }}
                            </h3>
                            @if($collection->description)
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $collection->description }}</p>
                            @endif
                            <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    {{ $collection->favorites_count }} favori(s)
                                </span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Modal création -->
    <div id="createModal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-label="Nouvelle collection">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('createModal').classList.add('hidden')"></div>
            
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Nouvelle collection</h3>
                
                <form action="{{ route('collections.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                            <input type="text" name="name" id="name" required
                                   class="w-full rounded-xl border-gray-300 focus:border-[#F16A00] focus:ring-[#F16A00]"
                                   placeholder="Ex: Vacances à Cocody">
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" id="description" rows="3"
                                      class="w-full rounded-xl border-gray-300 focus:border-[#F16A00] focus:ring-[#F16A00]"
                                      placeholder="Description de la collection..."></textarea>
                        </div>

                        <div>
                            <label for="cover_image" class="block text-sm font-medium text-gray-700 mb-1">Image de couverture</label>
                            <input type="file" name="cover_image" id="cover_image" accept="image/*"
                                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#FFF4EB] file:text-[#A34700] hover:file:bg-[#FFE7D1]">
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_public" id="is_public" value="1"
                                   class="rounded border-gray-300 text-[#CC5A00] focus:ring-[#F16A00]">
                            <label for="is_public" class="text-sm text-gray-700">Rendre cette collection publique</label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                                class="px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-[#CC5A00] text-white rounded-xl hover:bg-[#A34700] transition-colors">
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
