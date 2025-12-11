<!DOCTYPE html>
<html lang="{{ $locale ?? str_replace('_', '-', app()->getLocale()) }}" dir="{{ $direction ?? 'ltr' }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        {{-- Also handle locale and direction from page props --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }

                // Handle locale and direction from Inertia page props
                // This will be updated by Vue after hydration
                window.addEventListener('DOMContentLoaded', function() {
                    // Check if Inertia page data has localization info
                    const pageData = document.getElementById('app')?.dataset?.page;
                    if (pageData) {
                        try {
                            const props = JSON.parse(pageData);
                            if (props.props?.localization) {
                                const { locale, direction } = props.props.localization;
                                if (locale) document.documentElement.lang = locale;
                                if (direction) document.documentElement.dir = direction;
                            }
                        } catch (e) {}
                    }
                });
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        {{-- Also includes v-cloak support to prevent flash of unstyled content --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }

            /* Hide elements with v-cloak until Vue has mounted */
            [v-cloak] {
                display: none !important;
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
