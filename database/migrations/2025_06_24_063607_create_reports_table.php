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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // daily, weekly, monthly, custom
            $table->enum('report_format', ['summary', 'detailed', 'statistical'])
                ->default('summary');
            $table->json('metrics')
                ->nullable();
            $table->enum('data_source', ['all', 'device', 'group'])
                ->default('all');
            $table->boolean('email_on_completion')
                ->default(false);
            $table->text('email_recipients')  // ✅ TAMBAHKAN INI
                ->nullable();
            $table->boolean('include_anomalies')  // ✅ TAMBAHKAN INI
                ->default(false);
            $table->decimal('anomaly_threshold', 8, 2)
                ->nullable();
            $table->boolean('include_charts')
                ->default(false);
            $table->string('chart_type')
                ->nullable();
            $table->string('last_generated_file')
                ->nullable();
            $table->text('description')->nullable();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_group_id')->nullable()->constrained()->nullOnDelete();
            $table->json('parameters')->nullable(); // Tersimpan parameter laporan
            $table->json('data')->nullable(); // Tersimpan data laporan terakhir
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable(); // daily, weekly, monthly
            $table->json('recipients')->nullable(); // Penerima notifikasi/email
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};