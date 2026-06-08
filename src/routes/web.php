<?php

use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::view('/pantry', 'placeholder', ['title' => 'Комора'])->name('pantry.index');
    Route::get('/family', [FamilyMemberController::class, 'index'])->name('family.index');
    Route::view('/recipes', 'placeholder', ['title' => 'Рецепти'])->name('recipes.index');
    Route::view('/history', 'placeholder', ['title' => 'Історія'])->name('history.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
