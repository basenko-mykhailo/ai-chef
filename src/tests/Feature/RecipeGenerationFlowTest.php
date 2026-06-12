<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Jobs\GenerateRecipeJob;
use App\Models\FamilyMember;
use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecipeGenerationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function givePantry(User $user): void
    {
        PantryItem::factory()->create([
            'user_id' => $user->id,
            'ingredient_id' => Ingredient::factory()->create(['name' => 'Картопля'])->id,
        ]);
    }

    public function test_guest_cannot_generate(): void
    {
        $this->postJson('/api/recipes/generate')->assertUnauthorized();
    }

    public function test_valid_submit_creates_pending_recipe_and_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->givePantry($user);
        $member = FamilyMember::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/recipes/generate', [
            'members' => [$member->id],
        ]);

        $response->assertOk()->assertJsonStructure(['recipe_id', 'status_url']);

        $this->assertDatabaseCount('recipes', 1);

        $recipe = Recipe::first();
        $this->assertSame(GenerationStatus::Pending, $recipe->generation_status);
        $this->assertSame($user->id, $recipe->user_id);
        $this->assertNotEmpty($recipe->pantry_snapshot_json);
        $this->assertCount(1, $recipe->selected_family_members_json);

        Queue::assertPushed(
            GenerateRecipeJob::class,
            fn (GenerateRecipeJob $job) => $job->recipe->id === $recipe->id,
        );
    }

    public function test_zero_members_is_allowed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->givePantry($user);

        $this->actingAs($user)->postJson('/api/recipes/generate', ['members' => []])
            ->assertOk();

        $this->assertSame([], Recipe::first()->selected_family_members_json);
        Queue::assertPushed(GenerateRecipeJob::class);
    }

    public function test_empty_pantry_is_rejected(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/recipes/generate', ['members' => []])
            ->assertStatus(422);

        $this->assertDatabaseCount('recipes', 0);
        Queue::assertNothingPushed();
    }

    public function test_foreign_member_id_fails_validation(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->givePantry($user);
        $foreign = FamilyMember::factory()->create(); // belongs to another user

        $this->actingAs($user)->postJson('/api/recipes/generate', [
            'members' => [$foreign->id],
        ])->assertStatus(422)->assertJsonValidationErrors('members.0');

        $this->assertDatabaseCount('recipes', 0);
        Queue::assertNothingPushed();
    }

    public function test_status_endpoint_reports_pending(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->create();

        $this->actingAs($user)->getJson("/api/recipes/{$recipe->id}/status")
            ->assertOk()
            ->assertJson(['status' => 'pending', 'recipe' => null]);
    }

    public function test_status_endpoint_exposes_completed_recipe(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create();

        $this->actingAs($user)->getJson("/api/recipes/{$recipe->id}/status")
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('recipe.id', $recipe->id)
            ->assertJsonPath('recipe.show_url', route('recipes.show', $recipe));
    }

    public function test_status_endpoint_forbidden_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $recipe = Recipe::factory()->for($owner)->create();

        $this->actingAs($other)->getJson("/api/recipes/{$recipe->id}/status")
            ->assertForbidden();
    }
}
