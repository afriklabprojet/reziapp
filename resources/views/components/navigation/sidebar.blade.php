@props([
    'items' => [],
    'title' => null,
    'logo' => true,
])

<aside {{ $attributes->merge(['class' => 'w-64 bg-white border-r border-gray-200 min-h-screen flex flex-col']) }}>
    {{-- Header / Logo --}}
    @if($logo)
        <div class="h-16 flex items-center px-6 border-b border-gray-100">
            <a href="{{ route('home') }}" class="flex items-center">
                <x-application-logo size="default" />
            </a>
        </div>
    @endif
    
    {{-- Navigation --}}
    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
        @foreach($items as $section)
            {{-- Section title --}}
            @if(isset($section['title']))
                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-4 first:mt-0">
                    {{ $section['title'] }}
                </div>
            @endif
            
            {{-- Section items --}}
            @foreach($section['items'] ?? [] as $item)
                @php
                    $isActive = isset($item['route']) && request()->routeIs($item['route'] . '*');
                    $hasChildren = !empty($item['children']);
                @endphp
                
                @if($hasChildren)
                    {{-- Collapsible menu item --}}
                    <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                        <button 
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2.5 text-sm rounded-xl transition-colors {{ $isActive ? 'bg-orange-50 text-orange-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                        >
                            <span class="flex items-center gap-3">
                                @if(isset($item['icon']))
                                    <svg class="w-5 h-5 {{ $isActive ? 'text-orange-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        {!! $item['icon'] !!}
                                    </svg>
                                @endif
                                <span>{{ $item['label'] }}</span>
                            </span>
                            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        
                        <div x-show="open" x-collapse class="mt-1 ml-8 space-y-1">
                            @foreach($item['children'] as $child)
                                @php
                                    $childActive = isset($child['route']) && request()->routeIs($child['route']);
                                @endphp
                                <a 
                                    href="{{ isset($child['route']) ? route($child['route']) : ($child['url'] ?? '#') }}"
                                    class="block px-3 py-2 text-sm rounded-lg transition-colors {{ $childActive ? 'text-orange-600 bg-orange-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}"
                                >
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    {{-- Simple menu item --}}
                    <a 
                        href="{{ isset($item['route']) ? route($item['route']) : ($item['url'] ?? '#') }}"
                        class="flex items-center gap-3 px-3 py-2.5 text-sm rounded-xl transition-colors {{ $isActive ? 'bg-orange-50 text-orange-600 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                    >
                        @if(isset($item['icon']))
                            <svg class="w-5 h-5 {{ $isActive ? 'text-orange-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                {!! $item['icon'] !!}
                            </svg>
                        @endif
                        <span>{{ $item['label'] }}</span>
                        
                        @if(isset($item['badge']))
                            <span class="ml-auto px-2 py-0.5 text-xs rounded-full {{ $isActive ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-600' }}">
                                {{ $item['badge'] }}
                            </span>
                        @endif
                    </a>
                @endif
            @endforeach
        @endforeach
    </nav>
    
    {{-- Footer / User info --}}
    @auth
        <div class="p-4 border-t border-gray-100">
            <div class="flex items-center gap-3 px-2">
                <x-ui.avatar 
                    :src="Auth::user()->getAvatarUrl()" 
                    :alt="Auth::user()->name" 
                    size="sm" 
                />
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2 text-gray-400 hover:text-red-500 transition-colors rounded-lg hover:bg-gray-100" title="Déconnexion">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    @endauth
</aside>
