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
        Schema::create('attendance_correction_breaks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_correction_request_id');

            // キー名を短縮
            $table->foreign('attendance_correction_request_id', 'acr_breaks_request_fk')
                ->references('id')
                ->on('attendance_correction_requests')
                ->cascadeOnDelete();

            $table->dateTime('requested_break_start')->nullable();
            $table->dateTime('requested_break_end')->nullable();

            $table->unsignedInteger('break_order');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_breaks');
    }
};
