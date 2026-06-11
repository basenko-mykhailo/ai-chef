<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    /** Autocomplete for the pantry add/edit form (ticket 1.6). */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }

        $results = Ingredient::availableTo($request->user()->id)
            ->search($q)
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'category']);

        return response()->json($results);
    }
}
