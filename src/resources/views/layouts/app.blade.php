<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AI Chef') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col bg-gray-100">
            @include('layouts.navigation')

            <!-- AI safety disclaimer — non-negotiable per product/safety requirement -->
            <div class="bg-amber-50 border-b border-amber-200 text-amber-900" role="note" aria-label="AI safety disclaimer">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-start gap-2 text-sm">
                    <svg class="shrink-0 h-5 w-5 mt-0.5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <span>
                        <strong class="font-semibold">AI може помилитися</strong> — самостійно перевіряйте склад страв на алергени та інші дієтичні обмеження.
                    </span>
                </div>
            </div>

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 mt-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm text-gray-500">
                    <div>
                        &copy; {{ date('Y') }} {{ config('app.name', 'AI Chef') }}
                    </div>
                    <div class="text-gray-400">
                        Зроблено для хакатону · PHP · Laravel · Claude API
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
