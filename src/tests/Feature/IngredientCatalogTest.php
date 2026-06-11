<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_populates_a_catalog_of_at_least_150_products(): void
    {
        $this->seed(IngredientSeeder::class);

        $this->assertGreaterThanOrEqual(150, Ingredient::count());
        // Catalog rows are never custom.
        $this->assertSame(0, Ingredient::where('is_custom', true)->count());
        $this->assertNotNull(Ingredient::where('name', 'Картопля')->first());
    }

    public function test_search_scope_finds_by_partial_name(): void
    {
        Ingredient::factory()->create(['name' => 'Картопля']);
        Ingredient::factory()->create(['name' => 'Морква']);

        $results = Ingredient::search('карт')->get();

        $this->assertCount(1, $results);
        $this->assertSame('Картопля', $results->first()->name);
    }

    public function test_available_to_scope_returns_catalog_plus_own_custom_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $catalog = Ingredient::factory()->create(['name' => 'Молоко']);
        $mine = Ingredient::factory()->custom($user)->create(['name' => 'Бабусин сир']);
        $theirs = Ingredient::factory()->custom($other)->create(['name' => 'Секретний соус']);

        $ids = Ingredient::availableTo($user->id)->pluck('id');

        $this->assertTrue($ids->contains($catalog->id));
        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));
    }
}
