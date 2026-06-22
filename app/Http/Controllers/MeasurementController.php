<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeasurementRequest;
use App\Http\Requests\UpdateMeasurementRequest;
use App\Http\Resources\MeasurementResource;
use App\Models\Measurement;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MeasurementController extends Controller
{
    public function store(StoreMeasurementRequest $request): JsonResponse
    {
        try {
            $measurement = Measurement::create([
                'user_id'             => $request->user()->id,
                'measured_at'         => $request->measured_at,
                'weight'              => $request->weight,
                'body_fat_percentage' => $request->body_fat_percentage,
                'notes'               => $request->notes,
                'unit_system'         => $request->unit_system,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'measured_at' => ['A measurement for this date already exists.'],
            ]);
        }

        Log::info('measurement.created', [
            'measurement_id' => $measurement->id,
            'user_id'        => $request->user()->id,
        ]);

        return MeasurementResource::make($measurement)->response()->setStatusCode(201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Measurement::where('user_id', $request->user()->id);

        if ($request->filled('start_date')) {
            $query->whereDate('measured_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('measured_at', '<=', $request->end_date);
        }

        return MeasurementResource::collection($query->get())->response();
    }

    public function update(UpdateMeasurementRequest $request, Measurement $measurement): JsonResponse
    {
        $this->authorize('update', $measurement);

        try {
            $measurement->update($request->only([
                'measured_at',
                'weight',
                'body_fat_percentage',
                'notes',
                'unit_system',
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'measured_at' => ['A measurement for this date already exists.'],
            ]);
        }

        Log::info('measurement.updated', [
            'measurement_id' => $measurement->id,
            'user_id'        => $request->user()->id,
        ]);

        return MeasurementResource::make($measurement)->response();
    }

    public function destroy(Request $request, Measurement $measurement): JsonResponse
    {
        $this->authorize('delete', $measurement);

        $measurement->delete();

        Log::info('measurement.deleted', [
            'measurement_id' => $measurement->id,
            'user_id'        => $request->user()->id,
        ]);

        return response()->json(null, 204);
    }
}
