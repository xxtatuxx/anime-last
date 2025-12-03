<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- SEO BASIC -->
        <title inertia>{{ $title ?? 'Anime Last - مشاهدة الأنمي' }}</title>
        <meta name="description" content="{{ $description ?? 'Anime Last — موقع متخصص لمشاهدة وتحميل الأنمي أونلاين بجودة عالية وبث سريع جداً.' }}">
        <meta name="robots" content="index, follow">

        <!-- Canonical -->
        <link rel="canonical" href="{{ url()->current() }}">

        <!-- Open Graph (Social Media SEO) -->
        <meta property="og:title" content="{{ $title ?? 'Anime Last - مشاهدة الأنمي' }}">
        <meta property="og:description" content="{{ $description ?? 'Anime Last — موقع متخصص لمشاهدة وتحميل الأنمي أونلاين بجودة عالية وبث سريع جداً.' }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $image ?? asset('logo.png') }}">

        <!-- Theme Auto Detection -->
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';
                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        <!-- Background Colors -->
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <!-- Arabic Font -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap&subset=arabic" rel="stylesheet">

        <style>
            html, body, * {
                font-family: 'Roboto', sans-serif !important;
            }
        </style>

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>

    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
