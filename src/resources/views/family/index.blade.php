<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Сім'я</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-700">
                @if ($members->isEmpty())
                    Ще немає членів родини. Цей розділ незабаром з'явиться.
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach ($members as $member)
                            <li class="py-4">
                                <div class="font-medium text-gray-900">{{ $member->name }}</div>
                                @if ($member->favorite_products)
                                    <div class="mt-1 text-sm"><span class="text-gray-500">Улюблені продукти:</span> {{ $member->favorite_products }}</div>
                                @endif
                                @if ($member->disliked_products)
                                    <div class="mt-1 text-sm"><span class="text-gray-500">Не любить:</span> {{ $member->disliked_products }}</div>
                                @endif
                                @if ($member->allergies_and_diets)
                                    <div class="mt-1 text-sm"><span class="text-gray-500">Алергії та дієти:</span> {{ $member->allergies_and_diets }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
