<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Jobs\GenerateRecipeJob;
use App\Models\Recipe;
use App\Models\RecipeCache;
use App\Models\User;
use App\Services\ClaudeService;
use App\Services\RecipeCacheKeyBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class GenerateRecipeJobTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_JSON = '{"name":"Картопляне пюре","description":"Смачно та ситно","ingredients":[{"name":"Картопля","quantity":500,"unit":"г","in_pantry":true},{"name":"Сіль","quantity":1,"unit":"ч.л.","in_pantry":false}],"steps":["Зварити картоплю","Розім\'яти й посолити"],"kbju":{"kcal":250,"protein":8,"fat":5,"carbs":40},"servings":2}';

    private function pendingRecipe(): Recipe
    {
        return Recipe::factory()->for(User::factory())->create([
            'pantry_snapshot_json' => [
                ['name' => 'Картопля', 'quantity' => 500.0, 'unit' => 'г'],
            ],
            'selected_family_members_json' => [
                [
                    'name' => 'Мама',
                    'favorite_products' => null,
                    'disliked_products' => null,
                    'allergies_and_diets' => 'горіхи',
                ],
            ],
        ]);
    }

    public function test_successful_generation_completes_recipe(): void
    {
        $this->mock(ClaudeService::class, function ($mock) {
            $mock->shouldReceive('generateText')->once()->andReturn(self::VALID_JSON);
        });

        $recipe = $this->pendingRecipe();

        GenerateRecipeJob::dispatchSync($recipe);

        $recipe->refresh();
        $this->assertSame(GenerationStatus::Completed, $recipe->generation_status);
        $this->assertSame('Картопляне пюре', $recipe->name);
        $this->assertSame('Смачно та ситно', $recipe->description);
        $this->assertCount(2, $recipe->ingredients_json);
        $this->assertSame(2, $recipe->servings);
        $this->assertNull($recipe->generation_error);
    }

    public function test_api_failure_marks_recipe_failed(): void
    {
        // Stands in for a timeout / rate-limit AnthropicException — anything that
        // is not an InvalidRecipeResponseException propagates out of parseWithRetry.
        $this->mock(ClaudeService::class, function ($mock) {
            $mock->shouldReceive('generateText')->andThrow(new RuntimeException('API timeout'));
        });

        $recipe = $this->pendingRecipe();

        GenerateRecipeJob::dispatchSync($recipe);

        $recipe->refresh();
        $this->assertSame(GenerationStatus::Failed, $recipe->generation_status);
        $this->assertNotNull($recipe->generation_error);
        $this->assertEmpty($recipe->name); // never populated — stays the '' placeholder
    }

    public function test_unparseable_json_marks_recipe_failed_after_retry(): void
    {
        // parseWithRetry retries once (2 attempts) before giving up.
        $this->mock(ClaudeService::class, function ($mock) {
            $mock->shouldReceive('generateText')->twice()->andReturn('not json at all');
        });

        $recipe = $this->pendingRecipe();

        GenerateRecipeJob::dispatchSync($recipe);

        $recipe->refresh();
        $this->assertSame(GenerationStatus::Failed, $recipe->generation_status);
        $this->assertNotNull($recipe->generation_error);
    }

    public function test_cache_hit_fills_recipe_without_calling_claude(): void
    {
        // Cache pre-seeded for this recipe's snapshots → Claude must not be hit.
        $this->mock(ClaudeService::class, function ($mock) {
            $mock->shouldReceive('generateText')->never();
        });

        $recipe = $this->pendingRecipe();

        $cacheKey = app(RecipeCacheKeyBuilder::class)->build(
            $recipe->pantry_snapshot_json,
            $recipe->selected_family_members_json,
        );

        RecipeCache::create([
            'cache_key' => $cacheKey,
            'model_used' => 'claude-haiku-4-5',
            'response_json' => [
                'name' => 'Кешований борщ',
                'description' => 'З кешу',
                'ingredients' => [['name' => 'Буряк', 'quantity' => 300, 'unit' => 'г', 'in_pantry' => true]],
                'steps' => ['Зварити'],
                'kbju' => ['kcal' => 200, 'protein' => 6, 'fat' => 4, 'carbs' => 30],
                'servings' => 4,
            ],
        ]);

        GenerateRecipeJob::dispatchSync($recipe);

        $recipe->refresh();
        $this->assertSame(GenerationStatus::Completed, $recipe->generation_status);
        $this->assertSame('Кешований борщ', $recipe->name);
        $this->assertSame(4, $recipe->servings);
        // No new cache rows written on a hit.
        $this->assertSame(1, RecipeCache::count());
    }

    public function test_cache_miss_stores_response_under_the_key(): void
    {
        $this->mock(ClaudeService::class, function ($mock) {
            $mock->shouldReceive('generateText')->once()->andReturn(self::VALID_JSON);
        });

        $recipe = $this->pendingRecipe();

        $cacheKey = app(RecipeCacheKeyBuilder::class)->build(
            $recipe->pantry_snapshot_json,
            $recipe->selected_family_members_json,
        );

        $this->assertDatabaseMissing('recipe_cache', ['cache_key' => $cacheKey]);

        GenerateRecipeJob::dispatchSync($recipe);

        $recipe->refresh();
        $this->assertSame(GenerationStatus::Completed, $recipe->generation_status);
        $this->assertDatabaseHas('recipe_cache', ['cache_key' => $cacheKey]);

        $cached = RecipeCache::find($cacheKey);
        $this->assertSame('Картопляне пюре', $cached->response_json['name']);
        $this->assertSame('claude-haiku-4-5', $cached->model_used);
    }
}
