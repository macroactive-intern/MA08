 --------------------------------------------------------------------------

  Before

--------------------------------------------------------------------------
 
 PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\CoachProgressTest
  ✓ it coach can view a client's measurements                                                                                                0.26s  
  ✓ it coach can view a client's photo metadata                                                                                              0.02s  
  ✓ it coach photo list returns metadata only and not binary data                                                                            0.01s  
  ✓ it non-coach gets 403 from coach measurement endpoint                                                                                    0.01s  
  ✓ it non-coach gets 403 from coach photo endpoint                                                                                          0.01s  
  ✓ it returns 401 when unauthenticated on coach endpoints                                                                                   0.02s  

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                                                                            0.02s  

   FAIL  Tests\Feature\MeasurementTest
  ⨯ it can create a measurement                                                                                                              0.03s  
  ✓ it requires unit_system to be metric or imperial                                                                                         0.01s  
  ⨯ it returns 422 for a duplicate same-day measurement                                                                                      0.02s  
  ✓ it allows different users to create measurements for the same date                                                                       0.01s  
  ✓ it can list own measurements                                                                                                             0.01s  
  ✓ it filters measurements by start_date                                                                                                    0.01s  
  ✓ it filters measurements by end_date                                                                                                      0.01s  
  ✓ it filters measurements by date range                                                                                                    0.01s  
  ⨯ it can update own measurement                                                                                                            0.01s  
  ⨯ it cannot update a measurement to a date already used by the same user                                                                   0.02s  
  ✓ it can delete own measurement                                                                                                            0.01s  
  ✓ it cannot update another user's measurement                                                                                              0.01s  
  ✓ it cannot delete another user's measurement                                                                                              0.01s  
  ✓ it returns 401 when unauthenticated on measurement endpoints                                                                             0.01s  

   PASS  Tests\Feature\ProgressPhotoTest
  ✓ it can upload a JPEG photo                                                                                                               0.03s  
  ✓ it can upload a PNG photo                                                                                                                0.02s  
  ✓ it can upload a WEBP photo                                                                                                               0.02s  
  ✓ it rejects other image formats                                                                                                           0.02s  
  ✓ it rejects files larger than 5 MB                                                                                                        0.02s  
  ✓ it validates MIME type not just file extension                                                                                           0.01s  
  ✓ it stores a system-generated path, not the original filename                                                                             0.02s  
  ✓ it stores the file on disk after upload                                                                                                  0.02s  
  ✓ it can list own photo metadata                                                                                                           0.02s  
  ✓ it photo list returns metadata only and not binary data                                                                                  0.01s  
  ✓ it deleting a photo removes the database record                                                                                          0.01s  
  ✓ it deleting a photo removes the file from disk                                                                                           0.02s  
  ✓ it cannot delete another user's photo                                                                                                    0.02s  
  ✓ it returns 401 when unauthenticated on photo endpoints                                                                                   0.01s  
  ────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\MeasurementTest > it can create a measurement                                                                              
  Failed asserting that a row in the table [measurements] matches the attributes {
    "user_id": 1,
    "measured_at": "2026-06-15"
}.

Found similar results: [
    {
        "user_id": 1,
        "measured_at": "2026-06-15 00:00:00"
    }
].

  at tests\Feature\MeasurementTest.php:20
     16▕         'unit_system'        => 'metric',
     17▕     ]);
     18▕ 
     19▕     $response->assertStatus(201);
  ➜  20▕     $this->assertDatabaseHas('measurements', [
     21▕         'user_id'     => $user->id,
     22▕         'measured_at' => '2026-06-15',
     23▕     ]);
     24▕ });

  ────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\MeasurementTest > it returns 422 for a duplicate same-day measurement                                                      
  Expected response status code [422] but received 500.
Failed asserting that 500 is identical to 422.

SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: measurements.user_id, measurements.measured_at (Connection: sqlite, Database: :memory:, SQL: insert into "measurements" ("user_id", "measured_at", "weight", "body_fat_percentage", "notes", "unit_system", "updated_at", "created_at") values (1, 2026-06-15 00:00:00, ?, ?, ?, metric, 2026-06-22 01:22:46, 2026-06-22 01:22:46))

  at tests\Feature\MeasurementTest.php:48
     44▕ 
     45▕     $this->postJson('/api/measurements', [
     46▕         'measured_at' => '2026-06-15',
     47▕         'unit_system' => 'metric',
  ➜  48▕     ])->assertStatus(422);
     49▕ });
     50▕ 
     51▕ it('allows different users to create measurements for the same date', function () {
     52▕     $user1 = User::factory()->create();

  ────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\MeasurementTest > it can update own measurement                                                                            
  Failed asserting that a row in the table [measurements] matches the attributes {
    "id": 1,
    "measured_at": "2026-06-16"
}.

Found similar results: [
    {
        "id": 1,
        "measured_at": "2026-06-16 00:00:00"
    }
].

  at tests\Feature\MeasurementTest.php:139
    135▕         'weight'      => 83.10,
    136▕     ]);
    137▕ 
    138▕     $response->assertStatus(200);
  ➜ 139▕     $this->assertDatabaseHas('measurements', [
    140▕         'id'          => $measurement->id,
    141▕         'measured_at' => '2026-06-16',
    142▕     ]);
    143▕ });

  ────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\MeasurementTest > it cannot update a measurement to a date already used by the same user                                   
  Expected response status code [422] but received 500.
Failed asserting that 500 is identical to 422.

SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: measurements.user_id, measurements.measured_at (Connection: sqlite, Database: :memory:, SQL: update "measurements" set "measured_at" = 2026-06-15 00:00:00, "updated_at" = 2026-06-22 01:22:46 where "id" = 2)

  at tests\Feature\MeasurementTest.php:155
    151▕ 
    152▕     $this->putJson("/api/measurements/{$measurement->id}", [
    153▕         'measured_at' => '2026-06-15',
    154▕         'unit_system' => 'metric',
  ➜ 155▕     ])->assertStatus(422);
    156▕ });
    157▕ 
    158▕ it('can delete own measurement', function () {
    159▕     $user = User::factory()->create();


  Tests:    4 failed, 32 passed (81 assertions)
  Duration: 0.99s

--------------------------------------------------------------------------

  After

--------------------------------------------------------------------------
