<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressPhoto extends Model
{
    /** @use HasFactory<\Database\Factories\ProgressPhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'taken_at',
        'storage_path',
        'caption',
    ];

    protected function casts(): array
    {
        return [
            'taken_at' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
