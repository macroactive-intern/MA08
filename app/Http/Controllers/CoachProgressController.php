<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\CoachAccessRequiredException;
use App\Http\Resources\MeasurementResource;
use App\Http\Resources\ProgressPhotoResource;
use App\Models\Measurement;
use App\Models\ProgressPhoto;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoachProgressController extends Controller
{
    public function clientMeasurements(Request $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'coach') {
            throw new CoachAccessRequiredException();
        }

        return MeasurementResource::collection(
            Measurement::where('user_id', $user->id)->get()
        )->response();
    }

    public function clientPhotos(Request $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'coach') {
            throw new CoachAccessRequiredException();
        }

        return ProgressPhotoResource::collection(
            ProgressPhoto::where('user_id', $user->id)->get()
        )->response();
    }
}
