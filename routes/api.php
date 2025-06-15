<?php

use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/game/invite', [GameController::class, 'invite'])->name('game.invite');
    Route::post('/game/accept-invite', [GameController::class, 'acceptInvite'])->name('game.accept-invite');
    Route::post('/game/{game}/make-move', [GameController::class, 'makeMove'])->name('game.make-move');
});