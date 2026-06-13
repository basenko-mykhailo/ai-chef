<?php

namespace App\Jobs;

use App\Enums\GenerationStatus;
use App\Exceptions\InvalidRecipeResponseException;
use App\Models\FamilyMember;
use App\Models\Recipe;
use App\Models\RecipeCache;
use App\Services\ClaudeService;
use App\Services\RecipeCacheKeyBuilder;
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

    /**
     * Friendly, cause-specific помилки генерації (тікет 3.11). Зберігаються в
     * `recipes.generation_error` і показуються користувачу як є — без технічних
     * деталей (ті йдуть лише в Log::error).
     */
    private const ERROR_INVALID_RESPONSE = 'AI повернув некоректну відповідь. Спробуйте ще раз.';

    private const ERROR_SERVICE_UNAVAILABLE = 'Сервіс генерації тимчасово недоступний. Спробуйте ще раз за хвилину.';

    private const ERROR_TIMEOUT = 'Генерація зайняла забагато часу. Спробуйте ще раз.';

    public function __construct(public Recipe $recipe) {}

    public function handle(
        ClaudeService $claude,
        RecipePromptBuilder $builder,
        RecipeResponseParser $parser,
        RecipeCacheKeyBuilder $keyBuilder,
    ): void {
        $this->recipe->update(['generation_status' => GenerationStatus::Processing]);

        try {
            // Кеш (тікет 3.9): ключ від комбінації комори + обмежень. Дивимось
            // ДО будь-якого виклику Claude — hit повністю оминає платний запит.
            $cacheKey = $keyBuilder->build(
                $this->recipe->pantry_snapshot_json ?? [],
                $this->recipe->selected_family_members_json ?? [],
            );

            if ($cached = RecipeCache::find($cacheKey)) {
                $this->fillFromResponse($cached->response_json);

                return;
            }

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

            // Зберігаємо після успіху, щоб наступна ідентична комбінація
            // взяла відповідь з кешу.
            RecipeCache::create([
                'cache_key' => $cacheKey,
                'response_json' => $parsed,
                'model_used' => config('services.anthropic.default_model'),
            ]);

            $this->fillFromResponse($parsed);
        } catch (Throwable $e) {
            // Covers API failures (timeouts / rate limits → AnthropicException)
            // and unparseable JSON (InvalidRecipeResponseException after retry).
            // Технічні деталі лишаються в логах; користувач бачить лише friendly-текст.
            Log::error('Recipe generation failed', [
                'recipe_id' => $this->recipe->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $this->markFailed($this->friendlyMessage($e));
        }
    }

    /**
     * Мапить причину збою на friendly-повідомлення. Невалідна відповідь моделі —
     * окремий текст; усе інше (API/мережа/rate-limit/SDK-timeout) трактуємо як
     * тимчасову недоступність сервісу.
     */
    private function friendlyMessage(Throwable $e): string
    {
        return $e instanceof InvalidRecipeResponseException
            ? self::ERROR_INVALID_RESPONSE
            : self::ERROR_SERVICE_UNAVAILABLE;
    }

    /**
     * Заповнює рецепт парсованою відповіддю й ставить `completed`. Спільний
     * шлях для cache-hit і свіжої генерації.
     *
     * @param  array{name: string, description: string, ingredients: array, steps: array, kbju: array, servings: int}  $parsed
     */
    private function fillFromResponse(array $parsed): void
    {
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
    }

    /**
     * Safety net: fires when the worker kills the job (timeout / max tries)
     * before the in-handle catch could persist the failed state — handle()
     * swallows its own Throwable, тож сюди доходить лише worker-timeout/fatal.
     */
    public function failed(Throwable $e): void
    {
        $this->markFailed(self::ERROR_TIMEOUT);
    }

    private function markFailed(string $message): void
    {
        $this->recipe->update([
            'generation_status' => GenerationStatus::Failed,
            'generation_error' => $message,
        ]);
    }
}
