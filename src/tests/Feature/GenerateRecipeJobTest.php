<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Jobs\GenerateRecipeJob;
use App\Models\Recipe;
use App\Models\User;
use App\Services\ClaudeService;
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
}
