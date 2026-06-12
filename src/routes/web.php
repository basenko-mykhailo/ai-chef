<?php

use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\PantryController;
use App\Http\Controllers\ProfileController;
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

    Route::view('/recipes', 'placeholder', ['title' => 'Рецепти'])->name('recipes.index');
    Route::view('/history', 'placeholder', ['title' => 'Історія'])->name('history.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
