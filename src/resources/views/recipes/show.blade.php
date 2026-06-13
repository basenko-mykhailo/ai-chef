<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="font-semibold text-xl text-ink leading-tight truncate">{{ $recipe->name ?: 'Рецепт' }}</h2>
                <p class="text-sm text-muted">Згенеровано {{ $recipe->created_at?->format('d.m.Y H:i') }}</p>
            </div>
            @if ($recipe->status === 'cooked')
                <span class="shrink-0 inline-flex items-center gap-1 rounded-full bg-brand/10 text-brand px-3 py-1 text-sm font-medium">
                    🍳 Приготовано{{ $recipe->cooked_at ? ' ' . $recipe->cooked_at->format('d.m.Y') : '' }}
                </span>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($recipe->generation_status !== \App\Enums\GenerationStatus::Completed)
                <div class="rounded-xl border border-amber-300 bg-amber-50 px-5 py-6 text-amber-800">
                    <p class="font-medium">Рецепт ще не готовий.</p>
                    <p class="mt-1 text-sm">Статус: {{ $recipe->generation_status->label() }}.</p>
                    @if ($recipe->generation_error)
                        <p class="mt-2 text-sm">{{ $recipe->generation_error }}</p>
                        <a href="{{ route('recipes.create') }}" class="mt-3 inline-block text-brand font-medium hover:underline">Спробувати ще раз</a>
                    @endif
                </div>
            @else
                @if (session('recipe-favorite-flash'))
                    <div class="mb-4 rounded-md border border-brand/30 bg-brand/10 px-4 py-3 text-sm text-brand">
                        {{ session('recipe-favorite-flash') }}
                    </div>
                @endif

                @if ($recipe->description)
                    <p class="text-ink">{{ $recipe->description }}</p>
                @endif
                <p class="mt-1 text-sm text-muted">Порцій: {{ $recipe->servings ?? '—' }}</p>

                {{-- Інгредієнти --}}
                <div class="mt-6 bg-cream border border-beige rounded-xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 bg-brand text-cream font-medium">Інгредієнти</div>
                    <ul class="divide-y divide-beige">
                        @forelse ($recipe->ingredients_json ?? [] as $ingredient)
                            <li class="px-5 py-2.5 flex items-center justify-between gap-4 bg-white/50">
                                <span class="min-w-0 font-medium text-ink truncate">
                                    {{ $ingredient['name'] }}
                                    @if (($ingredient['in_pantry'] ?? false))
                                        <span class="ml-2 text-xs text-brand">є в коморі</span>
                                    @else
                                        <span class="ml-2 text-xs text-amber-700">треба купити</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-ink font-semibold">{{ $ingredient['quantity'] }} <span class="text-muted font-normal">{{ $ingredient['unit'] }}</span></span>
                            </li>
                        @empty
                            <li class="px-5 py-3 text-sm text-muted bg-white/50">Інгредієнти не вказані.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Кроки --}}
                <div class="mt-6 bg-cream border border-beige rounded-xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 bg-brand text-cream font-medium">Приготування</div>
                    @if (!empty($recipe->steps_json))
                        <ol class="list-decimal px-9 py-4 space-y-2 text-ink">
                            @foreach ($recipe->steps_json as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                    @else
                        <p class="px-5 py-3 text-sm text-muted">Кроки не вказані.</p>
                    @endif
                </div>

                {{-- КБЖУ --}}
                @php($kbju = $recipe->kbju_json ?? [])
                <div class="mt-6 bg-cream border border-beige rounded-xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 bg-brand text-cream font-medium">КБЖУ (на порцію)</div>
                    <div class="grid grid-cols-4 divide-x divide-beige text-center">
                        <div class="px-3 py-4"><div class="text-lg font-semibold text-ink">{{ $kbju['kcal'] ?? '—' }}</div><div class="text-xs text-muted">ккал</div></div>
                        <div class="px-3 py-4"><div class="text-lg font-semibold text-ink">{{ $kbju['protein'] ?? '—' }}</div><div class="text-xs text-muted">білки, г</div></div>
                        <div class="px-3 py-4"><div class="text-lg font-semibold text-ink">{{ $kbju['fat'] ?? '—' }}</div><div class="text-xs text-muted">жири, г</div></div>
                        <div class="px-3 py-4"><div class="text-lg font-semibold text-ink">{{ $kbju['carbs'] ?? '—' }}</div><div class="text-xs text-muted">вуглеводи, г</div></div>
                    </div>
                </div>

                {{-- Панель дій --}}
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    {{-- «Приготовано»: повний флоу (підтвердження → списання комори) — тікет 4.1. --}}
                    @if ($recipe->status === 'cooked')
                        <button type="button" disabled
                                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-beige text-muted font-semibold rounded-xl cursor-not-allowed">
                            🍳 Вже приготовано
                        </button>
                    @else
                        <div class="flex-1">
                            <button type="button" disabled title="Списання комори буде доступне незабаром"
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand/60 text-white font-semibold rounded-xl cursor-not-allowed">
                                🍳 Приготовано
                            </button>
                            <p class="mt-1 text-center text-xs text-muted">Списання комори — незабаром</p>
                        </div>
                    @endif

                    {{-- «В обране»: мінімальний тогл (тікет 3.10); AJAX-серце в історії — тікет 5.3. --}}
                    <form method="post" action="{{ route('recipes.favorite', $recipe) }}" class="flex-1">
                        @csrf
                        @method('PATCH')
                        @if ($recipe->is_favorite)
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand text-cream font-semibold rounded-xl shadow-sm hover:opacity-90 transition">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                В обраному
                            </button>
                        @else
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 border border-brand text-brand font-semibold rounded-xl hover:bg-brand/10 transition">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                В обране
                            </button>
                        @endif
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
