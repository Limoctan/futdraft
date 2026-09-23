<?php

use App\Enums\RoomStatus;
use App\Models\Player;
use App\Models\Room;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Testing\AssertableInertia;

/**
 * @return array{
 *     admin: User,
 *     captainOne: User,
 *     captainTwo: User,
 *     room: Room,
 *     teamOne: Team,
 *     teamTwo: Team,
 *     players: Collection<int, Player>,
 * }
 */
function makeDraftRoom(int $teamSize = 2, int $playerCount = 4): array
{
    $admin = User::factory()->create();
    $captainOne = User::factory()->create();
    $captainTwo = User::factory()->create();

    $room = Room::factory()->create([
        'user_id' => $admin->id,
        'num_teams' => 2,
        'team_size' => $teamSize,
        'status' => RoomStatus::Full,
    ]);

    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($captainOne->id, ['is_admin' => false]);
    $room->users()->attach($captainTwo->id, ['is_admin' => false]);

    $players = Player::factory()->count($playerCount)->create(['room_id' => $room->id]);

    $assignments = [
        [$captainOne, $players[0]],
        [$captainTwo, $players[1]],
    ];

    $teams = [];

    foreach ($assignments as $index => [$captain, $selfPick]) {
        $team = $room->teams()->create([
            'captain_user_id' => $captain->id,
            'pick_order' => $index + 1,
        ]);

        $team->players()->attach($selfPick->id, ['pick_number' => 1]);
        $selfPick->update([
            'is_captain' => true,
            'captain_user_id' => $captain->id,
        ]);

        $teams[] = $team;
    }

    return [
        'admin' => $admin,
        'captainOne' => $captainOne,
        'captainTwo' => $captainTwo,
        'room' => $room,
        'teamOne' => $teams[0],
        'teamTwo' => $teams[1],
        'players' => $players,
    ];
}

/**
 * @param  array{admin: User, room: Room}  $setup
 */
function startDraft(array $setup): Room
{
    test()->actingAs($setup['admin'])
        ->post("/rooms/{$setup['room']->id}/start-draft")
        ->assertRedirect(route('rooms.show', $setup['room']));

    return $setup['room']->refresh();
}

test('starting the draft randomizes the order of team ids', function () {
    $setup = makeDraftRoom();
    $teamIds = [$setup['teamOne']->id, $setup['teamTwo']->id];

    $room = startDraft($setup);

    expect($room->status)->toBe(RoomStatus::Drafting)
        ->and($room->draft_started_at)->not->toBeNull()
        ->and($room->current_pick_index)->toBe(0);

    $order = $room->draft_order;
    sort($order);
    expect($order)->toBe($teamIds);
});

test('starting the draft records self-picks as auto-assigned first picks', function () {
    $setup = makeDraftRoom();

    $room = startDraft($setup);

    expect($room->draftPicks()->count())->toBe(2);

    foreach ([$setup['teamOne'], $setup['teamTwo']] as $team) {
        $selfPick = $team->players()->wherePivot('pick_number', 1)->first();

        $pick = $room->draftPicks()
            ->where('team_id', $team->id)
            ->where('player_id', $selfPick->id)
            ->first();

        expect($pick)->not->toBeNull()
            ->and($pick->auto_picked)->toBeTrue()
            ->and($pick->picked_by_user_id)->toBe($team->captain_user_id);
    }

    $pickNumbers = $room->draftPicks()->orderBy('pick_number')->pluck('pick_number')->all();
    expect($pickNumbers)->toBe([1, 2]);
});

test('captain can pick an available player on their turn', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);

    $room->update(['draft_order' => [$setup['teamOne']->id, $setup['teamTwo']->id]]);
    $available = $room->players()->where('is_captain', false)->whereDoesntHave('teams')->first();

    $response = test()->actingAs($setup['captainOne'])->post("/rooms/{$room->id}/draft/pick", [
        'player_id' => $available->id,
    ]);

    $response->assertRedirect(route('rooms.show', $room));

    $this->assertDatabaseHas('team_players', [
        'team_id' => $setup['teamOne']->id,
        'player_id' => $available->id,
    ]);

    $pick = $room->draftPicks()->where('player_id', $available->id)->first();

    expect($pick)->not->toBeNull()
        ->and($pick->team_id)->toBe($setup['teamOne']->id)
        ->and($pick->picked_by_user_id)->toBe($setup['captainOne']->id)
        ->and($pick->auto_picked)->toBeFalse()
        ->and($room->refresh()->current_pick_index)->toBe(1);
});

test('captain cannot pick when it is not their turn', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);

    $room->update(['draft_order' => [$setup['teamOne']->id, $setup['teamTwo']->id]]);
    $available = $room->players()->whereDoesntHave('teams')->first();

    $response = test()->actingAs($setup['captainTwo'])->post("/rooms/{$room->id}/draft/pick", [
        'player_id' => $available->id,
    ]);

    $response->assertSessionHasErrors('draft');
    $this->assertDatabaseMissing('team_players', [
        'team_id' => $setup['teamTwo']->id,
        'player_id' => $available->id,
    ]);
    expect($room->refresh()->current_pick_index)->toBe(0);
});

test('non-member cannot pick', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);
    $outsider = User::factory()->create();
    $available = $room->players()->whereDoesntHave('teams')->first();

    $response = test()->actingAs($outsider)->post("/rooms/{$room->id}/draft/pick", [
        'player_id' => $available->id,
    ]);

    $response->assertForbidden();
    expect($room->refresh()->current_pick_index)->toBe(0);
});

test('captain cannot pick an unavailable player', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);
    $room->update(['draft_order' => [$setup['teamOne']->id, $setup['teamTwo']->id]]);

    $alreadyPicked = $setup['players'][0];

    $response = test()->actingAs($setup['captainOne'])->post("/rooms/{$room->id}/draft/pick", [
        'player_id' => $alreadyPicked->id,
    ]);

    $response->assertSessionHasErrors('player_id');
    expect($room->refresh()->current_pick_index)->toBe(0);
});

test('draft order reverses each round in snake fashion', function () {
    $setup = makeDraftRoom(teamSize: 3, playerCount: 6);
    $room = startDraft($setup);

    $room->update(['draft_order' => [$setup['teamOne']->id, $setup['teamTwo']->id]]);

    $available = $room->players()->whereDoesntHave('teams')->orderBy('id')->get();
    $sequence = [
        [$setup['captainOne'], $setup['teamOne'], $available[0]],
        [$setup['captainTwo'], $setup['teamTwo'], $available[1]],
        [$setup['captainTwo'], $setup['teamTwo'], $available[2]],
        [$setup['captainOne'], $setup['teamOne'], $available[3]],
    ];

    foreach ($sequence as [$captain, $expectedTeam, $player]) {
        test()->actingAs($captain)->post("/rooms/{$room->id}/draft/pick", [
            'player_id' => $player->id,
        ])->assertRedirect(route('rooms.show', $room));

        $latest = $room->draftPicks()->where('player_id', $player->id)->first();
        expect($latest->team_id)->toBe($expectedTeam->id);
    }

    expect($room->refresh()->current_pick_index)->toBe(4);
});

test('auto-pick selects the highest rated available player after the timer expires', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);
    $room->update(['draft_order' => [$setup['teamOne']->id, $setup['teamTwo']->id]]);

    $low = $room->players()->whereDoesntHave('teams')->orderBy('id')->first();
    $high = $room->players()->whereDoesntHave('teams')->orderBy('id')->skip(1)->first();
    $low->update(['rating' => 1]);
    $high->update(['rating' => 5]);

    $this->travel(3)->minutes();

    $response = test()->actingAs($setup['captainOne'])->post("/rooms/{$room->id}/draft/auto-pick");

    $response->assertRedirect(route('rooms.show', $room));

    $this->assertDatabaseHas('team_players', [
        'team_id' => $setup['teamOne']->id,
        'player_id' => $high->id,
    ]);

    $pick = $room->draftPicks()->where('player_id', $high->id)->first();

    expect($pick->auto_picked)->toBeTrue()
        ->and($room->refresh()->current_pick_index)->toBe(1);
});

test('auto-pick is rejected before the timer expires', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);

    $response = test()->actingAs($setup['captainOne'])->post("/rooms/{$room->id}/draft/auto-pick");

    $response->assertSessionHasErrors('draft');
    expect($room->refresh()->current_pick_index)->toBe(0);
});

test('manual pick is rejected after the timer expires', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);
    $room->update(['draft_order' => [$setup['teamOne']->id, $setup['teamTwo']->id]]);
    $available = $room->players()->whereDoesntHave('teams')->first();

    $this->travel(3)->minutes();

    $response = test()->actingAs($setup['captainOne'])->post("/rooms/{$room->id}/draft/pick", [
        'player_id' => $available->id,
    ]);

    $response->assertSessionHasErrors('draft');
    expect($room->refresh()->current_pick_index)->toBe(0);
});

test('draft completes when all team slots are filled', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);
    $room->update(['draft_order' => [$setup['teamOne']->id, $setup['teamTwo']->id]]);

    $available = $room->players()->whereDoesntHave('teams')->orderBy('id')->get();

    test()->actingAs($setup['captainOne'])->post("/rooms/{$room->id}/draft/pick", [
        'player_id' => $available[0]->id,
    ]);

    expect($room->refresh()->status)->toBe(RoomStatus::Drafting);

    test()->actingAs($setup['captainTwo'])->post("/rooms/{$room->id}/draft/pick", [
        'player_id' => $available[1]->id,
    ]);

    $room->refresh();

    expect($room->status)->toBe(RoomStatus::Completed);

    foreach ([$setup['teamOne'], $setup['teamTwo']] as $team) {
        expect($team->players()->count())->toBe(2);
    }
});

test('admin can cancel the draft and the room returns to waiting', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);
    $room->update(['draft_order' => [$setup['teamOne']->id, $setup['teamTwo']->id]]);
    $available = $room->players()->whereDoesntHave('teams')->first();

    test()->actingAs($setup['captainOne'])->post("/rooms/{$room->id}/draft/pick", [
        'player_id' => $available->id,
    ]);

    $response = test()->actingAs($setup['admin'])->post("/rooms/{$room->id}/cancel-draft");

    $response->assertRedirect(route('rooms.show', $room));
    $room->refresh();

    expect($room->status)->toBe(RoomStatus::Waiting)
        ->and($room->draft_order)->toBeNull()
        ->and($room->current_pick_index)->toBe(0)
        ->and($room->draft_started_at)->toBeNull()
        ->and($room->draftPicks()->count())->toBe(0)
        ->and($setup['teamOne']->players()->count())->toBe(1)
        ->and($setup['teamTwo']->players()->count())->toBe(1);
});

test('non-admin cannot cancel the draft', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);

    $response = test()->actingAs($setup['captainOne'])->post("/rooms/{$room->id}/cancel-draft");

    $response->assertForbidden();
    expect($room->refresh()->status)->toBe(RoomStatus::Drafting);
});

test('reserves are only available after the main list is exhausted', function () {
    $setup = makeDraftRoom(teamSize: 2, playerCount: 5);
    $room = startDraft($setup);
    $room->update(['draft_order' => [$setup['teamOne']->id, $setup['teamTwo']->id]]);

    $byId = $room->players()->orderBy('id')->get();
    $reserve = $byId[4];

    $available = $room->availableDraftPlayers();

    expect($available->pluck('id')->all())->toContain($byId[2]->id, $byId[3]->id)
        ->and($available->pluck('id')->all())->not->toContain($reserve->id);

    test()->actingAs($setup['captainOne'])->post("/rooms/{$room->id}/draft/pick", [
        'player_id' => $byId[2]->id,
    ]);
    test()->actingAs($setup['captainTwo'])->post("/rooms/{$room->id}/draft/pick", [
        'player_id' => $byId[3]->id,
    ]);

    $availableAfterMain = $room->availableDraftPlayers();

    expect($availableAfterMain->pluck('id')->all())->toBe([$reserve->id]);
});

test('draft current endpoint returns draft state for members', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);
    $room->update(['draft_order' => [$setup['teamOne']->id, $setup['teamTwo']->id]]);

    $response = test()->actingAs($setup['captainOne'])->get("/rooms/{$room->id}/draft/current");

    $response->assertOk()
        ->assertJsonPath('status', 'drafting')
        ->assertJsonPath('current_pick_index', 0)
        ->assertJsonPath('current_team_id', $setup['teamOne']->id)
        ->assertJsonPath('timer_expired', false)
        ->assertJsonPath('picks_count', 2);

    $response->assertJsonPath('available_players.0.id', $room->availableDraftPlayers()->first()->id);
});

test('draft current endpoint forbids non-members', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);
    $outsider = User::factory()->create();

    test()->actingAs($outsider)->get("/rooms/{$room->id}/draft/current")->assertForbidden();
});

test('room show page includes draft picks for the board', function () {
    $setup = makeDraftRoom();
    $room = startDraft($setup);

    $response = test()->actingAs($setup['admin'])->get("/rooms/{$room->id}");

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('rooms/show')
        ->has('room.draft_picks', 2)
        ->where('room.draft_picks.0.auto_picked', true)
        ->where('room.draft_order', $room->draft_order)
    );
});
