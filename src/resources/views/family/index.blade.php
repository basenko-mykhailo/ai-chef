<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="font-display text-3xl font-bold uppercase leading-none text-brand">Сімейний кабінет</h2>
                <p class="mt-1 text-sm font-medium text-brand-accent">Смаки та обмеження родини</p>
            </div>
            <a href="{{ route('family.create') }}"
               class="inline-flex items-center gap-1 rounded-lg bg-brand-accent px-4 py-2 text-sm font-semibold text-ink shadow-sm transition hover:bg-brand-accent-light focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 focus:ring-offset-cream">
                <span class="text-lg leading-none">+</span> Додати
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-md px-4">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-brand-accent bg-brand-accent/15 px-4 py-3 text-sm text-brand"
                     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
                    @if (session('status') === 'family-member-created')
                        Члена сім'ї додано.
                    @elseif (session('status') === 'family-member-updated')
                        Зміни збережено.
                    @elseif (session('status') === 'family-member-deleted')
                        Члена сім'ї видалено.
                    @endif
                </div>
            @endif

            @if ($members->isEmpty())
                <div class="rounded-xl border border-beige bg-cream px-6 py-12 text-center shadow-sm">
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-sand text-3xl">👪</div>
                    <p class="font-medium text-ink">Ще немає членів сім'ї.</p>
                    <p class="mt-1 text-sm text-muted">Додайте перших, щоб генерувати рецепти з урахуванням їхніх уподобань та обмежень.</p>
                    <a href="{{ route('family.create') }}"
                       class="mt-4 inline-flex items-center gap-1 rounded-lg bg-brand-accent px-4 py-2 text-sm font-semibold text-ink shadow-sm transition hover:bg-brand-accent-light">
                        <span class="text-lg leading-none">+</span> Додати члена сім'ї
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($members as $member)
                        @php
                            $sections = array_filter([
                                'Алергії та дієти' => $member->allergies_and_diets,
                                'Улюблені продукти' => $member->favorite_products,
                                'Не любить' => $member->disliked_products,
                            ]);
                        @endphp
                        <article class="rounded-xl bg-cream p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand text-lg font-semibold text-cream" aria-hidden="true">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($member->name, 0, 1)) }}
                                    </span>
                                    <h3 class="truncate text-lg font-semibold text-brand">{{ $member->name }}</h3>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <a href="{{ route('family.edit', $member) }}"
                                       class="flex h-8 w-8 items-center justify-center rounded-lg text-brand transition hover:bg-sand"
                                       aria-label="Редагувати" title="Редагувати">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                                        </svg>
                                    </a>
                                    <form method="post" action="{{ route('family.destroy', $member) }}"
                                          onsubmit="return confirm('Видалити цього члена родини?')">
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
                            </div>

                            @if (! empty($sections))
                                <div class="mt-4 space-y-3">
                                    @foreach ($sections as $label => $value)
                                        <div>
                                            <p class="text-sm font-medium text-brand">{{ $label }}</p>
                                            <div class="mt-1.5 flex flex-wrap gap-2">
                                                <span class="inline-flex max-w-full items-center rounded-md bg-sand px-3 py-1 text-sm text-ink break-words">{{ $value }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-3 text-sm text-muted">Уподобання ще не вказані.</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
