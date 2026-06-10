<?php

namespace App\Http\Requests;

use App\Models\FamilyMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FamilyMemberRequest extends FormRequest
{
    /**
     * A user may only create members for themselves and edit their own.
     *
     * Runs before validation, so a non-owner gets 403 (not a 422 that would
     * leak the validation rules). `store` has no bound model → allowed.
     */
    public function authorize(): bool
    {
        $member = $this->route('familyMember');

        return ! $member instanceof FamilyMember
            || $member->user_id === $this->user()?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'favorite_products' => ['nullable', 'string', 'max:1000'],
            'disliked_products' => ['nullable', 'string', 'max:1000'],
            'allergies_and_diets' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Ukrainian attribute names — the UI is Ukrainian-facing.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ім\'я',
            'favorite_products' => 'улюблені продукти',
            'disliked_products' => 'неулюблені продукти',
            'allergies_and_diets' => 'алергії та дієти',
        ];
    }

    /**
     * Ukrainian validation messages (kept here so they work regardless of
     * the app locale, which is switched to `uk` in a separate ticket).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Вкажіть ім\'я члена сім\'ї.',
            'max' => 'Поле «:attribute» занадто довге (максимум :max символів).',
        ];
    }
}
