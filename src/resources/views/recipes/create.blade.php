<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-ink leading-tight">Згенерувати рецепт</h2>
            <p class="text-sm text-muted">Оберіть, для кого готуємо, і AI підбере рецепт із ваших запасів</p>
        </div>
    </x-slot>

    <style>[x-cloak]{display:none!important}</style>

    <div class="py-10"
         x-data="{
            generating: false,
            failed: false,
            errorMessage: '',
            pollTimer: null,

            async start(event) {
                this.failed = false;
                this.errorMessage = '';
                this.generating = true;

                const form = event.target;

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: new FormData(form),
                    });

                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        throw new Error(data.message || 'Не вдалося розпочати генерацію.');
                    }

                    const data = await res.json();
                    this.poll(data.status_url);
                } catch (e) {
                    this.fail(e.message);
                }
            },

            poll(statusUrl) {
                this.pollTimer = setInterval(async () => {
                    try {
                        const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();

                        if (data.status === 'completed' && data.recipe) {
                            clearInterval(this.pollTimer);
                            window.location = data.recipe.show_url;
                        } else if (data.status === 'failed') {
                            clearInterval(this.pollTimer);
                            this.fail(data.error || 'Сталася помилка під час генерації.');
                        }
                    } catch (e) {
                        clearInterval(this.pollTimer);
                        this.fail('Сталася помилка мережі. Спробуйте ще раз.');
                    }
                }, 2000);
            },

            fail(message) {
                this.generating = false;
                this.failed = true;
                this.errorMessage = message;
            },
         }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div x-show="failed" x-cloak
                 class="mb-4 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                <span x-text="errorMessage"></span>
            </div>

            <form method="post" action="{{ route('api.recipes.generate') }}" x-on:submit.prevent="start($event)">
                @csrf

                {{-- Хто буде їсти --}}
                <div class="bg-cream border border-beige rounded-xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 bg-brand text-cream font-medium flex items-center justify-between">
                        <span>Для кого готуємо</span>
                        @if ($members->isNotEmpty())
                            <span class="text-sm opacity-80">{{ $members->count() }} чл.</span>
                        @endif
                    </div>

                    @if ($members->isEmpty())
                        <div class="px-5 py-6 text-sm text-ink">
                            <p>Рецепт буде згенеровано для вас, без додаткових обмежень.</p>
                            <p class="mt-1 text-muted">
                                Додайте
                                <a href="{{ route('family.create') }}" class="text-brand font-medium hover:underline">членів сім'ї</a>,
                                щоб AI враховував їхні алергії, дієти та вподобання.
                            </p>
                        </div>
                    @else
                        <ul class="divide-y divide-beige">
                            @foreach ($members as $member)
                                <li class="px-5 py-3 bg-white/50">
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" name="members[]" value="{{ $member->id }}" checked
                                               class="mt-1 rounded border-beige text-brand focus:ring-brand">
                                        <span class="min-w-0">
                                            <span class="block font-medium text-ink">{{ $member->name }}</span>
                                            @if ($member->allergies_and_diets)
                                                <span class="block text-xs text-red-600">Алергії/дієти: {{ $member->allergies_and_diets }}</span>
                                            @endif
                                            @if ($member->disliked_products)
                                                <span class="block text-xs text-amber-700">Не любить: {{ $member->disliked_products }}</span>
                                            @endif
                                            @if ($member->favorite_products)
                                                <span class="block text-xs text-brand">Улюблене: {{ $member->favorite_products }}</span>
                                            @endif
                                        </span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                        <p class="px-5 py-3 text-xs text-muted border-t border-beige">
                            Обмеження обраних членів сім'ї поєднуються — рецепт враховує всіх одразу.
                        </p>
                    @endif
                </div>

                {{-- Поточна комора --}}
                <div class="mt-6 bg-cream border border-beige rounded-xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 bg-brand text-cream font-medium flex items-center justify-between">
                        <span>Ваша комора</span>
                        <span class="text-sm opacity-80">{{ $pantry->count() }} поз.</span>
                    </div>

                    @if ($pantry->isEmpty())
                        <div class="text-center px-6 py-10">
                            <p class="text-ink font-medium">Комора порожня.</p>
                            <p class="mt-1 text-sm text-muted">Додайте продукти — без них AI не зможе скласти рецепт.</p>
                            <a href="{{ route('pantry.create') }}"
                               class="mt-4 inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-md hover:opacity-90 transition">
                                + Додати продукт
                            </a>
                        </div>
                    @else
                        <ul class="divide-y divide-beige">
                            @foreach ($pantry as $item)
                                <li class="px-5 py-2.5 flex items-center justify-between gap-4 bg-white/50">
                                    <span class="min-w-0 font-medium text-ink truncate">{{ $item->ingredient?->name ?? '—' }}</span>
                                    <span class="shrink-0 text-ink font-semibold">{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}<span class="ml-1 text-muted font-normal">{{ $item->unit->label() }}</span></span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Кнопка генерації --}}
                <div class="mt-8 text-center">
                    @if ($pantry->isEmpty())
                        <button type="submit" disabled
                                class="inline-flex items-center justify-center px-8 py-4 bg-beige text-muted text-lg font-semibold rounded-xl cursor-not-allowed">
                            🍳 Згенерувати рецепт
                        </button>
                    @else
                        <button type="submit" x-bind:disabled="generating"
                                class="inline-flex items-center justify-center px-8 py-4 bg-brand text-white text-lg font-semibold rounded-xl shadow-sm hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 transition disabled:opacity-60">
                            🍳 Згенерувати рецепт
                        </button>
                    @endif
                    <p class="mt-3 text-xs text-muted">Можна згенерувати до 10 рецептів на годину.</p>
                </div>
            </form>
        </div>

        {{-- Спіннер на час генерації (10+ сек) --}}
        <div x-show="generating" x-cloak
             class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-ink/60 backdrop-blur-sm">
            <svg class="animate-spin h-12 w-12 text-cream" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-4 text-cream font-medium">Генеруємо рецепт… Це може зайняти кілька секунд.</p>
        </div>
    </div>
</x-app-layout>
