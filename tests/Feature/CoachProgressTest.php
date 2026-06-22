<?php

use App\Models\Measurement;
use App\Models\ProgressPhoto;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('local');
});

it('coach can view a client\'s measurements', function () {
    $coach  = User::factory()->create(['role' => 'coach']);
    $client = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($coach);

    Measurement::factory()->count(3)->create(['user_id' => $client->id]);

    $response = $this->getJson("/api/coach/clients/{$client->id}/measurements");

    $response->assertStatus(200);
    $response->assertJsonCount(3);
});

it('coach can view a client\'s photo metadata', function () {
    $coach  = User::factory()->create(['role' => 'coach']);
    $client = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($coach);

    ProgressPhoto::factory()->count(2)->create(['user_id' => $client->id]);

    $response = $this->getJson("/api/coach/clients/{$client->id}/photos");

    $response->assertStatus(200);
    $response->assertJsonCount(2);
});

it('coach photo list returns metadata only and not binary data', function () {
    $coach  = User::factory()->create(['role' => 'coach']);
    $client = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($coach);

    ProgressPhoto::factory()->create(['user_id' => $client->id]);

    $response = $this->getJson("/api/coach/clients/{$client->id}/photos");

    $response->assertStatus(200);
    $response->assertJsonStructure([
        '*' => ['id', 'user_id', 'taken_at', 'storage_path', 'caption', 'created_at', 'updated_at'],
    ]);
    $response->assertJsonMissingPath('0.file');
    $response->assertJsonMissingPath('0.data');
});

it('non-coach gets 403 from coach measurement endpoint', function () {
    $client        = User::factory()->create(['role' => 'client']);
    $targetClient  = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($client);

    Measurement::factory()->create(['user_id' => $targetClient->id]);

    $this->getJson("/api/coach/clients/{$targetClient->id}/measurements")
        ->assertStatus(403);
});

it('non-coach gets 403 from coach photo endpoint', function () {
    $client        = User::factory()->create(['role' => 'client']);
    $targetClient  = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($client);

    ProgressPhoto::factory()->create(['user_id' => $targetClient->id]);

    $this->getJson("/api/coach/clients/{$targetClient->id}/photos")
        ->assertStatus(403);
});

it('returns 401 when unauthenticated on coach endpoints', function () {
    $client = User::factory()->create();

    $this->getJson("/api/coach/clients/{$client->id}/measurements")->assertStatus(401);
    $this->getJson("/api/coach/clients/{$client->id}/photos")->assertStatus(401);
});
