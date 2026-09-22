<?php

use App\Http\Controllers\PlayerController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [RoomController::class, 'index'])->name('dashboard');
    Route::post('rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::get('rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
    Route::patch('rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::post('rooms/join', [RoomController::class, 'join'])->name('rooms.join');
    Route::post('rooms/{room}/assign-admin', [RoomController::class, 'assignAdmin'])->name('rooms.assignAdmin');
    Route::post('rooms/{room}/revoke-admin', [RoomController::class, 'revokeAdmin'])->name('rooms.revokeAdmin');
    Route::delete('rooms/{room}/users/{user}', [RoomController::class, 'removeUser'])->name('rooms.removeUser');
    Route::post('rooms/{room}/players', [PlayerController::class, 'store'])->name('rooms.players.store');
    Route::patch('rooms/{room}/players/{player}', [PlayerController::class, 'update'])->name('rooms.players.update')->scopeBindings();
    Route::delete('rooms/{room}/players/{player}', [PlayerController::class, 'destroy'])->name('rooms.players.destroy')->scopeBindings();
});

require __DIR__.'/settings.php';
