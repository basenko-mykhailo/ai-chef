<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_two_completed_demo_recipes_for_the_test_user(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->seed(RecipeSeeder::class);

        $recipes = Recipe::where('user_id', $user->id)->get();
        $this->assertCount(2, $recipes);
        $this->assertTrue($recipes->every(fn (Recipe $r) => $r->generation_status === GenerationStatus::Completed));
        $this->assertSame(1, $recipes->where('is_favorite', true)->count());
        $this->assertSame(1, $recipes->where('status', 'cooked')->count());
    }

    public function test_it_is_idempotent(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->seed(RecipeSeeder::class);
        $this->seed(RecipeSeeder::class);

        $this->assertSame(2, Recipe::count());
    }

    public function test_it_no_ops_without_the_test_user(): void
    {
        $this->seed(RecipeSeeder::class);

        $this->assertSame(0, Recipe::count());
    }
}
