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
            {{-- Тікет 4.1 дає лише навігацію на цю сторінку. Список інгредієнтів
                 із редагованими кількостями — тікет 4.2; атомарне списання комори
                 — 4.3; перехід статусу рецепту в `cooked` — 4.4. --}}
            <div class="rounded-xl border border-beige bg-cream px-5 py-6 shadow-sm">
                <p class="font-medium text-ink">Підтвердження списання — незабаром.</p>
                <p class="mt-1 text-sm text-muted">
                    Тут зʼявиться список інгредієнтів рецепту з редагованими кількостями,
                    які буде списано з вашої комори.
                </p>
            </div>

            <div class="mt-6">
                <a href="{{ route('recipes.show', $recipe) }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 border border-brand text-brand font-semibold rounded-xl hover:bg-brand/10 transition">
                    ← Назад до рецепту
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
