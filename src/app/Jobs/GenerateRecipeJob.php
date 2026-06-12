<?php

namespace App\Jobs;

use App\Enums\GenerationStatus;
use App\Models\FamilyMember;
use App\Models\Recipe;
use App\Services\ClaudeService;
use App\Services\RecipePromptBuilder;
use App\Services\RecipeResponseParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generates a recipe via Claude off the request cycle (ticket 3.8) — the call
 * can take 10+ seconds. The recipe row already exists in the `pending` state
 * with pantry/member snapshots; this job fills it in and flips the status the
 * frontend polls (`api.recipes.status`).
 */
class GenerateRecipeJob implements ShouldQueue
{
    use Queueable;

    /**
     * No job-level retries: the SDK already transport-retries 429/5xx and
     * RecipeResponseParser::parseWithRetry re-parses, so retrying the whole job
     * would only duplicate Claude calls.
     */
    public int $tries = 1;

    /** Above ClaudeService's 120s API timeout so the worker doesn't kill us early. */
    public int $timeout = 180;

    public function __construct(public Recipe $recipe) {}

    public function handle(ClaudeService $claude, RecipePromptBuilder $builder, RecipeResponseParser $parser): void
    {
        $this->recipe->update(['generation_status' => GenerationStatus::Processing]);

        try {
            // Rebuild lightweight members from the snapshot taken at submit time —
            // robust even if a FamilyMember was deleted before the job ran.
            $members = array_map(
                fn (array $attrs) => new FamilyMember($attrs),
                $this->recipe->selected_family_members_json ?? [],
            );

            $prompt = $builder->build(
                $this->recipe->user,
                $members,
                $this->recipe->pantry_snapshot_json ?? [],
            );

            $parsed = $parser->parseWithRetry(
                fn () => $claude->generateText(
                    [['role' => 'user', 'content' => $prompt['user']]],
                    $prompt['system'],
                ),
            );

            $this->recipe->update([
                'name' => $parsed['name'],
                'description' => $parsed['description'],
                'ingredients_json' => $parsed['ingredients'],
                'steps_json' => $parsed['steps'],
                'kbju_json' => $parsed['kbju'],
                'servings' => $parsed['servings'],
                'generation_status' => GenerationStatus::Completed,
                'generation_error' => null,
            ]);
        } catch (Throwable $e) {
            // Covers API failures (timeouts / rate limits → AnthropicException)
            // and unparseable JSON (InvalidRecipeResponseException after retry).
            Log::error('Recipe generation failed', [
                'recipe_id' => $this->recipe->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $this->markFailed();
        }
    }

    /**
     * Safety net: fires when the worker kills the job (timeout / max tries)
     * before the in-handle catch could persist the failed state.
     */
    public function failed(Throwable $e): void
    {
        $this->markFailed();
    }

    private function markFailed(): void
    {
        $this->recipe->update([
            'generation_status' => GenerationStatus::Failed,
            'generation_error' => 'Не вдалося згенерувати рецепт. Спробуйте ще раз.',
        ]);
    }
}
