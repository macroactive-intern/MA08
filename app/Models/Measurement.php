<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Measurement extends Model
{
    /** @use HasFactory<\Database\Factories\MeasurementFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'measured_at',
        'weight',
        'body_fat_percentage',
        'notes',
        'unit_system',
    ];

    protected function casts(): array
    {
        return [
            'measured_at'         => 'date:Y-m-d',
            'weight'              => 'decimal:2',
            'body_fat_percentage' => 'decimal:1',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
