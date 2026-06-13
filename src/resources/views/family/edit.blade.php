<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-bold uppercase leading-none text-brand">Редагувати члена сім'ї</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-md mx-auto px-4">
            <div class="bg-cream border border-beige rounded-xl p-6 shadow-sm">
                @include('family.partials.form', [
                    'member' => $member,
                    'action' => route('family.update', $member),
                ])
            </div>
        </div>
    </div>
</x-app-layout>
