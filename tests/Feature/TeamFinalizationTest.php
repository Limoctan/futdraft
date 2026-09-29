<?php

use App\Enums\RoomStatus;
use App\Models\Player;
use App\Models\Room;
use App\Models\Team;
use App\Models\User;

/**
 * @return array{
 *     admin: User,
 *     captainOne: User,
 *     captainTwo: User,
 *     room: Room,
 *     teamOne: Team,
 *     teamTwo: Team,
 * }
 */
function makeFinalizedRoom(): array
{
    $admin = User::factory()->create();
    $captainOne = User::factory()->create();
    $captainTwo = User::factory()->create();

    $room = Room::factory()->create([
        'user_id' => $admin->id,
        'num_teams' => 2,
        'team_size' => 2,
        'status' => RoomStatus::Completed,
    ]);

    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($captainOne->id, ['is_admin' => false]);
    $room->users()->attach($captainTwo->id, ['is_admin' => false]);

    $players = Player::factory()->count(4)->create(['room_id' => $room->id]);

    $teams = [];

    foreach ([[$captainOne, $players[0]], [$captainTwo, $players[1]]] as $index => [$captain, $selfPick]) {
        $team = $room->teams()->create([
            'captain_user_id' => $captain->id,
            'pick_order' => $index + 1,
        ]);

        $team->players()->attach($selfPick->id, ['pick_number' => 1]);

        $teams[] = $team;
    }

    return [
        'admin' => $admin,
        'captainOne' => $captainOne,
        'captainTwo' => $captainTwo,
        'room' => $room,
        'teamOne' => $teams[0],
        'teamTwo' => $teams[1],
    ];
}

test('captain can update their team color', function () {
    $setup = makeFinalizedRoom();

    $response = test()->actingAs($setup['captainOne'])->patch(
        "/rooms/{$setup['room']->id}/teams/{$setup['teamOne']->id}",
        ['color' => '#EF4444'],
    );

    $response->assertRedirect(route('rooms.show', $setup['room']));

    $this->assertDatabaseHas('teams', [
        'id' => $setup['teamOne']->id,
        'color' => '#EF4444',
    ]);
});

test('a color already used by another team in the room is rejected', function () {
    $setup = makeFinalizedRoom();
    $setup['teamOne']->update(['color' => '#EF4444']);

    $response = test()->actingAs($setup['captainTwo'])->patch(
        "/rooms/{$setup['room']->id}/teams/{$setup['teamTwo']->id}",
        ['color' => '#EF4444'],
    );

    $response->assertSessionHasErrors('color');
    expect($setup['teamTwo']->refresh()->color)->not->toBe('#EF4444');
});

test('teams cannot be updated before the draft completes', function () {
    $setup = makeFinalizedRoom();
    $setup['room']->update(['status' => RoomStatus::Drafting]);

    $response = test()->actingAs($setup['captainOne'])->patch(
        "/rooms/{$setup['room']->id}/teams/{$setup['teamOne']->id}",
        ['color' => '#EF4444'],
    );

    $response->assertForbidden();
    expect($setup['teamOne']->refresh()->color)->not->toBe('#EF4444');
});

test('a member who is not the team captain cannot update that team', function () {
    $setup = makeFinalizedRoom();

    $response = test()->actingAs($setup['captainTwo'])->patch(
        "/rooms/{$setup['room']->id}/teams/{$setup['teamOne']->id}",
        ['color' => '#EF4444'],
    );

    $response->assertForbidden();
    expect($setup['teamOne']->refresh()->color)->not->toBe('#EF4444');
});

test('a color outside the predefined palette is rejected', function () {
    $setup = makeFinalizedRoom();

    $response = test()->actingAs($setup['captainOne'])->patch(
        "/rooms/{$setup['room']->id}/teams/{$setup['teamOne']->id}",
        ['color' => '#123456'],
    );

    $response->assertSessionHasErrors('color');
    expect($setup['teamOne']->refresh()->color)->not->toBe('#123456');
});

test('a captain can keep their own current color', function () {
    $setup = makeFinalizedRoom();
    $setup['teamOne']->update(['color' => '#EF4444']);

    $response = test()->actingAs($setup['captainOne'])->patch(
        "/rooms/{$setup['room']->id}/teams/{$setup['teamOne']->id}",
        ['color' => '#EF4444'],
    );

    $response->assertSessionHasNoErrors();
    expect($setup['teamOne']->refresh()->color)->toBe('#EF4444');
});

test('a room admin can update any team', function () {
    $setup = makeFinalizedRoom();

    $response = test()->actingAs($setup['admin'])->patch(
        "/rooms/{$setup['room']->id}/teams/{$setup['teamTwo']->id}",
        ['color' => '#2563EB'],
    );

    $response->assertRedirect(route('rooms.show', $setup['room']));

    $this->assertDatabaseHas('teams', [
        'id' => $setup['teamTwo']->id,
        'color' => '#2563EB',
    ]);
});

test('a non-member cannot update a team', function () {
    $setup = makeFinalizedRoom();
    $outsider = User::factory()->create();

    $response = test()->actingAs($outsider)->patch(
        "/rooms/{$setup['room']->id}/teams/{$setup['teamOne']->id}",
        ['color' => '#EF4444'],
    );

    $response->assertForbidden();
    expect($setup['teamOne']->refresh()->color)->not->toBe('#EF4444');
});

test('a team belonging to another room cannot be updated', function () {
    $setup = makeFinalizedRoom();
    $otherSetup = makeFinalizedRoom();

    $response = test()->actingAs($setup['captainOne'])->patch(
        "/rooms/{$setup['room']->id}/teams/{$otherSetup['teamOne']->id}",
        ['color' => '#EF4444'],
    );

    $response->assertNotFound();
    expect($otherSetup['teamOne']->refresh()->color)->not->toBe('#EF4444');
});

test('captain can update their team name', function () {
    $setup = makeFinalizedRoom();

    $response = test()->actingAs($setup['captainOne'])->patch(
        "/rooms/{$setup['room']->id}/teams/{$setup['teamOne']->id}",
        ['name' => 'Los Tigres'],
    );

    $response->assertRedirect(route('rooms.show', $setup['room']));

    $this->assertDatabaseHas('teams', [
        'id' => $setup['teamOne']->id,
        'name' => 'Los Tigres',
    ]);
});
