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
        Schema::table('order_statuses', function (Blueprint $table) {
            // Add clear_refused_reasons column
            $table->boolean('clear_refused_reasons')->default(false)->after('is_active');
        });

        Schema::table('refused_reasons', function (Blueprint $table) {
            // Remove requires_note column
            $table->dropColumn('requires_note');
        });

        Schema::table('order_statuses', function (Blueprint $table) {
            // Remove requires_note column
            $table->dropColumn('requires_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_statuses', function (Blueprint $table) {
            $table->dropColumn('clear_refused_reasons');
            $table->boolean('requires_note')->default(false)->after('icon');
        });

        Schema::table('refused_reasons', function (Blueprint $table) {
            $table->boolean('requires_note')->default(false)->after('icon');
        });
    }
};
