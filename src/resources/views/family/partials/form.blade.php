{{-- Shared add/edit form. Expects $member (FamilyMember, may be unsaved) and $action (URL). --}}
<form method="post" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($member->exists)
        @method('patch')
    @endif

    <div>
        <x-input-label for="name" value="Ім'я" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $member->name)" required autofocus
                      maxlength="255" placeholder="Напр. Мама, Син, Бабуся" />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="favorite_products" value="Улюблені продукти" />
        <x-textarea id="favorite_products" name="favorite_products" rows="3" class="mt-1 block w-full"
                    placeholder="Напр. борщ, вареники, картопля">{{ old('favorite_products', $member->favorite_products) }}</x-textarea>
        <x-input-error class="mt-2" :messages="$errors->get('favorite_products')" />
    </div>

    <div>
        <x-input-label for="disliked_products" value="Неулюблені продукти" />
        <x-textarea id="disliked_products" name="disliked_products" rows="3" class="mt-1 block w-full"
                    placeholder="Напр. гриби, морепродукти">{{ old('disliked_products', $member->disliked_products) }}</x-textarea>
        <x-input-error class="mt-2" :messages="$errors->get('disliked_products')" />
    </div>

    <div>
        <x-input-label for="allergies_and_diets" value="Алергії та дієти" />
        <x-textarea id="allergies_and_diets" name="allergies_and_diets" rows="3" class="mt-1 block w-full"
                    placeholder="Напр. алергія на горіхи, без лактози, вегетаріанство">{{ old('allergies_and_diets', $member->allergies_and_diets) }}</x-textarea>
        <x-input-error class="mt-2" :messages="$errors->get('allergies_and_diets')" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>Зберегти</x-primary-button>
        <a href="{{ route('family.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Скасувати</a>
    </div>
</form>
