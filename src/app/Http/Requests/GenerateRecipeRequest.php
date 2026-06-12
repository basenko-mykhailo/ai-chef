<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Zero members is allowed (cook «для себе», per 3.7); any submitted id must
     * be one of the current user's family members.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'members' => ['nullable', 'array'],
            'members.*' => [
                'integer',
                Rule::exists('family_members', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'members.*.integer' => 'Некоректний ідентифікатор члена сім\'ї.',
            'members.*.exists' => 'Обрано неіснуючого члена сім\'ї.',
        ];
    }
}
