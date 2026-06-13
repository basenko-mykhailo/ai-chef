<?php

use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\PantryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecipeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/ingredients/search', [IngredientController::class, 'search'])->name('ingredients.search');

    Route::get('/pantry', [PantryController::class, 'index'])->name('pantry.index');
    Route::get('/pantry/create', [PantryController::class, 'create'])->name('pantry.create');
    Route::post('/pantry', [PantryController::class, 'store'])->name('pantry.store');
    Route::get('/pantry/{pantryItem}/edit', [PantryController::class, 'edit'])->name('pantry.edit');
    Route::patch('/pantry/{pantryItem}', [PantryController::class, 'update'])->name('pantry.update');
    Route::delete('/pantry/{pantryItem}', [PantryController::class, 'destroy'])->name('pantry.destroy');

    Route::get('/family', [FamilyMemberController::class, 'index'])->name('family.index');
    Route::get('/family/create', [FamilyMemberController::class, 'create'])->name('family.create');
    Route::post('/family', [FamilyMemberController::class, 'store'])->name('family.store');
    Route::get('/family/{familyMember}/edit', [FamilyMemberController::class, 'edit'])->name('family.edit');
    Route::patch('/family/{familyMember}', [FamilyMemberController::class, 'update'])->name('family.update');
    Route::delete('/family/{familyMember}', [FamilyMemberController::class, 'destroy'])->name('family.destroy');

    Route::view('/recipes', 'placeholder', ['title' => 'Рецепти'])->name('recipes.index');
    Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');
    Route::get('/recipes/{recipe}/cook', [RecipeController::class, 'confirmCook'])->name('recipes.cook.confirm');
    Route::post('/recipes/{recipe}/cook', [RecipeController::class, 'cook'])->name('recipes.cook.store');
    Route::patch('/recipes/{recipe}/favorite', [RecipeController::class, 'toggleFavorite'])->name('recipes.favorite');
    Route::post('/api/recipes/generate', [RecipeController::class, 'generate'])
        ->middleware('throttle:recipe-generation')
        ->name('api.recipes.generate');
    Route::get('/api/recipes/{recipe}/status', [RecipeController::class, 'status'])->name('api.recipes.status');
    Route::view('/history', 'placeholder', ['title' => 'Історія'])->name('history.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
