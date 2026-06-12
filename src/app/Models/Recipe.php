<?php

namespace App\Models;

use App\Enums\GenerationStatus;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'name',
    'description',
    'ingredients_json',
    'steps_json',
    'kbju_json',
    'pantry_snapshot_json',
    'selected_family_members_json',
    'servings',
    'status',
    'generation_status',
    'generation_error',
    'is_favorite',
    'cooked_at',
])]
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ingredients_json' => 'array',
            'steps_json' => 'array',
            'kbju_json' => 'array',
            'pantry_snapshot_json' => 'array',
            'selected_family_members_json' => 'array',
            'servings' => 'integer',
            'generation_status' => GenerationStatus::class,
            'is_favorite' => 'boolean',
            'cooked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
