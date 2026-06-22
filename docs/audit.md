# Audit — ProgressPhotosMeasurements API

Rubric: docs/rubric.md
Project: Laravel JSON API for client body measurements and progress photos

---

## 1. Type Safety — FAIL

No PHP file under `app/` contains `declare(strict_types=1)`.

Affected files (all):

- `app/Http/Controllers/MeasurementController.php`
- `app/Http/Controllers/ProgressPhotoController.php`
- `app/Http/Controllers/CoachProgressController.php`
- `app/Http/Requests/StoreMeasurementRequest.php`
- `app/Http/Requests/UpdateMeasurementRequest.php`
- `app/Http/Requests/StoreProgressPhotoRequest.php`
- `app/Models/Measurement.php`
- `app/Models/ProgressPhoto.php`
- `app/Models/User.php`

Method return types are declared on controllers (`JsonResponse`) and form request methods (`bool`, `array`). However, model relationship methods (`user()`, `measurements()`, `progressPhotos()`) have no return types, and `strict_types=1` is absent from every file.

**What is required to pass:**
Add `declare(strict_types=1);` as the first statement after `<?php` in every file under `app/`. Add return types to all relationship methods.

---

## 2. Error Handling — FAIL

No named application exception classes exist. Ownership violations are handled with `abort(403)` in three controller methods:

- `MeasurementController::update()` — line 44
- `MeasurementController::destroy()` — line 61
- `ProgressPhotoController::destroy()` — line 48
- `CoachProgressController::clientMeasurements()` — line 16
- `CoachProgressController::clientPhotos()` — line 24

No raw `new \Exception(...)` is thrown anywhere, which satisfies the minimum bar. Validation failures correctly produce `ValidationException` via form requests. However, the rubric requires distinct business failure modes to be expressed as named exception classes. A generic `abort(403)` does not distinguish between "user does not own this measurement" and "user does not own this photo" — both map to the same undifferentiated response.

**What is required to pass:**
Create named exception classes (e.g. `App\Exceptions\MeasurementOwnershipException`, `App\Exceptions\PhotoOwnershipException`, `App\Exceptions\CoachAccessRequiredException`) and throw them instead of calling `abort()`. Register a handler in `app/Exceptions/Handler.php` to convert each to the correct HTTP response.

---

## 3. Observability — FAIL

No `Log::info()`, `Log::warning()`, or any other structured log call exists anywhere in the application code. State-changing operations — measurement creation, measurement update, measurement deletion, photo upload, photo deletion — emit nothing to the log.

**What is required to pass:**
Add at minimum one `Log::info()` call per state-changing controller method, including the entity ID and authenticated user ID. Example for `MeasurementController::store()`:

```php
Log::info('measurement.created', [
    'measurement_id' => $measurement->id,
    'user_id'        => $request->user()->id,
]);
```

---

## 4. Configuration — FAIL

Two magic numbers are hardcoded in application logic:

- `max:5120` in `StoreProgressPhotoRequest::rules()` line 21 — the 5 MB upload limit is a hardcoded kilobyte value with no label
- The accepted MIME types (`image/jpeg,image/png,image/webp`) are duplicated between `StoreProgressPhotoRequest` and `ProgressPhotoController::MIME_EXTENSIONS` with no shared config source

A developer who needs to change the upload limit or add a new accepted format must edit production code rather than a config value.

**What is required to pass:**
Create `config/progress_photos.php` with keys for the max upload size and accepted MIME types. Reference them via `config('progress_photos.max_size_kb')` and `config('progress_photos.accepted_mimetypes')` in both the form request and the controller.

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

## 7. Security — FAIL

**Middleware coverage — PASS:** All nine API routes are registered inside `Route::middleware('auth:sanctum')` in `routes/api.php`. Unauthenticated requests to every endpoint return 401, confirmed by tests.

**Authorization policies — FAIL:** Ownership checks are implemented as inline conditionals in controllers rather than using Laravel Policies or Gates:

```php
// MeasurementController::update(), line 44
if ($measurement->user_id !== $request->user()->id) {
    abort(403);
}
```

This pattern is repeated in five controller methods across two controllers. The rubric requires authorization policies for resource mutations.

**500 exposure risk — PARTIAL:** In the normal path, duplicate-date validation is caught by the form request and returns 422. However, a race condition between two concurrent requests from the same user could bypass the form request check and hit the database unique constraint directly, producing an unhandled `Illuminate\Database\UniqueConstraintViolationException` and a 500 response. This is unlikely in practice but is not guarded against.

**What is required to pass:**
Create `App\Policies\MeasurementPolicy` and `App\Policies\ProgressPhotoPolicy`, register them in `AuthServiceProvider`, and call `$this->authorize()` in each controller method. Wrap `Measurement::create()` and `Measurement::update()` in a try/catch for `UniqueConstraintViolationException` to return 422 instead of 500 on race conditions.

---

## 8. API Consistency — FAIL

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

**API Resources — FAIL:** Controllers return raw Eloquent model instances and collections directly via `response()->json($measurement)`. No `JsonResource` classes exist. This means the response shape is coupled to the model's attribute and cast definitions, and any model change silently changes the API contract. It also means sensitive fields could be accidentally included if `$hidden` is misconfigured.

**What is required to pass:**
Create `App\Http\Resources\MeasurementResource`, `App\Http\Resources\ProgressPhotoResource`, and return them from controllers:

```php
return MeasurementResource::make($measurement)->response()->setStatusCode(201);
```

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

## 10. No Hardcoded Environment Values — FAIL

`.env.example` line 4:

```
APP_DEBUG=true
```

The rubric requires `APP_DEBUG=false` in `.env.example`. A developer who copies `.env.example` verbatim and deploys will run the application in debug mode, which exposes stack traces, request data, and environment variables in HTTP error responses.

Additionally, no keys in `.env.example` are annotated with `# REQUIRED` to indicate which values must be set before the application can start.

**What is required to pass:**
Change line 4 of `.env.example` to `APP_DEBUG=false`. Annotate `APP_KEY` and `DB_CONNECTION` with `# REQUIRED`.

---

## Summary

| # | Criterion | Result |
|---|-----------|--------|
| 1 | Type Safety | FAIL |
| 2 | Error Handling | FAIL |
| 3 | Observability | FAIL |
| 4 | Configuration | FAIL |
| 5 | Validation | PASS |
| 6 | Data Integrity | PASS |
| 7 | Security | FAIL |
| 8 | API Consistency | FAIL |
| 9 | Tests Pass | PASS |
| 10 | No Hardcoded Environment Values | FAIL |

**3 of 10 criteria pass.**
