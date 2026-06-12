<?php

namespace App\Models;

use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category', 'is_custom', 'created_by_user_id'])]
class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
        ];
    }

    public function pantryItems(): HasMany
    {
        return $this->hasMany(PantryItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Ingredients selectable by a user: the shared catalog plus their own custom ones
     * (never another user's custom ingredients).
     */
    public function scopeAvailableTo(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('is_custom', false)
                ->orWhere('created_by_user_id', $userId);
        });
    }

    /** Case-insensitive name search for the add-item autocomplete (ticket 1.6). */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->whereLike('name', '%'.$term.'%');
    }
}
