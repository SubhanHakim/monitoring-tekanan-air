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
        Schema::create('sensor_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->decimal('flowrate', 10, 5)->nullable();
            $table->decimal('totalizer', 10, 5)->nullable();
            $table->decimal('battery', 10, 5)->nullable();
            $table->decimal('pressure1', 10, 5)->nullable();
            $table->decimal('pressure2', 10, 5)->nullable();
            $table->string('error_code')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();

            $table->index('recorded_at');
            $table->index(['device_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_data');
    }
};
