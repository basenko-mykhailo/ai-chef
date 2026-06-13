<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0">
            <h2 class="font-semibold text-xl text-ink leading-tight truncate">
                Приготувати: {{ $recipe->name ?: 'Рецепт' }}
            </h2>
            <p class="text-sm text-muted">Підтвердження списання з комори</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            {{-- Тікет 4.2: список інгредієнтів рецепту, що є в коморі (збіг за назвою +
                 одиницею), із редагованими кількостями. Атомарне списання — 4.3,
                 перехід статусу рецепту в `cooked` — 4.4 (поки сабміт нічого не мутує). --}}
            @if (!empty($matches))
                <p class="text-sm text-muted">
                    Перевірте кількості, які буде списано з комори, і підтвердьте.
                    Стейпли та інгредієнти, яких немає в коморі, тут не показані.
                </p>

                <form method="POST" action="{{ route('recipes.cook.store', $recipe) }}" class="mt-6">
                    @csrf

                    <div class="bg-cream border border-beige rounded-xl overflow-hidden shadow-sm">
                        <div class="px-5 py-3 bg-brand text-cream font-medium">Списати з комори</div>
                        <ul class="divide-y divide-beige">
                            @foreach ($matches as $match)
                                <li class="px-5 py-3 flex items-center justify-between gap-4 bg-white/50">
                                    <div class="min-w-0">
                                        <p class="font-medium text-ink truncate">{{ $match['name'] }}</p>
                                        <p class="text-xs text-muted">
                                            у коморі: {{ $match['pantry_quantity'] }} {{ $match['unit'] }}
                                        </p>
                                    </div>
                                    <div class="shrink-0 flex items-center gap-2">
                                        <input type="hidden"
                                               name="items[{{ $match['pantry_item_id'] }}][pantry_item_id]"
                                               value="{{ $match['pantry_item_id'] }}">
                                        <input type="number"
                                               name="items[{{ $match['pantry_item_id'] }}][quantity]"
                                               value="{{ $match['recipe_quantity'] }}"
                                               step="0.001" min="0"
                                               class="w-28 rounded-lg border-beige bg-white text-ink text-right focus:border-brand focus:ring-brand"
                                               aria-label="Кількість для списання: {{ $match['name'] }}">
                                        <span class="text-muted text-sm w-16">{{ $match['unit'] }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row gap-3">
                        <button type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand text-cream font-semibold rounded-xl shadow-sm hover:opacity-90 transition">
                            🍳 Підтвердити списання
                        </button>
                        <a href="{{ route('recipes.show', $recipe) }}"
                           class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 border border-brand text-brand font-semibold rounded-xl hover:bg-brand/10 transition">
                            ← Назад до рецепту
                        </a>
                    </div>
                </form>
            @else
                <div class="rounded-xl border border-beige bg-cream px-5 py-6 shadow-sm">
                    <p class="font-medium text-ink">Нема чого списувати.</p>
                    <p class="mt-1 text-sm text-muted">
                        Жоден інгредієнт рецепту не знайдено у вашій коморі (за збігом назви й одиниці).
                    </p>
                </div>

                <div class="mt-6">
                    <a href="{{ route('recipes.show', $recipe) }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 border border-brand text-brand font-semibold rounded-xl hover:bg-brand/10 transition">
                        ← Назад до рецепту
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
