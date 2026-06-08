<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'AI Chef') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <div class="min-h-screen flex flex-col items-center justify-center px-6">
            <main class="w-full max-w-md text-center">
                <h1 class="text-4xl font-semibold tracking-tight">{{ config('app.name', 'AI Chef') }}</h1>
                <p class="mt-3 text-gray-600">
                    Рецепти з вашої комори з урахуванням смаків та алергій родини.
                </p>

                <div class="mt-8 flex items-center justify-center gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                            Перейти до кабінету
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                            Увійти
                        </a>
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-50">
                            Зареєструватися
                        </a>
                    @endauth
                </div>
            </main>

            <footer class="mt-10 text-xs text-gray-500 text-center max-w-md">
                AI може помилятися — самостійно перевіряйте склад страв на алергени.
            </footer>
        </div>
    </body>
</html>
