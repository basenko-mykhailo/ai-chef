<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="font-display text-3xl font-bold uppercase leading-none text-brand">Комора</h2>
                <p class="mt-1 text-sm font-medium text-brand-accent">AI-аналіз ваших запасів</p>
            </div>
            <a href="{{ route('pantry.create') }}"
               class="inline-flex items-center gap-1 rounded-lg bg-brand-accent px-4 py-2 text-sm font-semibold text-ink shadow-sm transition hover:bg-brand-accent-light focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 focus:ring-offset-cream">
                <span class="text-lg leading-none">+</span> Додати
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-md px-4">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-brand-accent bg-brand-accent/15 px-4 py-3 text-sm text-brand"
                     x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 4000)" x-transition>
                    @switch(session('status'))
                        @case('pantry-added') Продукт додано до комори. @break
                        @case('pantry-updated') Зміни збережено. @break
                        @case('pantry-deleted') Продукт видалено з комори. @break
                    @endswitch
                </div>
            @endif

            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-display text-xl font-bold text-brand">
                    Запаси <span class="font-sans text-base font-normal text-muted">({{ $items->count() }})</span>
                </h3>
                <a href="{{ route('pantry.create') }}"
                   class="flex h-9 w-9 items-center justify-center rounded-lg bg-cream text-brand shadow-sm transition hover:bg-beige"
                   aria-label="Додати продукт" title="Додати продукт">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                    </svg>
                </a>
            </div>

            @if ($items->isEmpty())
                <div class="rounded-xl border border-beige bg-cream px-6 py-12 text-center shadow-sm">
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-sand text-3xl">🧺</div>
                    <p class="font-medium text-ink">Комора порожня.</p>
                    <p class="mt-1 text-sm text-muted">Додайте продукти, щоб AI міг готувати рецепти з того, що у вас є.</p>
                    <a href="{{ route('pantry.create') }}"
                       class="mt-4 inline-flex items-center gap-1 rounded-lg bg-brand-accent px-4 py-2 text-sm font-semibold text-ink shadow-sm transition hover:bg-brand-accent-light">
                        <span class="text-lg leading-none">+</span> Додати перший продукт
                    </a>
                </div>
            @else
                <ul class="space-y-2">
                    @foreach ($items as $item)
                        <li class="flex items-center gap-3 rounded-xl bg-cream px-4 py-3 shadow-sm">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sand text-brand" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2a2 2 0 0 0-2 2c0 .35.09.68.24.97C7.76 5.86 6 8.2 6 11v1H5a1 1 0 0 0 0 2h1.18A6 6 0 0 0 12 19a6 6 0 0 0 5.82-5H19a1 1 0 0 0 0-2h-1v-1c0-2.8-1.76-5.14-4.24-6.03.15-.29.24-.62.24-.97a2 2 0 0 0-2-2Z"/>
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-ink">{{ $item->ingredient?->name ?? '—' }}</p>
                                @if ($item->ingredient?->category)
                                    <p class="truncate text-xs text-muted">{{ $item->ingredient->category }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 font-semibold text-brand">
                                {{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}<span class="ml-0.5 font-normal text-muted">{{ $item->unit->label() }}</span>
                            </span>
                            <div class="flex shrink-0 items-center gap-1">
                                <a href="{{ route('pantry.edit', $item) }}"
                                   class="flex h-8 w-8 items-center justify-center rounded-lg text-brand transition hover:bg-sand"
                                   aria-label="Змінити" title="Змінити">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                                    </svg>
                                </a>
                                <form method="post" action="{{ route('pantry.destroy', $item) }}"
                                      onsubmit="return confirm('Видалити «{{ $item->ingredient?->name }}» з комори?')">
                                    @csrf
                                    @method('delete')
                                    <button type="submit"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50"
                                            aria-label="Видалити" title="Видалити">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m2 0v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
