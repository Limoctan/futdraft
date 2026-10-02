<?php

use App\Events\NewMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array{member: User, room: Room}
 */
function makeChatRoom(): array
{
    $member = User::factory()->create();

    $room = Room::factory()->create(['user_id' => $member->id]);
    $room->users()->attach($member->id, ['is_admin' => true]);

    return ['member' => $member, 'room' => $room];
}

test('a room member can send a message', function () {
    $setup = makeChatRoom();

    $response = $this->actingAs($setup['member'])->post("/rooms/{$setup['room']->id}/messages", [
        'body' => 'See you at 8',
    ]);

    $response->assertRedirect(route('rooms.show', $setup['room']));
    $this->assertDatabaseHas('messages', [
        'room_id' => $setup['room']->id,
        'user_id' => $setup['member']->id,
        'body' => 'See you at 8',
    ]);
});

test('sending a message broadcasts new message on the room channel', function () {
    $setup = makeChatRoom();

    Queue::fake();

    $response = $this->actingAs($setup['member'])->post("/rooms/{$setup['room']->id}/messages", [
        'body' => 'Goal!',
    ]);

    $response->assertRedirect(route('rooms.show', $setup['room']));
    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($setup) {
        return $job->event instanceof NewMessage
            && $job->event->broadcastOn()[0]->name === "presence-room.{$setup['room']->id}";
    });
});

test('sending a message requires a body', function () {
    $setup = makeChatRoom();

    $response = $this->actingAs($setup['member'])->post("/rooms/{$setup['room']->id}/messages", [
        'body' => '',
    ]);

    $response->assertSessionHasErrors('body');
    $this->assertDatabaseCount('messages', 0);
});

test('sending a message rejects a body longer than 1000 characters', function () {
    $setup = makeChatRoom();

    $response = $this->actingAs($setup['member'])->post("/rooms/{$setup['room']->id}/messages", [
        'body' => str_repeat('a', 1001),
    ]);

    $response->assertSessionHasErrors('body');
    $this->assertDatabaseCount('messages', 0);
});

test('a non-member cannot send a message', function () {
    $setup = makeChatRoom();
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->post("/rooms/{$setup['room']->id}/messages", [
        'body' => 'Let me in',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('messages', 0);
});

test('a guest is redirected to login when sending a message', function () {
    $setup = makeChatRoom();

    $response = $this->post("/rooms/{$setup['room']->id}/messages", [
        'body' => 'Hello',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertDatabaseCount('messages', 0);
});

test('a room member can list room messages with sender and timestamp', function () {
    $setup = makeChatRoom();
    $teammate = User::factory()->create();
    $setup['room']->users()->attach($teammate->id, ['is_admin' => false]);

    $setup['room']->messages()->create(['user_id' => $setup['member']->id, 'body' => 'First']);
    $setup['room']->messages()->create(['user_id' => $teammate->id, 'body' => 'Second']);

    $response = $this->actingAs($setup['member'])->get("/rooms/{$setup['room']->id}/messages");

    $response->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.body', 'First')
        ->assertJsonPath('0.user.username', $setup['member']->username)
        ->assertJsonPath('1.body', 'Second')
        ->assertJsonPath('1.user.username', $teammate->username)
        ->assertJsonStructure(['*' => ['id', 'body', 'created_at', 'user' => ['id', 'username']]]);
});

test('listing messages excludes messages from other rooms', function () {
    $setup = makeChatRoom();
    $otherSetup = makeChatRoom();

    $setup['room']->messages()->create(['user_id' => $setup['member']->id, 'body' => 'Mine']);
    $otherSetup['room']->messages()->create(['user_id' => $otherSetup['member']->id, 'body' => 'Theirs']);

    $response = $this->actingAs($setup['member'])->get("/rooms/{$setup['room']->id}/messages");

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.body', 'Mine');
});

test('a non-member cannot list messages', function () {
    $setup = makeChatRoom();
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->get("/rooms/{$setup['room']->id}/messages");

    $response->assertForbidden();
});

test('the room page includes chat messages with sender and timestamp', function () {
    $setup = makeChatRoom();
    $setup['room']->messages()->create(['user_id' => $setup['member']->id, 'body' => 'On my way']);

    $response = $this->actingAs($setup['member'])->get("/rooms/{$setup['room']->id}");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('rooms/show')
        ->has('room.messages', 1)
        ->where('room.messages.0.body', 'On my way')
        ->where('room.messages.0.user.username', $setup['member']->username)
        ->has('room.messages.0.created_at')
    );
});
