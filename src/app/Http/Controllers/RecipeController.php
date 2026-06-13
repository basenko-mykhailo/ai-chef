<?php

namespace App\Http\Controllers;

use App\Enums\GenerationStatus;
use App\Http\Requests\GenerateRecipeRequest;
use App\Jobs\GenerateRecipeJob;
use App\Models\PantryItem;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipeController extends Controller
{
    /**
     * Recipe generation page (ticket 3.7): family-member checkboxes
     * (all checked by default), the current pantry, and the
     * «Згенерувати рецепт» button.
     */
    public function create(Request $request): View
    {
        $user = $request->user();

        $pantry = $user->pantryItems()
            ->with('ingredient')
            ->get()
            ->sortBy(fn (PantryItem $item) => mb_strtolower((string) $item->ingredient?->name))
            ->values();

        $members = $user->familyMembers()->orderBy('id')->get();

        return view('recipes.create', [
            'pantry' => $pantry,
            'members' => $members,
        ]);
    }

    /**
     * Endpoint генерації (тікет 3.8): валідує обраних членів сім'ї, створює
     * рецепт у стані `pending` зі снапшотами комори й обмежень, диспатчить
     * GenerateRecipeJob (генерація може зайняти 10+ сек) і повертає URL, який
     * фронт опитує. Кеш (3.9) і throttle (3.12) — окремі тікети.
     */
    public function generate(GenerateRecipeRequest $request): JsonResponse
    {
        $user = $request->user();

        $pantry = $user->pantryItems()->with('ingredient')->get();

        if ($pantry->isEmpty()) {
            return response()->json(
                ['message' => 'Комора порожня — додайте продукти перед генерацією.'],
                422,
            );
        }

        // Units use the Ukrainian label so the prompt (and Claude's reply) stay
        // within RecipeSchema::ALLOWED_UNITS the parser validates against.
        $pantrySnapshot = $pantry
            ->map(fn (PantryItem $item) => [
                'name' => (string) $item->ingredient?->name,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit->label(),
            ])
            ->values()
            ->all();

        $membersSnapshot = $user->familyMembers()
            ->whereIn('id', $request->validated('members', []))
            ->get()
            ->map(fn ($member) => [
                'name' => $member->name,
                'favorite_products' => $member->favorite_products,
                'disliked_products' => $member->disliked_products,
                'allergies_and_diets' => $member->allergies_and_diets,
            ])
            ->values()
            ->all();

        $recipe = $user->recipes()->create([
            // Placeholders for the NOT NULL AI columns until the job fills them in.
            'name' => '',
            'ingredients_json' => [],
            'steps_json' => [],
            'kbju_json' => [],
            'generation_status' => GenerationStatus::Pending,
            'pantry_snapshot_json' => $pantrySnapshot,
            'selected_family_members_json' => $membersSnapshot,
        ]);

        GenerateRecipeJob::dispatch($recipe);

        return response()->json([
            'recipe_id' => $recipe->id,
            'status_url' => route('api.recipes.status', $recipe),
        ]);
    }

    /**
     * Polling-ендпоінт (тікет 3.8): повертає поточний стан генерації; коли
     * `completed` — додає посилання на готовий рецепт для redirect-у.
     */
    public function status(Request $request, Recipe $recipe): JsonResponse
    {
        abort_unless($recipe->user_id === $request->user()->id, 403);

        $payload = [
            'status' => $recipe->generation_status->value,
            'error' => $recipe->generation_error,
            'recipe' => null,
        ];

        if ($recipe->generation_status === GenerationStatus::Completed) {
            $payload['recipe'] = [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'show_url' => route('recipes.show', $recipe),
            ];
        }

        return response()->json($payload);
    }

    /**
     * Мінімальна сторінка результату — ціль redirect-у після завершення
     * генерації. Тікет 3.10 замінить її на повноцінну картку рецепту.
     */
    public function show(Request $request, Recipe $recipe): View
    {
        abort_unless($recipe->user_id === $request->user()->id, 403);

        return view('recipes.show', ['recipe' => $recipe]);
    }

    /**
     * Сторінка підтвердження списання (тікет 4.1): кнопка «Приготовано» з картки
     * веде сюди — нічого не списує і не змінює статус рецепту. Тікет 4.2 наповнить
     * сторінку списком інгредієнтів із редагованими кількостями, 4.3 додасть
     * атомарне списання комори, 4.4 — перехід статусу рецепту в `cooked`.
     */
    public function confirmCook(Request $request, Recipe $recipe): View
    {
        abort_unless($recipe->user_id === $request->user()->id, 403);

        return view('recipes.cook', ['recipe' => $recipe]);
    }

    /**
     * Тогл «В обране» з картки рецепту (тікет 3.10): перемикає `is_favorite`
     * і повертає назад із flash. Тікет 5.3 розширить це на AJAX-серце в
     * історії/списках.
     */
    public function toggleFavorite(Request $request, Recipe $recipe): RedirectResponse
    {
        abort_unless($recipe->user_id === $request->user()->id, 403);

        $recipe->update(['is_favorite' => ! $recipe->is_favorite]);

        return back()->with(
            'recipe-favorite-flash',
            $recipe->is_favorite ? 'Додано в обране.' : 'Прибрано з обраного.',
        );
    }
}
