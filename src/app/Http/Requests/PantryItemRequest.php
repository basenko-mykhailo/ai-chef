<?php

namespace App\Http\Requests;

use App\Enums\Unit;
use App\Models\PantryItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PantryItemRequest extends FormRequest
{
    /**
     * On update, the pantry item must belong to the current user.
     * Runs before validation → non-owner gets 403, not 422. Store has no bound model.
     */
    public function authorize(): bool
    {
        $item = $this->route('pantryItem');

        return ! $item instanceof PantryItem
            || $item->user_id === $this->user()?->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ingredient_name' => ['required', 'string', 'max:100'],
            'ingredient_id' => ['nullable', 'integer', 'exists:ingredients,id'],
            'category' => ['nullable', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'unit' => ['required', Rule::in(Unit::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ingredient_name' => 'назва продукту',
            'quantity' => 'кількість',
            'unit' => 'одиниця виміру',
            'category' => 'категорія',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ingredient_name.required' => 'Вкажіть назву продукту.',
            'quantity.required' => 'Вкажіть кількість.',
            'quantity.gt' => 'Кількість має бути більшою за нуль.',
            'quantity.numeric' => 'Кількість має бути числом.',
            'unit.required' => 'Оберіть одиницю виміру.',
            'unit.in' => 'Невідома одиниця виміру.',
        ];
    }
}
