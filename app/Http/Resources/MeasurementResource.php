<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeasurementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'user_id'             => $this->user_id,
            'measured_at'         => $this->measured_at->toDateString(),
            'weight'              => $this->weight,
            'body_fat_percentage' => $this->body_fat_percentage,
            'notes'               => $this->notes,
            'unit_system'         => $this->unit_system,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
