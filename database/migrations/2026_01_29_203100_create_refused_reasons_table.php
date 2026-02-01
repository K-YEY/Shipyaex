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
        Schema::create('refused_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Reason name (e.g., "Customer Not Answering", "Wrong Address")
            $table->string('slug')->unique(); // Unique identifier
            $table->string('color')->default('warning'); // Badge color
            $table->string('icon')->nullable(); // Optional icon
            $table->boolean('requires_note')->default(false); // Whether additional note is required
            $table->boolean('is_active')->default(true); // Active/Inactive
            $table->integer('sort_order')->default(0); // For ordering
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refused_reasons');
    }
};
