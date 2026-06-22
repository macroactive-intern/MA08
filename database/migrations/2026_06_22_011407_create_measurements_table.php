<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('measured_at');
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('body_fat_percentage', 4, 1)->nullable();
            $table->text('notes')->nullable();
            $table->enum('unit_system', ['metric', 'imperial']);
            $table->timestamps();

            $table->unique(['user_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
