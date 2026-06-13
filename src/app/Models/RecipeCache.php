<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Кеш відповідей Claude (тікет 3.9). Рядки insert-only: ключ — sha256 від
 * комбінації комори + обмежень (див. RecipeCacheKeyBuilder), `response_json` —
 * парсована відповідь рецепту. Таблиця `recipe_cache` має лише `created_at`
 * (без `updated_at`), тож timestamps вимкнено, а `created_at` ставить БД
 * (`useCurrent()` у міграції 3.2).
 */
#[Fillable([
    'cache_key',
    'response_json',
    'model_used',
])]
class RecipeCache extends Model
{
    protected $table = 'recipe_cache';

    protected $primaryKey = 'cache_key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'response_json' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
