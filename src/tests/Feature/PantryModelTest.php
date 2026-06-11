<?php

namespace Tests\Feature;

use App\Enums\Unit;
use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PantryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_pantry_item_belongs_to_user_and_ingredient(): void
    {
        $item = PantryItem::factory()->create();

        $this->assertInstanceOf(User::class, $item->user);
        $this->assertInstanceOf(Ingredient::class, $item->ingredient);
    }

    public function test_user_has_many_pantry_items(): void
    {
        $user = User::factory()->create();
        PantryItem::factory()->count(3)->create(['user_id' => $user->id]);
        PantryItem::factory()->create(); // another user

        $this->assertCount(3, $user->pantryItems);
    }

    public function test_ingredient_has_many_pantry_items(): void
    {
        $ingredient = Ingredient::factory()->create();
        PantryItem::factory()->count(2)->create(['ingredient_id' => $ingredient->id]);

        $this->assertCount(2, $ingredient->pantryItems);
    }

    public function test_for_user_scope_filters_by_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        PantryItem::factory()->count(2)->create(['user_id' => $user->id]);
        PantryItem::factory()->create(['user_id' => $other->id]);

        $this->assertCount(2, PantryItem::forUser($user->id)->get());
    }

    public function test_unit_is_cast_to_enum(): void
    {
        $item = PantryItem::factory()->create(['unit' => Unit::Kilogram->value]);

        $this->assertSame(Unit::Kilogram, $item->refresh()->unit);
    }

    public function test_quantity_is_cast_with_three_decimals(): void
    {
        $item = PantryItem::factory()->create(['quantity' => 2.5]);

        $this->assertSame('2.500', $item->refresh()->quantity);
    }
}
