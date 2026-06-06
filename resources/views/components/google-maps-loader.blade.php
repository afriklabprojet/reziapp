@props([
    'stack' => 'scripts',
    'libraries' => 'places',
])

@php
    $apiKey = config('services.google_maps.key');
    $nonce = \Illuminate\Support\Facades\Vite::cspNonce();
    $librariesParam = collect(explode(',', is_array($libraries) ? implode(',', $libraries) : $libraries))
        ->map(fn ($library) => trim($library))
        ->filter()
        ->unique()
        ->implode(',');

    $query = http_build_query(array_filter([
        'key' => $apiKey,
        'libraries' => $librariesParam ?: null,
        'callback' => '__googleMapsOnLoad',
        'loading' => 'async',
    ]));
@endphp

@if (filled($apiKey))
    @once
        @if ($stack === 'owner-scripts')
            @push('owner-scripts')
                <script nonce="{{ $nonce }}">
                    globalThis.__googleMapsCallbacks = globalThis.__googleMapsCallbacks || [];
                    globalThis.__googleMapsOnLoad = function () {
                        const callbacks = Array.isArray(globalThis.__googleMapsCallbacks)
                            ? [...new Set(globalThis.__googleMapsCallbacks)]
                            : [];

                        callbacks.forEach((callback) => {
                            if (typeof callback === 'function') {
                                callback();
                            }
                        });
                    };

                    if (!globalThis.__googleMapsLoaderRequested) {
                        globalThis.__googleMapsLoaderRequested = true;

                        const script = document.createElement('script');
                        script.src = @js('https://maps.googleapis.com/maps/api/js?' . $query);
                        script.async = true;
                        script.defer = true;
                        document.head.appendChild(script);
                    }
                </script>
            @endpush
        @else
            @push('scripts')
                <script nonce="{{ $nonce }}">
                    globalThis.__googleMapsCallbacks = globalThis.__googleMapsCallbacks || [];
                    globalThis.__googleMapsOnLoad = function () {
                        const callbacks = Array.isArray(globalThis.__googleMapsCallbacks)
                            ? [...new Set(globalThis.__googleMapsCallbacks)]
                            : [];

                        callbacks.forEach((callback) => {
                            if (typeof callback === 'function') {
                                callback();
                            }
                        });
                    };

                    if (!globalThis.__googleMapsLoaderRequested) {
                        globalThis.__googleMapsLoaderRequested = true;

                        const script = document.createElement('script');
                        script.src = @js('https://maps.googleapis.com/maps/api/js?' . $query);
                        script.async = true;
                        script.defer = true;
                        document.head.appendChild(script);
                    }
                </script>
            @endpush
        @endif
    @endonce
@endif
