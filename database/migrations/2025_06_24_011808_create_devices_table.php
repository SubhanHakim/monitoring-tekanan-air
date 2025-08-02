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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->string('diameter')->nullable();
            $table->string('merek')->nullable();
            $table->string('device_type');
            $table->string('location')->nullable();
            $table->string('jenis_distribusi')->nullable();
            $table->string('image_perangkat')->nullable();
            $table->enum('status', [
                'active',
                'inactive',
                'maintenance',
                'error',
                'offline',
                'baik',
                'rusak'
            ])->default('active');
            $table->json('configuration')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->string('api_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
