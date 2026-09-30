<?php

use App\Enums\RoomStatus;
use App\Events\CaptainAssigned;
use App\Events\DraftCompleted;
use App\Events\DraftPickMade;
use App\Events\DraftStarted;
use App\Events\NewMessage;
use App\Events\PaymentMarked;
use App\Events\PlayerAdded;
use App\Events\PlayerRemoved;
use App\Events\PlayerUpdated;
use App\Events\RoomUpdated;
use App\Events\TeamUpdated;
use App\Models\Message;
use App\Models\Player;
use App\Models\Room;
use App\Models\Team;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Queue;

function useReverbForChannelAuth(): void
{
    config(['broadcasting.default' => 'reverb']);

    require base_path('routes/channels.php');
}

/**
 * @return array{admin: User, room: Room}
 */
function makeBroadcastRoom(): array
{
    $admin = User::factory()->create();

    $room = Room::factory()->create([
        'user_id' => $admin->id,
        'num_teams' => 2,
        'team_size' => 2,
        'status' => RoomStatus::Waiting,
    ]);

    $room->users()->attach($admin->id, ['is_admin' => true]);

    return ['admin' => $admin, 'room' => $room];
}

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
function makeBroadcastDraftRoom(): array
{
    $admin = User::factory()->create();
    $captainOne = User::factory()->create();
    $captainTwo = User::factory()->create();

    $room = Room::factory()->create([
        'user_id' => $admin->id,
        'num_teams' => 2,
        'team_size' => 2,
        'status' => RoomStatus::Full,
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
 * @param  array{
 *     admin: User,
 *     room: Room,
 *     teamOne: Team,
 *     teamTwo: Team,
 * }  $setup
 */
function startBroadcastDraft(array $setup): Room
{
    test()->actingAs($setup['admin'])
        ->post("/rooms/{$setup['room']->id}/start-draft")
        ->assertRedirect(route('rooms.show', $setup['room']));

    $room = $setup['room']->refresh();
    $room->update(['draft_order' => [$setup['teamOne']->id, $setup['teamTwo']->id]]);

    return $room;
}

function assertRoomBroadcast(string $eventClass, Room $room): void
{
    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($eventClass, $room) {
        return $job->event instanceof $eventClass
            && $job->event->broadcastOn()[0]->name === "presence-room.{$room->id}";
    });
}

test('room member can authorize subscription to the room presence channel', function () {
    useReverbForChannelAuth();

    $user = User::factory()->create();
    $room = Room::factory()->create();
    $room->users()->attach($user->id, ['is_admin' => false]);

    $response = $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => "presence-room.{$room->id}",
        'socket_id' => '123.456',
    ]);

    $response->assertOk();
});

test('non-member cannot authorize subscription to the room presence channel', function () {
    useReverbForChannelAuth();

    $user = User::factory()->create();
    $room = Room::factory()->create();

    $response = $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => "presence-room.{$room->id}",
        'socket_id' => '123.456',
    ]);

    $response->assertForbidden();
});

test('guest cannot authorize subscription to the room presence channel', function () {
    useReverbForChannelAuth();

    $room = Room::factory()->create();

    $response = $this->post('/broadcasting/auth', [
        'channel_name' => "presence-room.{$room->id}",
        'socket_id' => '123.456',
    ]);

    $response->assertForbidden();
});

test('updating a room broadcasts room updated on the room channel', function () {
    $setup = makeBroadcastRoom();

    Queue::fake();

    $response = $this->actingAs($setup['admin'])->patch("/rooms/{$setup['room']->id}", [
        'name' => 'Renamed Room',
    ]);

    $response->assertRedirect(route('rooms.show', $setup['room']));
    assertRoomBroadcast(RoomUpdated::class, $setup['room']);
});

test('adding a player broadcasts player added on the room channel', function () {
    $setup = makeBroadcastRoom();

    Queue::fake();

    $response = $this->actingAs($setup['admin'])->post("/rooms/{$setup['room']->id}/players", [
        'name' => 'Lionel Messi',
        'rating' => 5,
    ]);

    $response->assertRedirect(route('rooms.show', $setup['room']));
    assertRoomBroadcast(PlayerAdded::class, $setup['room']);
});

test('updating a player broadcasts player updated on the room channel', function () {
    $setup = makeBroadcastRoom();
    $player = Player::factory()->create(['room_id' => $setup['room']->id]);

    Queue::fake();

    $response = $this->actingAs($setup['admin'])->patch(
        "/rooms/{$setup['room']->id}/players/{$player->id}",
        ['name' => 'Updated Player'],
    );

    $response->assertRedirect(route('rooms.show', $setup['room']));
    assertRoomBroadcast(PlayerUpdated::class, $setup['room']);
});

test('removing a player broadcasts player removed on the room channel', function () {
    $setup = makeBroadcastRoom();
    $player = Player::factory()->create(['room_id' => $setup['room']->id]);

    Queue::fake();

    $response = $this->actingAs($setup['admin'])->delete("/rooms/{$setup['room']->id}/players/{$player->id}");

    $response->assertRedirect(route('rooms.show', $setup['room']));
    assertRoomBroadcast(PlayerRemoved::class, $setup['room']);
});

test('marking a player paid broadcasts payment marked on the room channel', function () {
    $setup = makeBroadcastRoom();
    $player = Player::factory()->create(['room_id' => $setup['room']->id]);

    Queue::fake();

    $response = $this->actingAs($setup['admin'])->post(
        "/rooms/{$setup['room']->id}/players/{$player->id}/pay",
    );

    $response->assertRedirect(route('rooms.show', $setup['room']));
    assertRoomBroadcast(PaymentMarked::class, $setup['room']);
});

test('updating a team broadcasts team updated on the room channel', function () {
    $setup = makeBroadcastRoom();
    $setup['room']->update(['status' => RoomStatus::Completed]);
    $team = $setup['room']->teams()->create([
        'captain_user_id' => $setup['admin']->id,
        'pick_order' => 1,
    ]);

    Queue::fake();

    $response = $this->actingAs($setup['admin'])->patch(
        "/rooms/{$setup['room']->id}/teams/{$team->id}",
        ['name' => 'Renamed Team'],
    );

    $response->assertRedirect(route('rooms.show', $setup['room']));
    assertRoomBroadcast(TeamUpdated::class, $setup['room']);
});

test('assigning captains broadcasts captain assigned per team on the room channel', function () {
    $setup = makeBroadcastRoom();
    $room = $setup['room'];

    $captainOne = User::factory()->create();
    $captainTwo = User::factory()->create();
    $room->users()->attach($captainOne->id, ['is_admin' => false]);
    $room->users()->attach($captainTwo->id, ['is_admin' => false]);

    $players = Player::factory()->count(2)->create(['room_id' => $room->id]);

    Queue::fake();

    $response = $this->actingAs($setup['admin'])->post("/rooms/{$room->id}/assign-captains", [
        'captains' => [
            ['user_id' => $captainOne->id, 'player_id' => $players[0]->id],
            ['user_id' => $captainTwo->id, 'player_id' => $players[1]->id],
        ],
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    Queue::assertPushedTimes(BroadcastEvent::class, 2);
    assertRoomBroadcast(CaptainAssigned::class, $room);
});

test('starting the draft broadcasts draft started on the room channel', function () {
    $setup = makeBroadcastDraftRoom();

    Queue::fake();

    $response = $this->actingAs($setup['admin'])->post("/rooms/{$setup['room']->id}/start-draft");

    $response->assertRedirect(route('rooms.show', $setup['room']));
    assertRoomBroadcast(DraftStarted::class, $setup['room']);
});

test('recording a pick broadcasts draft pick made on the room channel', function () {
    $setup = makeBroadcastDraftRoom();
    $room = startBroadcastDraft($setup);

    $available = $room->players()->where('is_captain', false)->whereDoesntHave('teams')->firstOrFail();

    Queue::fake();

    $response = $this->actingAs($setup['captainOne'])->post("/rooms/{$room->id}/draft/pick", [
        'player_id' => $available->id,
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    assertRoomBroadcast(DraftPickMade::class, $room);
});

test('the final pick broadcasts draft completed on the room channel', function () {
    $setup = makeBroadcastDraftRoom();
    $room = startBroadcastDraft($setup);

    $available = $room->players()->where('is_captain', false)->whereDoesntHave('teams')->get();

    Queue::fake();

    $this->actingAs($setup['captainOne'])
        ->post("/rooms/{$room->id}/draft/pick", ['player_id' => $available[0]->id])
        ->assertRedirect(route('rooms.show', $room));

    $this->actingAs($setup['captainTwo'])
        ->post("/rooms/{$room->id}/draft/pick", ['player_id' => $available[1]->id])
        ->assertRedirect(route('rooms.show', $room));

    assertRoomBroadcast(DraftCompleted::class, $room);
});

test('a new message broadcasts on the room channel', function () {
    $setup = makeBroadcastRoom();

    Queue::fake();

    event(new NewMessage($setup['room'], (new Message)->forceFill(['room_id' => $setup['room']->id])));

    assertRoomBroadcast(NewMessage::class, $setup['room']);
});
