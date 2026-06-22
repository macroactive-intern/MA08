<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Measurement;
use App\Models\User;

class MeasurementPolicy
{
    public function update(User $user, Measurement $measurement): bool
    {
        return $user->id === $measurement->user_id;
    }

    public function delete(User $user, Measurement $measurement): bool
    {
        return $user->id === $measurement->user_id;
    }
}
