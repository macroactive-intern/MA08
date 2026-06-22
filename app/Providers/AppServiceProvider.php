<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Measurement;
use App\Models\ProgressPhoto;
use App\Policies\MeasurementPolicy;
use App\Policies\ProgressPhotoPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Gate::policy(Measurement::class, MeasurementPolicy::class);
        Gate::policy(ProgressPhoto::class, ProgressPhotoPolicy::class);
    }
}
