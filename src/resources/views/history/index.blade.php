<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-ink leading-tight">Історія рецептів</h2>
            <p class="text-sm text-muted">Усі згенеровані рецепти, обране та приготовані страви</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('recipe-favorite-flash'))
                <div class="mb-4 rounded-lg border border-beige bg-cream px-4 py-3 text-sm text-ink">
                    {{ session('recipe-favorite-flash') }}
                </div>
            @endif

            {{-- Фільтр: статус (5.1) + обране (5.4) --}}
            @php($tabs = ['all' => 'Усі', 'cooked' => 'Приготовані', 'favorites' => 'Обране'])
            <div class="flex flex-wrap gap-2 mb-6">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('history.index', $key === 'all' ? [] : ['filter' => $key]) }}"
                       @class([
                           'px-4 py-2 rounded-lg text-sm font-semibold transition',
                           'bg-brand text-cream' => $filter === $key,
                           'bg-cream text-brand border border-beige hover:bg-beige/40' => $filter !== $key,
                       ])>
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @forelse ($recipes as $recipe)
                <div class="mb-3 bg-cream border border-beige rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 flex items-center justify-between gap-4">
                        <a href="{{ route('recipes.show', $recipe) }}" class="min-w-0 flex-1">
                            <p class="font-medium text-ink truncate">{{ $recipe->name ?: 'Рецепт' }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ $recipe->created_at?->format('d.m.Y H:i') }}</p>
                        </a>

                        <div class="shrink-0 flex items-center gap-3">
                            @if ($recipe->status === 'cooked')
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-brand/10 text-brand">Приготовано</span>
                            @else
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-beige text-muted">Згенеровано</span>
                            @endif

                            <form method="POST" action="{{ route('recipes.favorite', $recipe) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        aria-label="{{ $recipe->is_favorite ? 'Прибрати з обраного' : 'Додати в обране' }}"
                                        class="text-xl leading-none {{ $recipe->is_favorite ? 'text-red-500' : 'text-muted hover:text-red-400' }}">
                                    {{ $recipe->is_favorite ? '♥' : '♡' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center bg-cream border border-beige rounded-xl px-6 py-12 shadow-sm">
                    <p class="text-ink font-medium">
                        @if ($filter === 'favorites')
                            Поки немає обраних рецептів.
                        @elseif ($filter === 'cooked')
                            Ще немає приготованих страв.
                        @else
                            Історія порожня.
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-muted">Згенеруйте рецепт із вашої комори — він зʼявиться тут.</p>
                    <a href="{{ route('recipes.create') }}"
                       class="mt-4 inline-flex items-center px-5 py-2.5 bg-brand text-cream text-sm font-semibold rounded-lg hover:opacity-90 transition">
                        Згенерувати рецепт
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
