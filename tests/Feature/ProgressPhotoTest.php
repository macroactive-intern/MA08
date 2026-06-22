<?php

use App\Models\ProgressPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('local');
});

it('can upload a JPEG photo', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $response = $this->postJson('/api/photos', [
        'photo'    => $file,
        'taken_at' => '2026-06-15',
        'caption'  => 'Morning check-in',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseCount('progress_photos', 1);
});

it('can upload a PNG photo', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('photo.png', 100, 100);

    $this->postJson('/api/photos', [
        'photo'    => $file,
        'taken_at' => '2026-06-15',
    ])->assertStatus(201);
});

it('can upload a WEBP photo', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('photo.webp', 100, 100);

    $this->postJson('/api/photos', [
        'photo'    => $file,
        'taken_at' => '2026-06-15',
    ])->assertStatus(201);
});

it('rejects other image formats', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // A file declared as image/gif — not in the accepted MIME list
    $file = UploadedFile::fake()->create('photo.gif', 100, 'image/gif');

    $this->postJson('/api/photos', [
        'photo'    => $file,
        'taken_at' => '2026-06-15',
    ])->assertStatus(422);
});

it('rejects files larger than 5 MB', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // 6 MB — exceeds the 5 MB limit
    $file = UploadedFile::fake()->create('photo.jpg', 6144, 'image/jpeg');

    $this->postJson('/api/photos', [
        'photo'    => $file,
        'taken_at' => '2026-06-15',
    ])->assertStatus(422);
});

it('validates MIME type not just file extension', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Non-image content with a .jpg extension
    $file = UploadedFile::fake()->create('photo.jpg', 100, 'application/pdf');

    $this->postJson('/api/photos', [
        'photo'    => $file,
        'taken_at' => '2026-06-15',
    ])->assertStatus(422);
});

it('stores a system-generated path, not the original filename', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('my-personal-photo.jpg', 100, 100);

    $response = $this->postJson('/api/photos', [
        'photo'    => $file,
        'taken_at' => '2026-06-15',
    ]);

    $response->assertStatus(201);

    $storagePath = $response->json('storage_path');

    expect($storagePath)->not->toContain('my-personal-photo');
    expect($storagePath)->toStartWith('progress-photos/');
});

it('stores the file on disk after upload', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $response = $this->postJson('/api/photos', [
        'photo'    => $file,
        'taken_at' => '2026-06-15',
    ]);

    $response->assertStatus(201);

    $storagePath = $response->json('storage_path');

    Storage::disk('local')->assertExists($storagePath);
});

it('can list own photo metadata', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    ProgressPhoto::factory()->create(['user_id' => $user->id]);
    ProgressPhoto::factory()->create(['user_id' => $other->id]);

    $response = $this->getJson('/api/photos');

    $response->assertStatus(200);
    $response->assertJsonCount(1);
});

it('photo list returns metadata only and not binary data', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    ProgressPhoto::factory()->create(['user_id' => $user->id]);

    $response = $this->getJson('/api/photos');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        '*' => ['id', 'user_id', 'taken_at', 'storage_path', 'caption', 'created_at', 'updated_at'],
    ]);
    // No binary image data field in the response
    $response->assertJsonMissingPath('0.file');
    $response->assertJsonMissingPath('0.contents');
    $response->assertJsonMissingPath('0.data');
});

it('deleting a photo removes the database record', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $photo = ProgressPhoto::factory()->create(['user_id' => $user->id]);

    $this->deleteJson("/api/photos/{$photo->id}")->assertStatus(204);

    $this->assertDatabaseMissing('progress_photos', ['id' => $photo->id]);
});

it('deleting a photo removes the file from disk', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $response = $this->postJson('/api/photos', [
        'photo'    => $file,
        'taken_at' => '2026-06-15',
    ]);

    $storagePath = $response->json('storage_path');
    $photoId     = $response->json('id');

    Storage::disk('local')->assertExists($storagePath);

    $this->deleteJson("/api/photos/{$photoId}")->assertStatus(204);

    Storage::disk('local')->assertMissing($storagePath);
});

it('cannot delete another user\'s photo', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    $photo = ProgressPhoto::factory()->create(['user_id' => $other->id]);

    $this->deleteJson("/api/photos/{$photo->id}")->assertStatus(403);
    $this->assertDatabaseHas('progress_photos', ['id' => $photo->id]);
});

it('returns 401 when unauthenticated on photo endpoints', function () {
    $this->getJson('/api/photos')->assertStatus(401);
    $this->postJson('/api/photos', [])->assertStatus(401);
    $this->deleteJson('/api/photos/1')->assertStatus(401);
});
