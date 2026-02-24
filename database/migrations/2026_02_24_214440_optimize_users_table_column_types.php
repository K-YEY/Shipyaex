<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ⚡ Database Performance Optimization
     * 
     * Problem: 'phone' and 'address' are currently 'text' types.
     * In MySQL, 'text' columns are stored outside the table row (off-page),
     * which makes hydration and searches significantly slower than 'varchar'.
     * 
     * Solution:
     * - Convert 'phone' to VARCHAR(50) - much faster for unique lookups.
     * - Convert 'address' to VARCHAR(500).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 50)->change();
            $table->string('address', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('phone')->change();
            $table->text('address')->nullable()->change();
        });
    }
};
