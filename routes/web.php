<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController; // TAMBAHKAN INI

Route::get('/', function () {
    return view('welcome');
});

// Biarkan ini (sudah punya middleware 'auth', 'verified' dan nama 'dashboard')
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Route untuk menampilkan halaman game (sekarang GameController dikenali)
Route::get('/game/{game}', [GameController::class, 'showGame'])->name('game.show');
});

require __DIR__.'/auth.php';