<?php

use App\Models\Room;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated user can create a room', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/rooms', [
        'name' => 'Sunday Match',
        'date' => now()->addWeek()->format('Y-m-d'),
        'team_size' => 5,
        'num_teams' => 2,
        'price_in_cents' => 1000,
        'currency' => 'USD',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('rooms', [
        'name' => 'Sunday Match',
        'user_id' => $user->id,
        'team_size' => 5,
        'num_teams' => 2,
        'price_in_cents' => 1000,
        'currency' => 'USD',
        'status' => 'waiting',
    ]);
});

test('unauthenticated user cannot create a room', function () {
    $response = $this->post('/rooms', [
        'name' => 'Sunday Match',
        'date' => now()->addWeek()->format('Y-m-d'),
        'team_size' => 5,
        'num_teams' => 2,
        'price_in_cents' => 1000,
        'currency' => 'USD',
    ]);

    $response->assertRedirect('/login');
});

test('room requires valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/rooms', []);

    $response->assertSessionHasErrors(['name', 'date', 'team_size', 'num_teams', 'price_in_cents', 'currency']);
});

test('room requires valid team size', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/rooms', [
        'name' => 'Sunday Match',
        'date' => now()->addWeek()->format('Y-m-d'),
        'team_size' => 3, // invalid
        'num_teams' => 2,
        'price_in_cents' => 1000,
        'currency' => 'USD',
    ]);

    $response->assertSessionHasErrors(['team_size']);
});

test('room requires valid currency', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/rooms', [
        'name' => 'Sunday Match',
        'date' => now()->addWeek()->format('Y-m-d'),
        'team_size' => 5,
        'num_teams' => 2,
        'price_in_cents' => 1000,
        'currency' => 'INVALID',
    ]);

    $response->assertSessionHasErrors(['currency']);
});

test('creator is automatically assigned as admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/rooms', [
        'name' => 'Sunday Match',
        'date' => now()->addWeek()->format('Y-m-d'),
        'team_size' => 5,
        'num_teams' => 2,
        'price_in_cents' => 1000,
        'currency' => 'USD',
    ]);

    $room = Room::where('name', 'Sunday Match')->first();
    $this->assertDatabaseHas('room_users', [
        'room_id' => $room->id,
        'user_id' => $user->id,
        'is_admin' => true,
    ]);
});

test('room generates unique invite code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/rooms', [
        'name' => 'Sunday Match',
        'date' => now()->addWeek()->format('Y-m-d'),
        'team_size' => 5,
        'num_teams' => 2,
        'price_in_cents' => 1000,
        'currency' => 'USD',
    ]);

    $room = Room::where('name', 'Sunday Match')->first();
    $this->assertNotEmpty($room->invite_code);
    $this->assertEquals(6, strlen($room->invite_code));
});

test('user can view dashboard with their rooms', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('rooms')
    );
});

test('user can view room details', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);

    $response = $this->actingAs($user)->get("/rooms/{$room->id}");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('rooms/show')
        ->has('room')
    );
});

test('admin can update room settings', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);

    $response = $this->actingAs($user)->patch("/rooms/{$room->id}", [
        'name' => 'Updated Match',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'name' => 'Updated Match',
    ]);
});

test('non-admin cannot update room settings', function () {
    $admin = User::factory()->create();
    $nonAdmin = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($nonAdmin->id, ['is_admin' => false]);

    $response = $this->actingAs($nonAdmin)->patch("/rooms/{$room->id}", [
        'name' => 'Updated Match',
    ]);

    $response->assertForbidden();
});

test('user can join room by invite code', function () {
    $admin = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id, 'invite_code' => 'ABC123']);
    $room->users()->attach($admin->id, ['is_admin' => true]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/rooms/join', [
        'invite_code' => 'ABC123',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('room_users', [
        'room_id' => $room->id,
        'user_id' => $user->id,
        'is_admin' => false,
    ]);
});

test('user cannot join room with invalid invite code', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/rooms/join', [
        'invite_code' => 'INVALID',
    ]);

    $response->assertSessionHasErrors(['invite_code']);
});

test('user can join room they already belong to', function () {
    $admin = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id, 'invite_code' => 'ABC123']);
    $room->users()->attach($admin->id, ['is_admin' => true]);

    $response = $this->actingAs($admin)->post('/rooms/join', [
        'invite_code' => 'ABC123',
    ]);

    $response->assertRedirect();
});

test('admin can assign admin role to another user', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($user->id, ['is_admin' => false]);

    $response = $this->actingAs($admin)->post("/rooms/{$room->id}/assign-admin", [
        'user_id' => $user->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('room_users', [
        'room_id' => $room->id,
        'user_id' => $user->id,
        'is_admin' => true,
    ]);
});

test('non-admin cannot assign admin role', function () {
    $admin = User::factory()->create();
    $nonAdmin = User::factory()->create();
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $admin->id]);
    $room->users()->attach($admin->id, ['is_admin' => true]);
    $room->users()->attach($nonAdmin->id, ['is_admin' => false]);
    $room->users()->attach($user->id, ['is_admin' => false]);

    $response = $this->actingAs($nonAdmin)->post("/rooms/{$room->id}/assign-admin", [
        'user_id' => $user->id,
    ]);

    $response->assertForbidden();
});
