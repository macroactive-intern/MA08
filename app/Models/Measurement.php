<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
            'measured_at'         => 'date',
            'weight'              => 'decimal:2',
            'body_fat_percentage' => 'decimal:1',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
