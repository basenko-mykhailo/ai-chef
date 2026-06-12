<?php

namespace App\Http\Controllers;

use App\Models\PantryItem;
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
     * Stub generation endpoint for ticket 3.7 — the page is fully clickable
     * but no recipe is produced yet.
     *
     * TODO(3.8): replace with RecipeGenerationJob dispatch + cache lookup (3.9).
     */
    public function generate(Request $request): RedirectResponse
    {
        return redirect()->route('recipes.create')->with('status', 'recipe-generation-pending');
    }
}
