<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeasurementRequest;
use App\Http\Requests\UpdateMeasurementRequest;
use App\Models\Measurement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeasurementController extends Controller
{
    public function store(StoreMeasurementRequest $request): JsonResponse
    {
        $measurement = Measurement::create([
            'user_id'             => $request->user()->id,
            'measured_at'         => $request->measured_at,
            'weight'              => $request->weight,
            'body_fat_percentage' => $request->body_fat_percentage,
            'notes'               => $request->notes,
            'unit_system'         => $request->unit_system,
        ]);

        return response()->json($measurement, 201);
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

        return response()->json($query->get());
    }

    public function update(UpdateMeasurementRequest $request, Measurement $measurement): JsonResponse
    {
        if ($measurement->user_id !== $request->user()->id) {
            abort(403);
        }

        $measurement->update($request->only([
            'measured_at',
            'weight',
            'body_fat_percentage',
            'notes',
            'unit_system',
        ]));

        return response()->json($measurement);
    }

    public function destroy(Request $request, Measurement $measurement): JsonResponse
    {
        if ($measurement->user_id !== $request->user()->id) {
            abort(403);
        }

        $measurement->delete();

        return response()->json(null, 204);
    }
}
