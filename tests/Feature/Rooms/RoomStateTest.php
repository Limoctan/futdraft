<?php

use App\Enums\RoomStatus;
use App\Models\Player;
use App\Models\Room;
use App\Models\User;

test('a room transitions through every state of the lifecycle', function () {
    $admin = User::factory()->create();
    $captainOne = User::factory()->create();
    $captainTwo = User::factory()->create();

    $this->actingAs($admin)->post('/rooms', [
        'name' => 'Lifecycle Match',
        'date' => now()->addWeek()->format('Y-m-d'),
        'team_size' => 5,
        'num_teams' => 2,
        'price_in_cents' => 1000,
        'currency' => 'USD',
    ])->assertRedirect();

    $room = Room::where('name', 'Lifecycle Match')->firstOrFail();
    $room->users()->attach($captainOne->id, ['is_admin' => false]);
    $room->users()->attach($captainTwo->id, ['is_admin' => false]);

    expect($room->status)->toBe(RoomStatus::Waiting);

    foreach (range(1, 10) as $number) {
        $this->actingAs($admin)->post("/rooms/{$room->id}/players", [
            'name' => "Player {$number}",
            'rating' => 5,
        ])->assertRedirect();
    }

    expect($room->refresh()->status)->toBe(RoomStatus::Full);

    $players = $room->players()->orderBy('id')->get();

    $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $captainOne->id, 'player_id' => $players[0]->id],
            ['user_id' => $captainTwo->id, 'player_id' => $players[1]->id],
        ],
    ])->assertRedirect();

    $this->actingAs($admin)->post("/rooms/{$room->id}/start-draft")->assertRedirect();

    expect($room->refresh()->status)->toBe(RoomStatus::Drafting);

    foreach (range(1, 8) as $ignored) {
        $room->refresh();

        $team = $room->teams()->findOrFail($room->currentDraftTeamId());
        $captain = User::findOrFail($team->captain_user_id);
        $availablePlayer = $room->availableDraftPlayers()->first();

        $this->actingAs($captain)->post("/rooms/{$room->id}/draft/pick", [
            'player_id' => $availablePlayer->id,
        ])->assertRedirect();
    }

    expect($room->refresh()->status)->toBe(RoomStatus::Completed);
});

test('a completed room cannot leave the completed state', function () {
    $admin = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $admin->id,
        'team_size' => 5,
        'num_teams' => 2,
        'status' => RoomStatus::Completed,
    ]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    Player::factory()->count(10)->create(['room_id' => $room->id]);

    $this->actingAs($admin)->post("/rooms/{$room->id}/start-draft")
        ->assertSessionHasErrors(['room' => 'The draft has already started.']);

    $this->actingAs($admin)->post("/rooms/{$room->id}/cancel-draft")
        ->assertSessionHasErrors(['room' => 'There is no active draft to cancel.']);

    expect($room->refresh()->status)->toBe(RoomStatus::Completed);
});
