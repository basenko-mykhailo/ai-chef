<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'AI Chef') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|oswald:500,600,700|playfair-display-sc:400&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-sand text-ink">
        {{-- Mobile-first single column (iPhone mockups). Centred on larger screens. --}}
        <div class="mx-auto w-full max-w-md bg-cream min-h-screen shadow-sm">

            {{-- Top bar --}}
            <header class="sticky top-0 z-20 flex items-center justify-between bg-ink px-4 py-3 text-cream">
                <a href="/" class="font-brand text-xl tracking-wide">AI&#8209;CHEF</a>
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-cream/90 hover:text-cream">Кабінет</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-cream/90 hover:text-cream">Увійти</a>
                @endauth
            </header>

            {{-- Hero --}}
            <section class="relative overflow-hidden bg-gradient-to-b from-brand to-[#0d2310] px-6 pb-10 pt-12 text-cream">
                <div class="pointer-events-none absolute -right-8 -top-8 text-[7rem] opacity-20 select-none">🥗</div>
                <p class="font-display text-sm font-semibold uppercase tracking-[0.18em] text-brand-accent-light">
                    Твій розумний помічник на кухні
                </p>
                <h1 class="mt-3 font-display text-5xl font-bold leading-none">AI&#8209;CHEF</h1>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-cream/80">
                    Готуй смачно з того, що вже є в холодильнику, завдяки силі штучного інтелекту.
                </p>

                <div class="mt-7 flex flex-col gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="inline-flex items-center justify-center rounded-lg bg-brand-accent px-5 py-3 text-sm font-semibold text-ink shadow-sm transition hover:bg-brand-accent-light">
                            Перейти до кабінету
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center justify-center rounded-lg bg-brand-accent px-5 py-3 text-sm font-semibold text-ink shadow-sm transition hover:bg-brand-accent-light">
                            Спробувати зараз
                        </a>
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center justify-center rounded-lg border border-cream/40 px-5 py-3 text-sm font-semibold text-cream transition hover:bg-cream/10">
                            Увійти
                        </a>
                    @endauth
                </div>
            </section>

            {{-- Advantages --}}
            <section class="bg-ink px-4 py-8 text-cream">
                <h2 class="mb-5 text-xl font-bold">Наші переваги</h2>
                <div class="grid grid-cols-3 gap-3">
                    @php
                        $advantages = [
                            ['🥕', 'Розумний контроль продуктів', 'Автоматично відстежуємо запаси та пропонуємо рецепти, щоб нічого не викидати'],
                            ['🚚', 'Миттєва доставка', 'Замовляйте відсутні інгредієнти для обраної страви в один клік через партнерів'],
                            ['🤖', 'Персональний кулінарний AI', 'Адаптуємо меню під ваші смаки, дієти та вподобання за допомогою ШІ'],
                        ];
                    @endphp
                    @foreach ($advantages as [$icon, $title, $desc])
                        <div class="flex flex-col gap-2 rounded-lg bg-beige p-3 text-ink">
                            <span class="text-2xl leading-none">{{ $icon }}</span>
                            <span class="text-[0.7rem] font-semibold leading-tight text-ink">{{ $title }}</span>
                            <span class="text-[0.6rem] leading-snug text-muted">{{ $desc }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- How it works --}}
            <section class="px-6 py-10">
                <h2 class="mb-6 text-center text-xl font-bold text-brand">Як це працює?</h2>
                <ol class="space-y-5">
                    @php
                        $steps = [
                            ['📸', 'Введи або скануй продукти'],
                            ['✨', 'Наш AI створює рецепт'],
                            ['🍽️', 'Насолоджуйся покроковим приготуванням'],
                        ];
                    @endphp
                    @foreach ($steps as $i => [$icon, $label])
                        <li class="flex items-center gap-4">
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-sand text-2xl shadow-sm">{{ $icon }}</span>
                            <div>
                                <span class="block text-xs font-semibold uppercase tracking-wide text-brand-accent">Крок {{ $i + 1 }}</span>
                                <span class="text-sm font-medium text-ink">{{ $label }}</span>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Why AI --}}
            <section class="mx-4 mb-10 flex items-center gap-4 rounded-2xl bg-ink p-5 text-cream">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl bg-brand text-4xl">👩‍🍳</div>
                <div>
                    <h2 class="text-lg font-bold leading-tight">Чому AI — це майбутнє вашої кухні?</h2>
                    <p class="mt-2 text-xs leading-relaxed text-cream/75">
                        Штучний інтелект — це персональний кулінарний помічник. Він швидко аналізує інгредієнти,
                        допомагає обрати страву та робить приготування легким і приємним.
                    </p>
                </div>
            </section>

            {{-- Testimonials --}}
            <section class="px-4 pb-10">
                <h2 class="mb-4 text-xl font-bold text-brand">Відгуки користувачів</h2>
                <div class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2">
                    @php
                        $reviews = [
                            ['Олександр', 'Зручно і дуже економно. Тепер викидаю продукти в рази рідше, а рецепти виходять просто ресторанні!'],
                            ['Софія Мельник', 'Це мій особистий помічник на кухні. AI-Chef знаходить нові ідеї там, де я бачу лише порожній холодильник.'],
                            ['Оксана Шевченко', 'Не вірилося, що це працює, поки сама не спробувала. Тепер готую з натхненням щодня!'],
                            ['Вікторія Ткачук', 'Це справжній прорив для тих, хто любить творчий підхід до їжі. Ніяких зайвих витрат, лише смачні рішення.'],
                        ];
                    @endphp
                    @foreach ($reviews as [$name, $quote])
                        <figure class="flex w-56 shrink-0 snap-start flex-col gap-3 rounded-xl bg-muted p-4 text-cream">
                            <blockquote class="text-xs leading-relaxed text-cream/90">«{{ $quote }}»</blockquote>
                            <figcaption class="mt-auto flex items-center justify-between">
                                <span class="text-sm font-semibold">{{ $name }}</span>
                                <span class="text-xs text-[#dcd859]" aria-label="5 з 5">★★★★★</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </section>

            {{-- Final CTA --}}
            <section class="mx-4 mb-10 rounded-2xl bg-gradient-to-br from-brand to-[#0d2310] p-6 text-center text-cream">
                <h2 class="font-display text-2xl font-bold uppercase">Спробуй магію в дії</h2>
                <p class="mx-auto mt-2 max-w-xs text-sm text-cream/80">
                    Додайте продукти: сфотографуйте холодильник або впишіть інгредієнти — ШІ проаналізує й запропонує страви.
                </p>
                <a href="{{ Auth::check() ? url('/dashboard') : route('register') }}"
                   class="mt-5 inline-flex items-center justify-center rounded-lg bg-brand-accent px-6 py-3 text-sm font-semibold text-ink shadow-sm transition hover:bg-brand-accent-light">
                    {{ Auth::check() ? 'Перейти до кабінету' : 'Створити акаунт' }}
                </a>
            </section>

            {{-- AI safety disclaimer — non-negotiable per product/safety requirement --}}
            <div class="mx-4 mb-8 flex items-start gap-2 rounded-lg border border-beige bg-sand px-4 py-3 text-xs text-muted"
                 role="note" aria-label="AI safety disclaimer">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
                <span><strong class="font-semibold text-ink">AI може помилятися</strong> — самостійно перевіряйте склад страв на алергени та інші дієтичні обмеження.</span>
            </div>

            {{-- Footer --}}
            <footer class="bg-ink px-6 py-8 text-cream">
                <p class="font-brand text-lg">AI&#8209;CHEF</p>
                <nav class="mt-4 grid grid-cols-2 gap-2 text-xs text-cream/70">
                    <a href="#" class="hover:text-cream">Як це працює</a>
                    <a href="#" class="hover:text-cream">Швидкий пошук інгредієнтів</a>
                    <a href="#" class="hover:text-cream">Про AI-технології</a>
                    <a href="#" class="hover:text-cream">Відгуки клієнтів</a>
                </nav>
                <p class="mt-6 text-[0.65rem] text-cream/50">&copy; {{ date('Y') }} AI-Chef. Усі права захищені.</p>
            </footer>
        </div>
    </body>
</html>
