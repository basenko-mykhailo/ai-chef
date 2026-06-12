<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected(): void
    {
        $this->get(route('ingredients.search', ['q' => 'кар']))->assertRedirect('/login');
    }

    public function test_search_returns_catalog_matches_by_name(): void
    {
        $user = User::factory()->create();
        Ingredient::factory()->create(['name' => 'Картопля']);
        Ingredient::factory()->create(['name' => 'Морква']);

        $this->actingAs($user)->getJson(route('ingredients.search', ['q' => 'карт']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Картопля']);
    }

    public function test_search_excludes_other_users_custom_ingredients(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Ingredient::factory()->create(['name' => 'Картопля']);
        Ingredient::factory()->custom($other)->create(['name' => 'Картопляний секрет']);

        $names = $this->actingAs($user)
            ->getJson(route('ingredients.search', ['q' => 'картопл']))
            ->assertOk()
            ->json('*.name');

        $this->assertContains('Картопля', $names);
        $this->assertNotContains('Картопляний секрет', $names);
    }

    public function test_empty_query_returns_empty(): void
    {
        $user = User::factory()->create();
        Ingredient::factory()->create(['name' => 'Картопля']);

        $this->actingAs($user)->getJson(route('ingredients.search', ['q' => '']))
            ->assertOk()
            ->assertExactJson([]);
    }
}
