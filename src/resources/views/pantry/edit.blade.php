<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-bold uppercase leading-none text-brand">Змінити продукт</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-md mx-auto px-4">
            <div class="bg-cream border border-beige rounded-xl p-6 shadow-sm">
                @include('pantry.partials.form', [
                    'item' => $item,
                    'action' => route('pantry.update', $item),
                    'units' => $units,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
