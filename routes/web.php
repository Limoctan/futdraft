<?php

use App\Http\Controllers\DraftController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [RoomController::class, 'index'])->name('dashboard');
    Route::post('rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::post('rooms/join', [RoomController::class, 'join'])->name('rooms.join');

    Route::middleware('can:view,room')->group(function () {
        Route::get('rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
        Route::post('rooms/{room}/draft/pick', [DraftController::class, 'pick'])->name('rooms.draft.pick');
        Route::post('rooms/{room}/draft/auto-pick', [DraftController::class, 'autoPick'])->name('rooms.draft.autoPick');
        Route::get('rooms/{room}/draft/current', [DraftController::class, 'current'])->name('rooms.draft.current');
        Route::post('rooms/{room}/players', [PlayerController::class, 'store'])->name('rooms.players.store');
        Route::patch('rooms/{room}/players/{player}', [PlayerController::class, 'update'])->name('rooms.players.update')->scopeBindings();
        Route::delete('rooms/{room}/players/{player}', [PlayerController::class, 'destroy'])->name('rooms.players.destroy')->scopeBindings();
        Route::post('rooms/{room}/players/{player}/pay', [PlayerController::class, 'markPaid'])->name('rooms.players.markPaid')->scopeBindings();
        Route::get('rooms/{room}/players/{player}/payment', [PlayerController::class, 'paymentImage'])->name('rooms.players.paymentImage')->scopeBindings();
    });

    Route::middleware('can:manage,room')->group(function () {
        Route::patch('rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::post('rooms/{room}/assign-admin', [RoomController::class, 'assignAdmin'])->name('rooms.assignAdmin');
        Route::post('rooms/{room}/revoke-admin', [RoomController::class, 'revokeAdmin'])->name('rooms.revokeAdmin');
        Route::delete('rooms/{room}/users/{user}', [RoomController::class, 'removeUser'])->name('rooms.removeUser');
        Route::post('rooms/{room}/assign-captains', [RoomController::class, 'assignCaptains'])->name('rooms.assignCaptains');
        Route::post('rooms/{room}/start-draft', [RoomController::class, 'startDraft'])->name('rooms.startDraft');
        Route::post('rooms/{room}/cancel-draft', [RoomController::class, 'cancelDraft'])->name('rooms.cancelDraft');
    });
});

require __DIR__.'/settings.php';
