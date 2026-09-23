<?php

use App\Models\Player;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

test('room member can mark player paid with a reference image', function () {
    Storage::fake('payments');

    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => false]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($user)->post("/rooms/{$room->id}/players/{$player->id}/pay", [
        'reference_image' => UploadedFile::fake()->image('receipt.jpg'),
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    $this->assertDatabaseHas('payments', [
        'player_id' => $player->id,
        'marked_by_user_id' => $user->id,
    ]);

    $payment = $player->payment()->first();
    expect($payment)->not->toBeNull()
        ->and($payment->reference_image_path)->not->toBeNull()
        ->and($payment->paid_at)->not->toBeNull();

    Storage::disk('payments')->assertExists($payment->reference_image_path);
});

test('admin can mark player paid without a reference image', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($user)->post("/rooms/{$room->id}/players/{$player->id}/pay");

    $response->assertRedirect(route('rooms.show', $room));
    $this->assertDatabaseHas('payments', [
        'player_id' => $player->id,
        'marked_by_user_id' => $user->id,
        'reference_image_path' => null,
    ]);
});

test('non-admin cannot mark player paid without a reference image', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => false]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($user)->post("/rooms/{$room->id}/players/{$player->id}/pay");

    $response->assertSessionHasErrors('reference_image');
    $this->assertDatabaseMissing('payments', [
        'player_id' => $player->id,
    ]);
});

test('mark paid rejects files that are not images', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => false]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($user)->post("/rooms/{$room->id}/players/{$player->id}/pay", [
        'reference_image' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors('reference_image');
    $this->assertDatabaseMissing('payments', [
        'player_id' => $player->id,
    ]);
});

test('non-member cannot mark player paid', function () {
    $creator = User::factory()->create();
    $nonMember = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $creator->id]);
    $room->users()->attach($creator->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id]);

    $response = $this->actingAs($nonMember)->post("/rooms/{$room->id}/players/{$player->id}/pay", [
        'reference_image' => UploadedFile::fake()->image('receipt.jpg'),
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('payments', [
        'player_id' => $player->id,
    ]);
});

test('cannot mark player paid when player belongs to another room', function () {
    $user = User::factory()->create();
    $room1 = Room::factory()->create(['user_id' => $user->id]);
    $room2 = Room::factory()->create(['user_id' => $user->id]);
    $room1->users()->attach($user->id, ['is_admin' => true]);
    $room2->users()->attach($user->id, ['is_admin' => true]);

    $player = Player::factory()->create(['room_id' => $room2->id]);

    $response = $this->actingAs($user)->post("/rooms/{$room1->id}/players/{$player->id}/pay", [
        'reference_image' => UploadedFile::fake()->image('receipt.jpg'),
    ]);

    $response->assertNotFound();
    $this->assertDatabaseMissing('payments', [
        'player_id' => $player->id,
    ]);
});

test('room show page includes payment status for players', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id, 'name' => 'Paid Player']);
    $player->payment()->create([
        'marked_by_user_id' => $user->id,
        'reference_image_path' => null,
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($user)->get("/rooms/{$room->id}");

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('rooms/show')
        ->has('room.players', 1)
        ->where('room.players.0.name', 'Paid Player')
        ->where('room.players.0.payment.marked_by_user_id', $user->id)
    );
});

test('room member can view a payment reference image', function () {
    Storage::fake('payments');

    $user = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $user->id]);
    $room->users()->attach($user->id, ['is_admin' => false]);
    $player = Player::factory()->create(['room_id' => $room->id]);
    $player->payment()->create([
        'marked_by_user_id' => $user->id,
        'reference_image_path' => "payments/{$room->id}/{$player->id}_1.png",
        'paid_at' => now(),
    ]);

    Storage::disk('payments')->put(
        "payments/{$room->id}/{$player->id}_1.png",
        UploadedFile::fake()->image('receipt.png')->getContent()
    );

    $response = $this->actingAs($user)->get("/rooms/{$room->id}/players/{$player->id}/payment");

    $response->assertOk();
});

test('non-member cannot view a payment reference image', function () {
    Storage::fake('payments');

    $creator = User::factory()->create();
    $nonMember = User::factory()->create();
    $room = Room::factory()->create(['user_id' => $creator->id]);
    $room->users()->attach($creator->id, ['is_admin' => true]);
    $player = Player::factory()->create(['room_id' => $room->id]);
    $player->payment()->create([
        'marked_by_user_id' => $creator->id,
        'reference_image_path' => "payments/{$room->id}/{$player->id}_1.png",
        'paid_at' => now(),
    ]);

    Storage::disk('payments')->put(
        "payments/{$room->id}/{$player->id}_1.png",
        UploadedFile::fake()->image('receipt.png')->getContent()
    );

    $response = $this->actingAs($nonMember)->get("/rooms/{$room->id}/players/{$player->id}/payment");

    $response->assertForbidden();
});
