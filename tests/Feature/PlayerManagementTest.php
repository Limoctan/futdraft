<?php

use App\Enums\RoomStatus;
use App\Models\Player;
use App\Models\Room;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('room member can add a player with name and rating', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);

    $response = $this->actingAs($user)->post("/rooms/{$room->id}/players", [
        'name' => 'Lionel Messi',
        'rating' => 5,
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    $this->assertDatabaseHas('players', [
        'room_id' => $room->id,
        'name' => 'Lionel Messi',
        'rating' => 5,
        'is_captain' => false,
        'captain_user_id' => null,
    ]);
});

test('unauthenticated user cannot add a player', function () {
    $room = Room::factory()->create();

    $response = $this->post("/rooms/{$room->id}/players", [
        'name' => 'Lionel Messi',
        'rating' => 5,
    ]);

    $response->assertRedirect('/login');
});

test('non-member cannot add a player', function () {
    $creator = User::factory()->create();
    $nonMember = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $creator->id]);
    $room->users()->attach($creator->id, ['is_admin' => true]);

    $response = $this->actingAs($nonMember)->post("/rooms/{$room->id}/players", [
        'name' => 'Lionel Messi',
        'rating' => 5,
    ]);

    $response->assertForbidden();
});

test('add player requires valid name and rating', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);

    $response = $this->actingAs($user)->post("/rooms/{$room->id}/players", [
        'name' => '',
        'rating' => 6,
    ]);

    $response->assertSessionHasErrors(['name', 'rating']);
});

test('add player requires rating between 1 and 5', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);

    $responseZero = $this->actingAs($user)->post("/rooms/{$room->id}/players", [
        'name' => 'Player',
        'rating' => 0,
    ]);
    $responseZero->assertSessionHasErrors(['rating']);

    $responseNegative = $this->actingAs($user)->post("/rooms/{$room->id}/players", [
        'name' => 'Player',
        'rating' => -1,
    ]);
    $responseNegative->assertSessionHasErrors(['rating']);
});

test('cannot add player if room is drafting or completed', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $user->id,
        'status' => RoomStatus::Drafting,
    ]);
    $room->users()->attach($user->id, ['is_admin' => true]);

    $response = $this->actingAs($user)->post("/rooms/{$room->id}/players", [
        'name' => 'Lionel Messi',
        'rating' => 5,
    ]);

    $response->assertForbidden();

    $room->update(['status' => RoomStatus::Completed]);

    $responseCompleted = $this->actingAs($user)->post("/rooms/{$room->id}/players", [
        'name' => 'Lionel Messi',
        'rating' => 5,
    ]);

    $responseCompleted->assertForbidden();
});

test('room member can update a player', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);
    $player = Player::factory()->create([
        'room_id' => $room->id,
        'name' => 'Old Name',
        'rating' => 2,
    ]);

    $response = $this->actingAs($user)->patch("/rooms/{$room->id}/players/{$player->id}", [
        'name' => 'Updated Name',
        'rating' => 4,
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    $this->assertDatabaseHas('players', [
        'id' => $player->id,
        'name' => 'Updated Name',
        'rating' => 4,
    ]);
});

test('non-member cannot update a player', function () {
    $creator = User::factory()->create();
    $nonMember = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $creator->id]);
    $room->users()->attach($creator->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($nonMember)->patch("/rooms/{$room->id}/players/{$player->id}", [
        'name' => 'Hacked Name',
        'rating' => 5,
    ]);

    $response->assertForbidden();
});

test('cannot update player belonging to another room', function () {
    $user = User::factory()->create();
    $room1 = Room::factory()->create(['user_id' => $user->id]);
    $room2 = Room::factory()->create(['user_id' => $user->id]);
    $room1->users()->attach($user->id, ['is_admin' => true]);
    $room2->users()->attach($user->id, ['is_admin' => true]);

    $player = Player::factory()->create(['room_id' => $room2->id]);

    $response = $this->actingAs($user)->patch("/rooms/{$room1->id}/players/{$player->id}", [
        'name' => 'Updated Name',
        'rating' => 4,
    ]);

    $response->assertNotFound();
});

test('update player requires valid rating', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($user)->patch("/rooms/{$room->id}/players/{$player->id}", [
        'name' => 'Valid Name',
        'rating' => 6,
    ]);

    $response->assertSessionHasErrors(['rating']);
});

test('cannot update player if room is drafting or completed', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $user->id,
        'status' => RoomStatus::Drafting,
    ]);
    $room->users()->attach($user->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($user)->patch("/rooms/{$room->id}/players/{$player->id}", [
        'name' => 'New Name',
        'rating' => 3,
    ]);

    $response->assertForbidden();
});

test('room member can remove a player', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($user)->delete("/rooms/{$room->id}/players/{$player->id}");

    $response->assertRedirect(route('rooms.show', $room));
    $this->assertDatabaseMissing('players', [
        'id' => $player->id,
    ]);
});

test('non-member cannot remove a player', function () {
    $creator = User::factory()->create();
    $nonMember = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $creator->id]);
    $room->users()->attach($creator->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($nonMember)->delete("/rooms/{$room->id}/players/{$player->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('players', ['id' => $player->id]);
});

test('cannot remove player belonging to another room', function () {
    $user = User::factory()->create();
    $room1 = Room::factory()->create(['user_id' => $user->id]);
    $room2 = Room::factory()->create(['user_id' => $user->id]);
    $room1->users()->attach($user->id, ['is_admin' => true]);
    $room2->users()->attach($user->id, ['is_admin' => true]);

    $player = Player::factory()->create(['room_id' => $room2->id]);

    $response = $this->actingAs($user)->delete("/rooms/{$room1->id}/players/{$player->id}");

    $response->assertNotFound();
});

test('cannot remove player if room is drafting or completed', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $user->id,
        'status' => RoomStatus::Drafting,
    ]);
    $room->users()->attach($user->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($user)->delete("/rooms/{$room->id}/players/{$player->id}");

    $response->assertForbidden();
});

test('room automatically transitions to full when player count reaches capacity', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $user->id,
        'team_size' => 5,
        'num_teams' => 2, // capacity = 10
        'status' => RoomStatus::Waiting,
    ]);
    $room->users()->attach($user->id, ['is_admin' => true]);

    // Create 9 players (1 short of capacity)
    Player::factory()->count(9)->create(['room_id' => $room->id]);
    $room->refresh();
    expect($room->status)->toBe(RoomStatus::Waiting);

    // Add 10th player
    $response = $this->actingAs($user)->post("/rooms/{$room->id}/players", [
        'name' => 'Tenth Player',
        'rating' => 4,
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    $room->refresh();
    expect($room->status)->toBe(RoomStatus::Full);
});

test('players can be added beyond capacity as reserve list and room remains full', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $user->id,
        'team_size' => 5,
        'num_teams' => 2, // capacity = 10
        'status' => RoomStatus::Waiting,
    ]);
    $room->users()->attach($user->id, ['is_admin' => true]);

    // Create 10 players
    Player::factory()->count(10)->create(['room_id' => $room->id]);
    $room->syncCapacityStatus();
    $room->refresh();
    expect($room->status)->toBe(RoomStatus::Full);

    // Add 11th player (Reserve)
    $response = $this->actingAs($user)->post("/rooms/{$room->id}/players", [
        'name' => 'Reserve Player 1',
        'rating' => 3,
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    $room->refresh();
    expect($room->status)->toBe(RoomStatus::Full);
    expect($room->players()->count())->toBe(11);
});

test('room transitions back to waiting when player count drops below capacity', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $user->id,
        'team_size' => 5,
        'num_teams' => 2, // capacity = 10
        'status' => RoomStatus::Waiting,
    ]);
    $room->users()->attach($user->id, ['is_admin' => true]);

    $players = Player::factory()->count(10)->create(['room_id' => $room->id]);
    $room->syncCapacityStatus();
    $room->refresh();
    expect($room->status)->toBe(RoomStatus::Full);

    // Remove 1 player -> now 9 players
    $response = $this->actingAs($user)->delete("/rooms/{$room->id}/players/{$players->first()->id}");

    $response->assertRedirect(route('rooms.show', $room));
    $room->refresh();
    expect($room->status)->toBe(RoomStatus::Waiting);
    expect($room->players()->count())->toBe(9);
});

test('removing reserve player keeps room status full if still at or above capacity', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create([
        'user_id' => $user->id,
        'team_size' => 5,
        'num_teams' => 2, // capacity = 10
        'status' => RoomStatus::Waiting,
    ]);
    $room->users()->attach($user->id, ['is_admin' => true]);

    $players = Player::factory()->count(11)->create(['room_id' => $room->id]);
    $room->syncCapacityStatus();
    $room->refresh();
    expect($room->status)->toBe(RoomStatus::Full);

    // Remove 1 reserve player -> still 10 players
    $response = $this->actingAs($user)->delete("/rooms/{$room->id}/players/{$players->last()->id}");

    $response->assertRedirect(route('rooms.show', $room));
    $room->refresh();
    expect($room->status)->toBe(RoomStatus::Full);
    expect($room->players()->count())->toBe(10);
});

test('room show page loads players', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id, 'name' => 'Kylian Mbappe']);

    $response = $this->actingAs($user)->get("/rooms/{$room->id}");

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('rooms/show')
        ->has('room.players', 1)
        ->where('room.players.0.name', 'Kylian Mbappe')
    );
});
