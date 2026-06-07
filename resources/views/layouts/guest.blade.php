<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($cspNonce = \Illuminate\Support\Facades\Vite::cspNonce())

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts: Plus Jakarta Sans (Airbnb Cereal / Circular fallback) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php($clarityId = config('services.clarity.id'))
    @if (app()->isProduction() && filled($clarityId))
    <!-- Microsoft Clarity -->
    <script type="text/javascript" nonce="{{ $cspNonce }}">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", {{ \Illuminate\Support\Js::encode($clarityId) }});
    </script>
    @endif
</head>

<body class="font-sans text-[#0F0F0F] antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#F2F2F2]">
        <div>
            <a href="/">
                <x-application-logo size="large" />
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-4 sm:px-6 py-4 bg-white overflow-hidden sm:rounded-md" style="box-shadow: rgba(0,0,0,0.02) 0 0 0 1px, rgba(0,0,0,0.04) 0 2px 6px, rgba(0,0,0,0.1) 0 4px 8px;">
            {{ $slot }}
        </div>
    </div>
</body>

</html>
