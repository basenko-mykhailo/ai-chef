<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-ink leading-tight">Комора</h2>
                <p class="text-sm text-muted">Ваші запаси продуктів</p>
            </div>
            <a href="{{ route('pantry.create') }}"
               class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 transition">
                + Додати продукт
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-brand-accent bg-brand-accent/15 px-4 py-3 text-sm text-brand"
                     x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 4000)" x-transition>
                    @switch(session('status'))
                        @case('pantry-added') Продукт додано до комори. @break
                        @case('pantry-updated') Зміни збережено. @break
                        @case('pantry-deleted') Продукт видалено з комори. @break
                    @endswitch
                </div>
            @endif

            <div class="bg-cream border border-beige rounded-xl overflow-hidden shadow-sm">
                <div class="px-5 py-3 bg-brand text-cream font-medium flex items-center justify-between">
                    <span>Запаси</span>
                    <span class="text-sm opacity-80">{{ $items->count() }} поз.</span>
                </div>

                @if ($items->isEmpty())
                    <div class="text-center px-6 py-12">
                        <p class="text-ink font-medium">Комора порожня.</p>
                        <p class="mt-1 text-sm text-muted">Додайте продукти, щоб AI міг готувати рецепти з того, що у вас є.</p>
                        <a href="{{ route('pantry.create') }}"
                           class="mt-4 inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-md hover:opacity-90 transition">
                            + Додати перший продукт
                        </a>
                    </div>
                @else
                    <ul class="divide-y divide-beige">
                        @foreach ($items as $item)
                            <li class="px-5 py-3 flex items-center justify-between gap-4 bg-white/50">
                                <div class="min-w-0">
                                    <p class="font-medium text-ink truncate">{{ $item->ingredient?->name ?? '—' }}</p>
                                    @if ($item->ingredient?->category)
                                        <p class="text-xs text-muted">{{ $item->ingredient->category }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4 shrink-0">
                                    <span class="text-ink font-semibold">{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}<span class="ml-1 text-muted font-normal">{{ $item->unit->label() }}</span></span>
                                    <a href="{{ route('pantry.edit', $item) }}" class="text-sm text-brand hover:underline">Змінити</a>
                                    <form method="post" action="{{ route('pantry.destroy', $item) }}"
                                          onsubmit="return confirm('Видалити «{{ $item->ingredient?->name }}» з комори?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-sm text-red-600 hover:underline">Видалити</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
