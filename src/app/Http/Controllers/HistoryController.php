<?php

namespace App\Http\Controllers;

use App\Enums\GenerationStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Recipe history (ticket 5.1) + favourites filter (5.4): the user's
     * successfully generated recipes, newest first, with a status tag. The
     * `filter` query param narrows the list to cooked dishes or favourites.
     */
    public function index(Request $request): View
    {
        $filter = $request->query('filter');

        $recipes = $request->user()->recipes()
            ->where('generation_status', GenerationStatus::Completed)
            ->when($filter === 'cooked', fn ($query) => $query->where('status', 'cooked'))
            ->when($filter === 'favorites', fn ($query) => $query->where('is_favorite', true))
            ->latest()
            ->get();

        return view('history.index', [
            'recipes' => $recipes,
            'filter' => in_array($filter, ['cooked', 'favorites'], true) ? $filter : 'all',
        ]);
    }
}
