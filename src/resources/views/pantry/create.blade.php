<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">Додати продукт</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-cream border border-beige rounded-xl p-6 shadow-sm">
                @include('pantry.partials.form', [
                    'item' => $item,
                    'action' => route('pantry.store'),
                    'units' => $units,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
