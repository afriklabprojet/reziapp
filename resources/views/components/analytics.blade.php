{{-- Composant Analytics & Tracking --}}
@props([
    'pageType' => 'page', // page, residence, search, booking
    'residence' => null,
    'searchQuery' => null,
    'booking' => null,
])

@php
    // Données de tracking structurées
    $trackingData = [
        'page_type' => $pageType,
        'page_url' => request()->url(),
        'user_id' => auth()->id(),
        'session_id' => session()->getId(),
        'timestamp' => now()->toIso8601String(),
    ];

    if ($residence) {
        $trackingData['residence'] = [
            'id' => $residence->id,
            'title' => $residence->title ?? $residence->name,
            'commune' => $residence->commune,
            'price' => $residence->price_per_month ?? $residence->price_per_day,
            'type' => $residence->type,
        ];
    }

    if ($searchQuery) {
        $trackingData['search'] = [
            'query' => $searchQuery,
            'filters' => request()->only(['commune', 'min_price', 'max_price', 'type', 'bedrooms']),
            'results_count' => $results_count ?? 0,
        ];
    }

    if ($booking) {
        $trackingData['booking'] = [
            'id' => $booking->id,
            'residence_id' => $booking->residence_id,
            'total' => $booking->total_amount,
        ];
    }
@endphp

{{-- Google Analytics 4 --}}
@if(config('services.google.analytics_id'))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google.analytics_id') }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ config('services.google.analytics_id') }}', {
        page_title: document.title,
        page_location: window.location.href,
        @auth
        user_id: '{{ auth()->id() }}',
        @endauth
    });

    // Custom events selon le type de page
    @if($pageType === 'residence' && $residence)
    gtag('event', 'view_item', {
        currency: 'XOF',
        value: {{ $residence->price_per_month ?? $residence->price_per_day ?? 0 }},
        items: [{
            item_id: '{{ $residence->id }}',
            item_name: '{{ addslashes($residence->title ?? $residence->name) }}',
            item_category: '{{ $residence->type }}',
            item_category2: '{{ $residence->commune }}',
            price: {{ $residence->price_per_month ?? $residence->price_per_day ?? 0 }},
        }]
    });
    @endif

    @if($pageType === 'search')
    gtag('event', 'search', {
        search_term: '{{ addslashes($searchQuery ?? '') }}'
    });
    @endif

    @if($pageType === 'booking' && $booking)
    gtag('event', 'begin_checkout', {
        currency: 'XOF',
        value: {{ $booking->total_amount ?? 0 }},
        items: [{
            item_id: '{{ $booking->residence_id }}',
            quantity: 1,
            price: {{ $booking->total_amount ?? 0 }}
        }]
    });
    @endif
</script>
@endif

{{-- Meta Pixel (Facebook) --}}
@if(config('services.facebook.pixel_id'))
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    
    fbq('init', '{{ config('services.facebook.pixel_id') }}');
    fbq('track', 'PageView');

    @if($pageType === 'residence' && $residence)
    fbq('track', 'ViewContent', {
        content_ids: ['{{ $residence->id }}'],
        content_type: 'product',
        content_name: '{{ addslashes($residence->title ?? $residence->name) }}',
        content_category: '{{ $residence->commune }}',
        value: {{ $residence->price_per_month ?? $residence->price_per_day ?? 0 }},
        currency: 'XOF'
    });
    @endif

    @if($pageType === 'search')
    fbq('track', 'Search', {
        search_string: '{{ addslashes($searchQuery ?? '') }}',
        content_category: 'residences'
    });
    @endif
</script>
<noscript>
    <img loading="lazy" height="1" width="1" style="display:none" 
         src="https://www.facebook.com/tr?id={{ config('services.facebook.pixel_id') }}&ev=PageView&noscript=1"/ alt="">
</noscript>
@endif

{{-- REZI Internal Analytics --}}
<script>
window.ReziAnalytics = {
    data: {!! json_encode($trackingData) !!},
    
    track(event, properties = {}) {
        const payload = {
            event,
            properties: { ...this.data, ...properties },
            timestamp: new Date().toISOString()
        };

        // Envoyer au serveur
        if (navigator.sendBeacon) {
            navigator.sendBeacon('/api/analytics/track', JSON.stringify(payload));
        } else {
            fetch('/api/analytics/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                keepalive: true
            });
        }

        // Console en dev
        @if(config('app.debug'))
        console.log('[ReziAnalytics]', event, properties);
        @endif
    },

    // Tracking des clics
    trackClick(element, eventName, properties = {}) {
        element.addEventListener('click', () => {
            this.track(eventName, properties);
        });
    },

    // Tracking du scroll
    trackScroll(thresholds = [25, 50, 75, 100]) {
        let tracked = new Set();
        window.addEventListener('scroll', () => {
            const scrollPercent = Math.round(
                (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100
            );
            thresholds.forEach(threshold => {
                if (scrollPercent >= threshold && !tracked.has(threshold)) {
                    tracked.add(threshold);
                    this.track('scroll_depth', { depth: threshold });
                }
            });
        }, { passive: true });
    },

    // Tracking du temps passé
    trackTimeOnPage() {
        const start = Date.now();
        window.addEventListener('beforeunload', () => {
            const duration = Math.round((Date.now() - start) / 1000);
            this.track('time_on_page', { duration_seconds: duration });
        });
    },

    // Initialisation
    init() {
        // Track page view
        this.track('page_view');
        
        // Track scroll depth
        this.trackScroll();
        
        // Track time on page
        this.trackTimeOnPage();

        // Auto-track des liens de contact
        document.querySelectorAll('[data-track-contact]').forEach(el => {
            this.trackClick(el, 'contact_click', {
                type: el.dataset.trackContact,
                residence_id: el.dataset.residenceId
            });
        });

        // Auto-track des boutons favoris
        document.querySelectorAll('[data-track-favorite]').forEach(el => {
            this.trackClick(el, 'favorite_toggle', {
                residence_id: el.dataset.residenceId
            });
        });

        // Auto-track des partages
        document.querySelectorAll('[data-track-share]').forEach(el => {
            this.trackClick(el, 'share_click', {
                platform: el.dataset.trackShare,
                residence_id: el.dataset.residenceId
            });
        });
    }
};

// Auto-init
document.addEventListener('DOMContentLoaded', () => ReziAnalytics.init());
</script>

{{-- Hotjar (Session Recording) --}}
@if(config('services.hotjar.id') && app()->environment('production'))
<script>
    (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:{{ config('services.hotjar.id') }},hjsv:6};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
    })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
</script>
@endif

{{-- Microsoft Clarity --}}
@if(config('services.clarity.id') && app()->environment('production'))
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "{{ config('services.clarity.id') }}");
</script>
@endif
