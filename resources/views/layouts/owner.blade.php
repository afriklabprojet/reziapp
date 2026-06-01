@extends('layouts.app')

@section('content')
    <div class="min-h-[calc(100vh-4rem)] bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <div class="lg:flex lg:gap-8">
                {{-- Sidebar --}}
                <x-owner-sidebar :active="$sidebarActive ?? ''" />

                {{-- Main Content --}}
                <div class="flex-1 min-w-0">
                    {{-- Bannière vérification d'identité (restriction) --}}
                    @if (session('warning'))
                        <div class="mb-4 bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
                            <div class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900">{{ session('warning') }}</p>
                                <a href="{{ route('verification.dashboard') }}"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-[#CC5A00] hover:text-[#A34700] mt-1">
                                    Vérifier maintenant
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endif
                    @yield('owner-content')
                </div>
            </div>
        </div>
    </div>
@endsection

@stack('owner-styles')
@stack('owner-scripts')
