<?php

use App\Models\Measurement;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('can create a measurement', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/measurements', [
        'measured_at'        => '2026-06-15',
        'weight'             => 82.50,
        'body_fat_percentage' => 18.4,
        'notes'              => 'Felt good today',
        'unit_system'        => 'metric',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('measurements', [
        'user_id'     => $user->id,
        'measured_at' => '2026-06-15',
    ]);
});

it('requires unit_system to be metric or imperial', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/measurements', [
        'measured_at' => '2026-06-15',
        'unit_system' => 'kilograms',
    ])->assertStatus(422);
});

it('returns 422 for a duplicate same-day measurement', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Measurement::factory()->create([
        'user_id'     => $user->id,
        'measured_at' => '2026-06-15',
    ]);

    $this->postJson('/api/measurements', [
        'measured_at' => '2026-06-15',
        'unit_system' => 'metric',
    ])->assertStatus(422);
});

it('allows different users to create measurements for the same date', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Measurement::factory()->create([
        'user_id'     => $user1->id,
        'measured_at' => '2026-06-15',
    ]);

    Sanctum::actingAs($user2);

    $this->postJson('/api/measurements', [
        'measured_at' => '2026-06-15',
        'unit_system' => 'metric',
    ])->assertStatus(201);
});

it('can list own measurements', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    Measurement::factory()->create(['user_id' => $user->id]);
    Measurement::factory()->create(['user_id' => $other->id]);

    $response = $this->getJson('/api/measurements');

    $response->assertStatus(200);
    $response->assertJsonCount(1);
});

it('filters measurements by start_date', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Measurement::factory()->create(['user_id' => $user->id, 'measured_at' => '2026-01-01']);
    Measurement::factory()->create(['user_id' => $user->id, 'measured_at' => '2026-06-15']);

    $response = $this->getJson('/api/measurements?start_date=2026-06-01');

    $response->assertStatus(200);
    $response->assertJsonCount(1);
});

it('filters measurements by end_date', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Measurement::factory()->create(['user_id' => $user->id, 'measured_at' => '2026-01-01']);
    Measurement::factory()->create(['user_id' => $user->id, 'measured_at' => '2026-06-15']);

    $response = $this->getJson('/api/measurements?end_date=2026-03-01');

    $response->assertStatus(200);
    $response->assertJsonCount(1);
});

it('filters measurements by date range', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Measurement::factory()->create(['user_id' => $user->id, 'measured_at' => '2026-01-01']);
    Measurement::factory()->create(['user_id' => $user->id, 'measured_at' => '2026-06-15']);
    Measurement::factory()->create(['user_id' => $user->id, 'measured_at' => '2026-12-31']);

    $response = $this->getJson('/api/measurements?start_date=2026-06-01&end_date=2026-06-30');

    $response->assertStatus(200);
    $response->assertJsonCount(1);
});

it('can update own measurement', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $measurement = Measurement::factory()->create([
        'user_id'     => $user->id,
        'measured_at' => '2026-06-15',
        'unit_system' => 'metric',
    ]);

    $response = $this->putJson("/api/measurements/{$measurement->id}", [
        'measured_at' => '2026-06-16',
        'unit_system' => 'metric',
        'weight'      => 83.10,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('measurements', [
        'id'          => $measurement->id,
        'measured_at' => '2026-06-16',
    ]);
});

it('cannot update a measurement to a date already used by the same user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Measurement::factory()->create(['user_id' => $user->id, 'measured_at' => '2026-06-15']);
    $measurement = Measurement::factory()->create(['user_id' => $user->id, 'measured_at' => '2026-06-16']);

    $this->putJson("/api/measurements/{$measurement->id}", [
        'measured_at' => '2026-06-15',
        'unit_system' => 'metric',
    ])->assertStatus(422);
});

it('can delete own measurement', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $measurement = Measurement::factory()->create(['user_id' => $user->id]);

    $this->deleteJson("/api/measurements/{$measurement->id}")->assertStatus(204);
    $this->assertDatabaseMissing('measurements', ['id' => $measurement->id]);
});

it('cannot update another user\'s measurement', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    $measurement = Measurement::factory()->create(['user_id' => $other->id]);

    $this->putJson("/api/measurements/{$measurement->id}", [
        'measured_at' => '2026-06-20',
        'unit_system' => 'metric',
    ])->assertStatus(403);
});

it('cannot delete another user\'s measurement', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    $measurement = Measurement::factory()->create(['user_id' => $other->id]);

    $this->deleteJson("/api/measurements/{$measurement->id}")->assertStatus(403);
});

it('returns 401 when unauthenticated on measurement endpoints', function () {
    $this->getJson('/api/measurements')->assertStatus(401);
    $this->postJson('/api/measurements', [])->assertStatus(401);
    $this->putJson('/api/measurements/1', [])->assertStatus(401);
    $this->deleteJson('/api/measurements/1')->assertStatus(401);
});
