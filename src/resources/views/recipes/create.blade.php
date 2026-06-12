<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-ink leading-tight">Згенерувати рецепт</h2>
            <p class="text-sm text-muted">Оберіть, для кого готуємо, і AI підбере рецепт із ваших запасів</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status') === 'recipe-generation-pending')
                <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                     x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 5000)" x-transition>
                    Генерація рецептів буде доступна незабаром.
                </div>
            @endif

            <form method="post" action="{{ route('recipes.generate') }}">
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
                        <button type="submit"
                                class="inline-flex items-center justify-center px-8 py-4 bg-brand text-white text-lg font-semibold rounded-xl shadow-sm hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 transition">
                            🍳 Згенерувати рецепт
                        </button>
                    @endif
                    <p class="mt-3 text-xs text-muted">Можна згенерувати до 10 рецептів на годину.</p>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
