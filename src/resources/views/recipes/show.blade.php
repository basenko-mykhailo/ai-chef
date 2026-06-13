<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-brand leading-tight truncate">{{ $recipe->name ?: 'Рецепт' }}</h2>
                <p class="mt-1 text-sm text-muted">Згенеровано {{ $recipe->created_at?->format('d.m.Y H:i') }}</p>
            </div>
            @if ($recipe->status === 'cooked')
                <span class="shrink-0 inline-flex items-center gap-1 rounded-full bg-brand/10 text-brand px-3 py-1 text-sm font-medium">
                    🍳 Приготовано{{ $recipe->cooked_at ? ' ' . $recipe->cooked_at->format('d.m.Y') : '' }}
                </span>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-md mx-auto px-4">
            @if ($recipe->generation_status === \App\Enums\GenerationStatus::Failed)
                {{-- Friendly error генерації (тікет 3.11) з явним retry → нова генерація. --}}
                <div class="rounded-2xl border border-red-300 bg-red-50 px-5 py-6 text-red-800">
                    <p class="font-medium">Не вдалося згенерувати рецепт.</p>
                    <p class="mt-1 text-sm">{{ $recipe->generation_error ?? 'Сталася помилка під час генерації.' }}</p>
                    <a href="{{ route('recipes.create') }}"
                       class="mt-4 inline-flex items-center px-5 py-2.5 bg-brand text-cream font-semibold rounded-lg shadow-sm hover:opacity-90 transition">
                        Спробувати ще раз
                    </a>
                </div>
            @elseif ($recipe->generation_status !== \App\Enums\GenerationStatus::Completed)
                <div class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-6 text-amber-800">
                    <p class="font-medium">Рецепт ще не готовий.</p>
                    <p class="mt-1 text-sm">Статус: {{ $recipe->generation_status->label() }}.</p>
                </div>
            @else
                @if (session('recipe-favorite-flash'))
                    <div class="mb-4 rounded-lg border border-brand/30 bg-brand/10 px-4 py-3 text-sm text-brand">
                        {{ session('recipe-favorite-flash') }}
                    </div>
                @endif

                {{-- Картка рецепта у стилі Figma «рецепт омлету»: темно-зелений фон, кремовий текст --}}
                <div class="rounded-2xl bg-brand p-5 text-cream shadow-sm sm:p-6">
                    @if ($recipe->description)
                        <p class="text-cream/85">{{ $recipe->description }}</p>
                    @endif
                    <p class="mt-1 text-sm text-cream/60">Порцій: {{ $recipe->servings ?? '—' }}</p>

                    {{-- Інгредієнти --}}
                    <h3 class="mt-5 text-lg font-semibold text-cream">Інгредієнти</h3>
                    <ul class="mt-2.5 space-y-2">
                        @forelse ($recipe->ingredients_json ?? [] as $ingredient)
                            <li class="flex items-center gap-2.5 text-sm">
                                @if (($ingredient['in_pantry'] ?? false))
                                    <svg class="h-4 w-4 shrink-0 text-brand-accent" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1.2 14-4-4 1.4-1.4 2.6 2.6 5.6-5.6L17.8 9l-7 7Z"/>
                                    </svg>
                                @else
                                    <svg class="h-4 w-4 shrink-0 text-amber-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M12 2 1 21h22L12 2Zm1 14h-2v2h2v-2Zm0-6h-2v4h2v-4Z"/>
                                    </svg>
                                @endif
                                <span class="min-w-0 flex-1 text-cream">{{ $ingredient['name'] }}
                                    @if (($ingredient['in_pantry'] ?? false))
                                        <span class="ml-1 text-xs text-brand-accent">є в коморі</span>
                                    @else
                                        <span class="ml-1 text-xs text-amber-300">треба купити</span>
                                    @endif
                                </span>
                                <span class="shrink-0 font-semibold text-cream">{{ $ingredient['quantity'] }} <span class="font-normal text-cream/60">{{ $ingredient['unit'] }}</span></span>
                            </li>
                        @empty
                            <li class="text-sm text-cream/60">Інгредієнти не вказані.</li>
                        @endforelse
                    </ul>

                    {{-- Покрокове приготування --}}
                    <h3 class="mt-6 text-lg font-semibold text-brand-accent">Покрокове приготування</h3>
                    @if (!empty($recipe->steps_json))
                        <ol class="mt-3 space-y-4">
                            @foreach ($recipe->steps_json as $step)
                                <li class="flex gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-accent text-sm font-bold text-ink">{{ $loop->iteration }}</span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-accent">Крок {{ $loop->iteration }}</p>
                                        <p class="mt-0.5 text-sm leading-relaxed text-cream/90">{{ $step }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="mt-2 text-sm text-cream/60">Кроки не вказані.</p>
                    @endif

                    {{-- КБЖУ --}}
                    @php($kbju = $recipe->kbju_json ?? [])
                    <div class="mt-6 grid grid-cols-4 gap-2 border-t border-cream/15 pt-4 text-center">
                        <div><div class="text-lg font-semibold text-brand-accent">{{ $kbju['kcal'] ?? '—' }}</div><div class="text-[0.65rem] text-cream/60">ккал</div></div>
                        <div><div class="text-lg font-semibold text-brand-accent">{{ $kbju['protein'] ?? '—' }}</div><div class="text-[0.65rem] text-cream/60">білки, г</div></div>
                        <div><div class="text-lg font-semibold text-brand-accent">{{ $kbju['fat'] ?? '—' }}</div><div class="text-[0.65rem] text-cream/60">жири, г</div></div>
                        <div><div class="text-lg font-semibold text-brand-accent">{{ $kbju['carbs'] ?? '—' }}</div><div class="text-[0.65rem] text-cream/60">вуглеводи, г</div></div>
                    </div>
                </div>

                {{-- Панель дій --}}
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    {{-- «Приготовано» (тікет 4.1): веде на сторінку підтвердження списання,
                         нічого не списує одразу. Список + списання комори — тікети 4.2–4.4. --}}
                    @if ($recipe->status === 'cooked')
                        <button type="button" disabled
                                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-beige text-muted font-semibold rounded-xl cursor-not-allowed">
                            🍳 Вже приготовано
                        </button>
                    @else
                        <a href="{{ route('recipes.cook.confirm', $recipe) }}"
                           class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand-accent text-ink font-semibold rounded-xl shadow-sm hover:bg-brand-accent-light transition">
                            🍳 Приготовано
                        </a>
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
