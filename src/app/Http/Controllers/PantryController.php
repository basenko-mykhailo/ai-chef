<?php

namespace App\Http\Controllers;

use App\Enums\Unit;
use App\Http\Requests\PantryItemRequest;
use App\Models\Ingredient;
use App\Models\PantryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PantryController extends Controller
{
    public function index(Request $request): View
    {
        $items = $request->user()->pantryItems()
            ->with('ingredient')
            ->get()
            ->sortBy(fn (PantryItem $item) => mb_strtolower((string) $item->ingredient?->name))
            ->values();

        return view('pantry.index', ['items' => $items]);
    }

    public function create(): View
    {
        return view('pantry.create', [
            'item' => new PantryItem(),
            'units' => Unit::options(),
        ]);
    }

    public function store(PantryItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $ingredient = $this->resolveIngredient($request->user()->id, $data);

        $request->user()->pantryItems()->create([
            'ingredient_id' => $ingredient->id,
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
        ]);

        return redirect()->route('pantry.index')->with('status', 'pantry-added');
    }

    public function edit(Request $request, PantryItem $pantryItem): View
    {
        abort_unless($pantryItem->user_id === $request->user()->id, 403);

        return view('pantry.edit', [
            'item' => $pantryItem->load('ingredient'),
            'units' => Unit::options(),
        ]);
    }

    public function update(PantryItemRequest $request, PantryItem $pantryItem): RedirectResponse
    {
        // Ownership enforced by PantryItemRequest::authorize().
        $data = $request->validated();
        $ingredient = $this->resolveIngredient($request->user()->id, $data);

        $pantryItem->update([
            'ingredient_id' => $ingredient->id,
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
        ]);

        return redirect()->route('pantry.index')->with('status', 'pantry-updated');
    }

    public function destroy(Request $request, PantryItem $pantryItem): RedirectResponse
    {
        abort_unless($pantryItem->user_id === $request->user()->id, 403);

        $pantryItem->delete();

        return redirect()->route('pantry.index')->with('status', 'pantry-deleted');
    }

    /**
     * Resolve the chosen ingredient: an existing catalog / own-custom row by id,
     * else match by name (case-insensitive), else create a new custom ingredient
     * on the fly (ticket 1.7). `availableTo` prevents grabbing another user's custom row.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveIngredient(int $userId, array $data): Ingredient
    {
        $name = trim((string) $data['ingredient_name']);

        if (! empty($data['ingredient_id'])) {
            $byId = Ingredient::availableTo($userId)->find($data['ingredient_id']);
            if ($byId) {
                return $byId;
            }
        }

        $byName = Ingredient::availableTo($userId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        return $byName ?? Ingredient::create([
            'name' => $name,
            'category' => $data['category'] ?? null,
            'is_custom' => true,
            'created_by_user_id' => $userId,
        ]);
    }
}
