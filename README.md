# ProgressPhotosMeasurements

A Laravel JSON API for tracking client body measurements and progress photos. Built for MacroActive as an intern project (MA08).

## What it does

Coaches and clients can:

- Log body measurements (weight, body fat %, unit system, notes) with a date
- Upload progress photos (JPEG, PNG, WebP, max 5 MB) with a date and caption
- Coaches can read any client's measurements and photos
- Clients can only read and manage their own data

## Stack

- PHP 8.2 / Laravel 11
- SQLite (local and test)
- Laravel Sanctum (API token auth)
- Pest v3 (36 tests)

## Setup

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Creating users and tokens

There is no registration endpoint. Issue tokens via Tinker:

```bash
php artisan tinker
```

```php
$user = App\Models\User::create([
    'name'     => 'Alice',
    'email'    => 'alice@test.com',
    'password' => bcrypt('password'),
    'role'     => 'client', // or 'coach'
]);
echo $user->createToken('test')->plainTextToken;
```

Use the printed token as a `Bearer` token on all requests.

## API endpoints

All endpoints require `Authorization: Bearer <token>`.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/measurements` | Create a measurement |
| `GET` | `/api/measurements` | List own measurements |
| `PUT` | `/api/measurements/{id}` | Update own measurement |
| `DELETE` | `/api/measurements/{id}` | Delete own measurement |
| `POST` | `/api/photos` | Upload a progress photo (multipart/form-data) |
| `GET` | `/api/photos` | List own photos |
| `DELETE` | `/api/photos/{id}` | Delete own photo |
| `GET` | `/api/coach/clients/{userId}/measurements` | Coach: read a client's measurements |
| `GET` | `/api/coach/clients/{userId}/photos` | Coach: read a client's photos |

## Running tests

```bash
php artisan test
```

## Docs

- `docs/UNDERSTANDING.md` — requirements breakdown
- `docs/APPROACH.md` — technical approach
- `docs/ESTIMATE.md` — step-by-step build plan
- `docs/audit.md` — production-readiness audit (10/10)
