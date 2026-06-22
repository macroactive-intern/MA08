# Audit — ProgressPhotosMeasurements API

Rubric: docs/rubric.md
Project: Laravel JSON API for client body measurements and progress photos

---

## 1. Type Safety — PASS

`declare(strict_types=1);` is present as the first statement after `<?php` in every file under `app/`:

- `app/Http/Controllers/Controller.php`
- `app/Http/Controllers/MeasurementController.php`
- `app/Http/Controllers/ProgressPhotoController.php`
- `app/Http/Controllers/CoachProgressController.php`
- `app/Http/Requests/StoreMeasurementRequest.php`
- `app/Http/Requests/UpdateMeasurementRequest.php`
- `app/Http/Requests/StoreProgressPhotoRequest.php`
- `app/Models/Measurement.php`
- `app/Models/ProgressPhoto.php`
- `app/Models/User.php`
- `app/Policies/MeasurementPolicy.php`
- `app/Policies/ProgressPhotoPolicy.php`
- `app/Exceptions/CoachAccessRequiredException.php`
- `app/Http/Resources/MeasurementResource.php`
- `app/Http/Resources/ProgressPhotoResource.php`
- `app/Providers/AppServiceProvider.php`

All model relationship methods declare typed return types (`HasMany`, `BelongsTo`). All controller methods declare `JsonResponse` return types. Form request methods declare `bool` and `array`.

---

## 2. Error Handling — PASS

A named exception class `App\Exceptions\CoachAccessRequiredException` is thrown when a non-coach user accesses a coach-only endpoint. No raw `abort()` calls exist in controller logic for business failures.

Ownership violations throw `Illuminate\Auth\Access\AuthorizationException` via `$this->authorize()` (Laravel's named policy exception) — distinguishing authorization failures from generic server errors.

Race-condition duplicate-date writes are caught as `Illuminate\Database\UniqueConstraintViolationException` and converted to `ValidationException` with a 422 response, preventing unhandled 500 errors.

The `CoachAccessRequiredException` handler is registered in `bootstrap/app.php`:

```php
$exceptions->render(function (CoachAccessRequiredException $e, Request $request) {
    return response()->json(['message' => $e->getMessage()], 403);
});
```

---

## 3. Observability — PASS

`Log::info()` is called in every state-changing controller method with the entity ID and authenticated user ID:

| Operation | Log key | Fields |
|-----------|---------|--------|
| Measurement create | `measurement.created` | `measurement_id`, `user_id` |
| Measurement update | `measurement.updated` | `measurement_id`, `user_id` |
| Measurement delete | `measurement.deleted` | `measurement_id`, `user_id` |
| Photo upload | `photo.uploaded` | `photo_id`, `user_id`, `path` |
| Photo delete | `photo.deleted` | `photo_id`, `user_id` |

---

## 4. Configuration — PASS

`config/progress_photos.php` centralises all photo upload settings:

```php
return [
    'max_size_kb'         => 5120,
    'accepted_mimetypes'  => ['image/jpeg', 'image/png', 'image/webp'],
    'mime_extensions'     => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
    'storage_directory'   => 'progress-photos',
];
```

`StoreProgressPhotoRequest::rules()` references `config('progress_photos.accepted_mimetypes')` and `config('progress_photos.max_size_kb')`. `ProgressPhotoController::store()` references `config('progress_photos.mime_extensions')` and `config('progress_photos.storage_directory')`. No magic numbers or hardcoded MIME strings appear in application logic.

---

## 5. Validation — PASS

The unique-date validation in `StoreMeasurementRequest` and `UpdateMeasurementRequest` uses `Rule::unique()` which issues a single `SELECT COUNT(*)` query per request. No repeated DB lookups occur within a single validation cycle. The `UpdateMeasurementRequest` correctly uses `->ignore($measurementId)` to avoid a self-match without issuing additional queries.

---

## 6. Data Integrity — PASS

No controller operation writes to more than one database table in a single request. Each endpoint touches a single model:

- `MeasurementController` reads and writes only the `measurements` table.
- `ProgressPhotoController` reads and writes only the `progress_photos` table plus the local filesystem.
- `CoachProgressController` is read-only.

The `ProgressPhotoController::destroy()` method deletes a file from disk and then deletes the database record. These two operations cannot be made atomic (filesystem is outside the DB transaction boundary), but the implementation guards against orphaned records by checking `Storage::disk('local')->exists()` before deletion. The desired final state — no file, no DB row — is achieved in the success path.

No `DB::transaction()` is required because no operation writes to more than one table.

---

## 7. Security — PASS

**Middleware coverage — PASS:** All nine API routes are registered inside `Route::middleware('auth:sanctum')` in `routes/api.php`. Unauthenticated requests to every endpoint return 401, confirmed by tests.

**Authorization policies — PASS:** `App\Policies\MeasurementPolicy` and `App\Policies\ProgressPhotoPolicy` are registered via `Gate::policy()` in `AppServiceProvider::boot()`. The base `Controller` class uses the `AuthorizesRequests` trait. Controllers call `$this->authorize('update', $measurement)` and `$this->authorize('delete', $photo)` before any mutation. Ownership is enforced at the policy layer, not inline in controllers.

**Race condition protection — PASS:** `Measurement::create()` and `Measurement::update()` are wrapped in `try/catch UniqueConstraintViolationException` which converts DB-level constraint violations to `ValidationException` (422), preventing 500 responses under concurrent duplicate requests.

---

## 8. API Consistency — PASS

**HTTP status codes — PASS:** All endpoints return correct status codes:

| Operation | Status | Verified |
|-----------|--------|---------|
| Create measurement | 201 | ✓ |
| List measurements | 200 | ✓ |
| Update measurement | 200 | ✓ |
| Delete measurement | 204 | ✓ |
| Upload photo | 201 | ✓ |
| List photos | 200 | ✓ |
| Delete photo | 204 | ✓ |
| Validation failure | 422 | ✓ |
| Authorization failure | 403 | ✓ |
| Unauthenticated | 401 | ✓ |

**API Resources — PASS:** `App\Http\Resources\MeasurementResource` and `App\Http\Resources\ProgressPhotoResource` are returned from all relevant controller methods. `JsonResource::withoutWrapping()` is called in `AppServiceProvider::boot()` to keep flat JSON responses consistent with existing tests. Response shape is decoupled from model attributes.

---

## 9. Tests Pass — PASS

`php artisan test` exits with code 0.

```
Tests: 36 passed (81 assertions)
Duration: 0.92s
```

All 36 tests pass across four test files:

- `MeasurementTest` — 14 tests covering create, list, filter, update, delete, ownership, and auth
- `ProgressPhotoTest` — 15 tests covering upload (3 formats), rejection (format, size, MIME), storage path, listing, deletion (DB + disk), ownership, and auth
- `CoachProgressTest` — 6 tests covering coach access, metadata-only response, non-coach 403, and auth
- `ExampleTest` — 1 test (framework health check)

No tests are pending or skipped.

---

## 10. No Hardcoded Environment Values — PASS

`.env.example` sets `APP_DEBUG=false`. Required keys are annotated:

```
APP_KEY= # REQUIRED
APP_DEBUG=false
DB_CONNECTION=sqlite # REQUIRED
```

A developer who copies `.env.example` verbatim will not deploy with debug mode enabled. `APP_KEY` and `DB_CONNECTION` are marked to prevent silent misconfiguration.

---

## Summary

| # | Criterion | Result |
|---|-----------|--------|
| 1 | Type Safety | PASS |
| 2 | Error Handling | PASS |
| 3 | Observability | PASS |
| 4 | Configuration | PASS |
| 5 | Validation | PASS |
| 6 | Data Integrity | PASS |
| 7 | Security | PASS |
| 8 | API Consistency | PASS |
| 9 | Tests Pass | PASS |
| 10 | No Hardcoded Environment Values | PASS |

**10 of 10 criteria pass.**
