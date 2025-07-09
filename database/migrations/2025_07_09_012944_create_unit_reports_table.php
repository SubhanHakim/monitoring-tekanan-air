<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('unit_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('report_format', ['summary', 'detailed', 'statistical'])->default('summary');
            $table->enum('data_source', ['all', 'device', 'group'])->default('all');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('device_group_id')->nullable()->constrained()->onDelete('set null');
            $table->date('start_date');
            $table->date('end_date');
            $table->json('metrics')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('file_type', ['pdf', 'csv', 'excel'])->default('pdf');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('unit_reports');
    }
};
