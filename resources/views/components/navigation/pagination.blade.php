@props([
    'paginator' => null,
    'onEachSide' => 1,
])

@if($paginator && $paginator->hasPages())
    <nav {{ $attributes->merge(['class' => 'flex items-center justify-between']) }} aria-label="Pagination">
        {{-- Mobile view --}}
        <div class="flex flex-1 justify-between sm:hidden">
            @if($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-200 cursor-not-allowed rounded-xl">
                    Précédent
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    Précédent
                </a>
            @endif

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    Suivant
                </a>
            @else
                <span class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-200 cursor-not-allowed rounded-xl">
                    Suivant
                </span>
            @endif
        </div>

        {{-- Desktop view --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    Affichage de
                    <span class="font-medium text-gray-700">{{ $paginator->firstItem() ?? 0 }}</span>
                    à
                    <span class="font-medium text-gray-700">{{ $paginator->lastItem() ?? 0 }}</span>
                    sur
                    <span class="font-medium text-gray-700">{{ $paginator->total() }}</span>
                    résultats
                </p>
            </div>

            <div>
                <span class="isolate inline-flex rounded-xl shadow-sm">
                    {{-- Previous --}}
                    @if($paginator->onFirstPage())
                        <span class="relative inline-flex items-center rounded-l-xl px-3 py-2 text-gray-300 bg-white border border-gray-200 cursor-not-allowed">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center rounded-l-xl px-3 py-2 text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 hover:text-orange-500 transition-colors">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Page numbers --}}
                    @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                        @if($page == $paginator->currentPage())
                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-orange-500 border border-orange-500 focus:z-20">
                                {{ $page }}
                            </span>
                        @elseif($page == 1 || $page == $paginator->lastPage() || abs($page - $paginator->currentPage()) <= $onEachSide)
                            <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 hover:text-orange-500 transition-colors focus:z-20">
                                {{ $page }}
                            </a>
                        @elseif(abs($page - $paginator->currentPage()) == $onEachSide + 1)
                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200">
                                ...
                            </span>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center rounded-r-xl px-3 py-2 text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 hover:text-orange-500 transition-colors">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span class="relative inline-flex items-center rounded-r-xl px-3 py-2 text-gray-300 bg-white border border-gray-200 cursor-not-allowed">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
