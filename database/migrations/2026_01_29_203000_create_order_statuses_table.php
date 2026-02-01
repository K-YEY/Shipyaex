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
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Status name (e.g., "Out for Delivery", "Delivered")
            $table->string('slug')->unique(); // Unique identifier (e.g., "out-for-delivery")
            $table->string('color')->default('gray'); // Badge color
            $table->string('icon')->nullable(); // Optional icon
            $table->boolean('requires_note')->default(false); // Whether status note is required
            $table->boolean('is_active')->default(true); // Active/Inactive status
            $table->integer('sort_order')->default(0); // For ordering
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
