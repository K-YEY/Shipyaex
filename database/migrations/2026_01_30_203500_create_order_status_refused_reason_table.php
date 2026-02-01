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
        Schema::create('order_status_refused_reason', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_status_id')->constrained('order_statuses')->onDelete('cascade');
            $table->foreignId('refused_reason_id')->constrained('refused_reasons')->onDelete('cascade');
            $table->timestamps();

            // Ensure unique combinations
            $table->unique(['order_status_id', 'refused_reason_id'], 'status_reason_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_refused_reason');
    }
};
