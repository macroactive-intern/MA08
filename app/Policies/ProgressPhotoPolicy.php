<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProgressPhoto;
use App\Models\User;

class ProgressPhotoPolicy
{
    public function delete(User $user, ProgressPhoto $photo): bool
    {
        return $user->id === $photo->user_id;
    }
}
