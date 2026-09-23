<?php

use App\Enums\RoomStatus;
use App\Models\Player;
use App\Models\Room;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('admin can assign captains and self-picks to teams', function () {
    $admin = User::factory()->create();
    $captainOne = User::factory()->create();
    $captainTwo = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id, 'num_teams' => 2]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($captainOne->id, ['is_admin' => false]);
    $room->users()->attach($captainTwo->id, ['is_admin' => false]);
    $playerOne = Player::factory()->create(['room_id' => $room->id]);
    $playerTwo = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $captainOne->id, 'player_id' => $playerOne->id],
            ['user_id' => $captainTwo->id, 'player_id' => $playerTwo->id],
        ],
    ]);

    $response->assertRedirect(route('rooms.show', $room));

    $this->assertDatabaseHas('teams', [
        'room_id' => $room->id,
        'captain_user_id' => $captainOne->id,
        'pick_order' => 1,
    ]);
    $this->assertDatabaseHas('teams', [
        'room_id' => $room->id,
        'captain_user_id' => $captainTwo->id,
        'pick_order' => 2,
    ]);

    $teamOne = $room->teams()->where('pick_order', 1)->first();
    $teamTwo = $room->teams()->where('pick_order', 2)->first();

    $this->assertDatabaseHas('team_players', [
        'team_id' => $teamOne->id,
        'player_id' => $playerOne->id,
        'pick_number' => 1,
    ]);
    $this->assertDatabaseHas('team_players', [
        'team_id' => $teamTwo->id,
        'player_id' => $playerTwo->id,
        'pick_number' => 1,
    ]);

    $playerOne->refresh();
    $playerTwo->refresh();

    expect($playerOne->is_captain)->toBeTrue()
        ->and($playerOne->captain_user_id)->toBe($captainOne->id)
        ->and($playerTwo->is_captain)->toBeTrue()
        ->and($playerTwo->captain_user_id)->toBe($captainTwo->id);
});

test('admin can assign themselves as a captain', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id, 'num_teams' => 2]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($member->id, ['is_admin' => false]);
    $playerOne = Player::factory()->create(['room_id' => $room->id]);
    $playerTwo = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $admin->id, 'player_id' => $playerOne->id],
            ['user_id' => $member->id, 'player_id' => $playerTwo->id],
        ],
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    $this->assertDatabaseHas('teams', [
        'room_id' => $room->id,
        'captain_user_id' => $admin->id,
        'pick_order' => 1,
    ]);
});

test('non-admin cannot assign captains', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id, 'num_teams' => 2]);
    $room->users()->attach($user->id, ['is_admin' => false]);
    $playerOne = Player::factory()->create(['room_id' => $room->id]);
    $playerTwo = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($user)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $user->id, 'player_id' => $playerOne->id],
            ['user_id' => User::factory()->create()->id, 'player_id' => $playerTwo->id],
        ],
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('teams', 0);
});

test('assign captains requires the correct number of assignments', function () {
    $admin = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id, 'num_teams' => 2]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $admin->id, 'player_id' => $player->id],
        ],
    ]);

    $response->assertSessionHasErrors('captains');
    $this->assertDatabaseCount('teams', 0);
});

test('assign captains rejects users who are not room members', function () {
    $admin = User::factory()->create();
    $outsider = User::factory()->create();
    $member = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id, 'num_teams' => 2]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($member->id, ['is_admin' => false]);
    $playerOne = Player::factory()->create(['room_id' => $room->id]);
    $playerTwo = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $outsider->id, 'player_id' => $playerOne->id],
            ['user_id' => $member->id, 'player_id' => $playerTwo->id],
        ],
    ]);

    $response->assertSessionHasErrors('captains.0.user_id');
    $this->assertDatabaseCount('teams', 0);
});

test('assign captains rejects players from another room', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id, 'num_teams' => 2]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($member->id, ['is_admin' => false]);
    $otherRoomPlayer = Player::factory()->create();
    $playerTwo = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $admin->id, 'player_id' => $otherRoomPlayer->id],
            ['user_id' => $member->id, 'player_id' => $playerTwo->id],
        ],
    ]);

    $response->assertSessionHasErrors('captains.0.player_id');
    $this->assertDatabaseCount('teams', 0);
});

test('assign captains rejects duplicate captains', function () {
    $admin = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id, 'num_teams' => 2]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $playerOne = Player::factory()->create(['room_id' => $room->id]);
    $playerTwo = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $admin->id, 'player_id' => $playerOne->id],
            ['user_id' => $admin->id, 'player_id' => $playerTwo->id],
        ],
    ]);

    $response->assertSessionHasErrors('captains');
    $this->assertDatabaseCount('teams', 0);
});

test('admin can update existing captain assignments', function () {
    $admin = User::factory()->create();
    $captainOne = User::factory()->create();
    $captainTwo = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id, 'num_teams' => 2]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($captainOne->id, ['is_admin' => false]);
    $room->users()->attach($captainTwo->id, ['is_admin' => false]);
    $playerOne = Player::factory()->create(['room_id' => $room->id]);
    $playerTwo = Player::factory()->create(['room_id' => $room->id]);
    $playerThree = Player::factory()->create(['room_id' => $room->id]);

    $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $captainOne->id, 'player_id' => $playerOne->id],
            ['user_id' => $captainTwo->id, 'player_id' => $playerTwo->id],
        ],
    ])->assertRedirect(route('rooms.show', $room));

    $response = $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $captainOne->id, 'player_id' => $playerThree->id],
            ['user_id' => $captainTwo->id, 'player_id' => $playerTwo->id],
        ],
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    $this->assertDatabaseCount('teams', 2);

    $teamOne = $room->teams()->where('pick_order', 1)->first();
    $this->assertDatabaseMissing('team_players', [
        'team_id' => $teamOne->id,
        'player_id' => $playerOne->id,
    ]);
    $this->assertDatabaseHas('team_players', [
        'team_id' => $teamOne->id,
        'player_id' => $playerThree->id,
        'pick_number' => 1,
    ]);

    $playerOne->refresh();
    $playerThree->refresh();

    expect($playerOne->is_captain)->toBeFalse()
        ->and($playerOne->captain_user_id)->toBeNull()
        ->and($playerThree->is_captain)->toBeTrue()
        ->and($playerThree->captain_user_id)->toBe($captainOne->id);
});

test('room show page includes teams with captain and players', function () {
    $admin = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id, 'num_teams' => 2]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $playerOne = Player::factory()->create(['room_id' => $room->id, 'name' => 'Self Pick']);
    $playerTwo = Player::factory()->create(['room_id' => $room->id]);

    $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $admin->id, 'player_id' => $playerOne->id],
            ['user_id' => $admin->id, 'player_id' => $playerTwo->id],
        ],
    ])->assertSessionHasErrors('captains');

    $otherCaptain = User::factory()->create();
    $room->users()->attach($otherCaptain->id, ['is_admin' => false]);

    $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $admin->id, 'player_id' => $playerOne->id],
            ['user_id' => $otherCaptain->id, 'player_id' => $playerTwo->id],
        ],
    ])->assertRedirect(route('rooms.show', $room));

    $response = $this->actingAs($admin)->get("/rooms/{$room->id}");

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('rooms/show')
        ->has('room.teams', 2)
        ->where('room.teams.0.pick_order', 1)
        ->where('room.teams.0.captain.id', $admin->id)
        ->where('room.teams.0.players.0.name', 'Self Pick')
        ->where('room.teams.0.players.0.pivot.pick_number', 1)
    );
});

test('cannot start draft without all captains assigned', function () {
    $admin = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $admin->id,
        'num_teams' => 2,
        'team_size' => 5,
        'status' => RoomStatus::Full,
    ]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    Player::factory()->count(10)->create(['room_id' => $room->id]);

    $response = $this->actingAs($admin)->post("/rooms/{$room->id}/start-draft");

    $response->assertSessionHasErrors('captains');
    $room->refresh();
    expect($room->status)->toBe(RoomStatus::Full);
});

test('admin can start draft when room is full and all captains are assigned', function () {
    $admin = User::factory()->create();
    $captainOne = User::factory()->create();
    $captainTwo = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $admin->id,
        'num_teams' => 2,
        'team_size' => 5,
        'status' => RoomStatus::Full,
    ]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($captainOne->id, ['is_admin' => false]);
    $room->users()->attach($captainTwo->id, ['is_admin' => false]);
    Player::factory()->count(10)->create(['room_id' => $room->id]);
    $players = $room->players()->get();

    $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $captainOne->id, 'player_id' => $players[0]->id],
            ['user_id' => $captainTwo->id, 'player_id' => $players[1]->id],
        ],
    ])->assertRedirect(route('rooms.show', $room));

    $response = $this->actingAs($admin)->post("/rooms/{$room->id}/start-draft");

    $response->assertRedirect(route('rooms.show', $room));
    $room->refresh();
    expect($room->status)->toBe(RoomStatus::Drafting)
        ->and($room->draft_started_at)->not->toBeNull();
});

test('non-admin cannot start draft', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $user->id,
        'num_teams' => 2,
        'team_size' => 5,
        'status' => RoomStatus::Full,
    ]);
    $room->users()->attach($user->id, ['is_admin' => false]);
    Player::factory()->count(10)->create(['room_id' => $room->id]);

    $response = $this->actingAs($user)->post("/rooms/{$room->id}/start-draft");

    $response->assertForbidden();
    expect($room->refresh()->status)->toBe(RoomStatus::Full);
});

test('cannot start draft when room is not full', function () {
    $admin = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $admin->id,
        'num_teams' => 2,
        'team_size' => 5,
        'status' => RoomStatus::Waiting,
    ]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    Player::factory()->count(3)->create(['room_id' => $room->id]);

    $response = $this->actingAs($admin)->post("/rooms/{$room->id}/start-draft");

    $response->assertSessionHasErrors('room');
    expect($room->refresh()->status)->toBe(RoomStatus::Waiting);
});

test('cannot delete a player assigned as a self-pick', function () {
    $admin = User::factory()->create();
    $captain = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id, 'num_teams' => 2]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($captain->id, ['is_admin' => false]);
    $playerOne = Player::factory()->create(['room_id' => $room->id]);
    $playerTwo = Player::factory()->create(['room_id' => $room->id]);
    $playerThree = Player::factory()->create(['room_id' => $room->id]);

    $this->actingAs($admin)->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $admin->id, 'player_id' => $playerOne->id],
            ['user_id' => $captain->id, 'player_id' => $playerTwo->id],
        ],
    ])->assertRedirect(route('rooms.show', $room));

    $response = $this->actingAs($admin)->delete("/rooms/{$room->id}/players/{$playerOne->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('players', ['id' => $playerOne->id]);

    $deleteRegular = $this->actingAs($admin)->delete("/rooms/{$room->id}/players/{$playerThree->id}");
    $deleteRegular->assertRedirect(route('rooms.show', $room));
    $this->assertDatabaseMissing('players', ['id' => $playerThree->id]);
});
