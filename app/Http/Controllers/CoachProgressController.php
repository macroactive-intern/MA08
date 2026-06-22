<?php

namespace App\Http\Controllers;

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
            abort(403);
        }

        return response()->json(Measurement::where('user_id', $user->id)->get());
    }

    public function clientPhotos(Request $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'coach') {
            abort(403);
        }

        return response()->json(ProgressPhoto::where('user_id', $user->id)->get());
    }
}
