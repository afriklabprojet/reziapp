@extends('layouts.app')

@section('content')
    <div class="min-h-[calc(100vh-4rem)] bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <div class="lg:flex lg:gap-8">
                {{-- Sidebar --}}
                <x-client-sidebar :active="$sidebarActive ?? ''" />

                {{-- Main Content --}}
                <div class="flex-1 min-w-0">
                    @yield('client-content')
                </div>
            </div>
        </div>
    </div>
@endsection
