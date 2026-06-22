<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class CoachAccessRequiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Coach access is required for this endpoint.');
    }
}
