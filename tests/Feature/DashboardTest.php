<?php

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('completed rooms are archived away from the main dashboard list', function () {
    $user = User::factory()->create();

    $active = Room::factory()->create(['user_id' => $user->id, 'status' => RoomStatus::Waiting]);
    $active->users()->attach($user->id, ['is_admin' => true]);

    Room::factory()->create(['user_id' => $user->id, 'status' => RoomStatus::Completed])
        ->users()->attach($user->id, ['is_admin' => true]);
    Room::factory()->create(['user_id' => $user->id, 'status' => RoomStatus::Completed])
        ->users()->attach($user->id, ['is_admin' => true]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('rooms', 1)
        ->where('rooms.0.id', $active->id)
        ->has('archivedRooms', 2)
    );
});

test('completed rooms the user joined are archived as well', function () {
    $user = User::factory()->create();

    $joined = Room::factory()->create(['status' => RoomStatus::Completed]);
    $joined->users()->attach($user->id, ['is_admin' => false]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('rooms', 0)
        ->has('archivedRooms', 1)
        ->where('archivedRooms.0.id', $joined->id)
    );
});

test('waiting and drafting rooms stay on the main dashboard list', function () {
    $user = User::factory()->create();

    foreach ([RoomStatus::Waiting, RoomStatus::Full, RoomStatus::Drafting] as $status) {
        Room::factory()->create(['user_id' => $user->id, 'status' => $status])
            ->users()->attach($user->id, ['is_admin' => true]);
    }

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('rooms', 3)
        ->has('archivedRooms', 0)
    );
});
