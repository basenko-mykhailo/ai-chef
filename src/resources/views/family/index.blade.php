<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Сім'я</h2>
            <a href="{{ route('family.create') }}"
                class="inline-flex items-center px-4 py-2 bg-brand border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-accent focus:bg-brand-accent active:bg-ink focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 transition ease-in-out duration-150">
                Додати члена сім'ї
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800"
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

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-700">
                @if ($members->isEmpty())
                    <div class="text-center py-8">
                        <p class="text-gray-900 font-medium">Ще немає членів сім'ї.</p>
                        <p class="mt-1 text-sm text-gray-500">Додайте перших, щоб генерувати рецепти з урахуванням їхніх
                            уподобань та обмежень.</p>
                        <a href="{{ route('family.create') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-brand border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-accent focus:bg-brand-accent active:bg-ink focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 transition ease-in-out duration-150">
                            Додати члена сім'ї
                        </a>
                    </div>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach ($members as $member)
                            <li class="py-4 flex items-start justify-between gap-4">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $member->name }}</div>
                                    @if ($member->favorite_products)
                                        <div class="mt-1 text-sm"><span class="text-gray-500">Улюблені продукти:</span>
                                            {{ $member->favorite_products }}</div>
                                    @endif
                                    @if ($member->disliked_products)
                                        <div class="mt-1 text-sm"><span class="text-gray-500">Не любить:</span>
                                            {{ $member->disliked_products }}</div>
                                    @endif
                                    @if ($member->allergies_and_diets)
                                        <div class="mt-1 text-sm"><span class="text-gray-500">Алергії та дієти:</span>
                                            {{ $member->allergies_and_diets }}</div>
                                    @endif
                                </div>
                                <div class="shrink-0 flex items-center gap-3">
                                    <a href="{{ route('family.edit', $member) }}"
                                        class="inline-flex items-center px-4 py-2 bg-brand border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-accent focus:bg-brand-accent active:bg-ink focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 transition ease-in-out duration-150">Редагувати</a>
                                    <form method="post" action="{{ route('family.destroy', $member) }}"
                                        onsubmit="return confirm('Видалити цього члена родини?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-[#c2706a] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#a85a55] active:bg-[#8e4a45] focus:outline-none focus:ring-2 focus:ring-[#c2706a] focus:ring-offset-2 transition ease-in-out duration-150">Видалити</button>
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