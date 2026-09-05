<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Mauricio Masias — Full-Stack Developer. PHP, TypeScript, Vue, React, Next.js." />

    <title inertia>{{ config('app.name', 'Masias') }}</title>

    @php
        $gtmContainerId = config('analytics.gtm_container_id');
        $analyticsConsentGranted = request()->cookie('cookie_consent') === 'accepted';
    @endphp

    {{--
        Google Consent Mode v2 defaults. These must run before any Google tag,
        so nothing is stored until the visitor has actually chosen.
    --}}
    <script>
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };

        gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
            functionality_storage: 'granted',
            security_storage: 'granted',
            wait_for_update: 500
        });

        window.__gtmContainerId = @json($gtmContainerId);
    </script>

    @if ($gtmContainerId && $analyticsConsentGranted)
        {{--
            Only loaded once consent is on record. Tag Manager is not injected
            at all beforehand, so no request reaches Google and no cookie is
            written until the visitor opts in.
        --}}
        <script>
            gtag('consent', 'update', {
                ad_storage: 'granted',
                ad_user_data: 'granted',
                ad_personalization: 'granted',
                analytics_storage: 'granted'
            });
        </script>

        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.id='gtm-script';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',@json($gtmContainerId));</script>
        <!-- End Google Tag Manager -->
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />

    @vite('resources/js/app.ts')
    @inertiaHead
</head>
<body class="antialiased bg-[#080808] text-white">
    @if ($gtmContainerId && $analyticsConsentGranted)
        {{-- Without JavaScript the visitor cannot give consent, so this only
             renders for someone who already has. --}}
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmContainerId }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    @endif

    @inertia
</body>
</html>
