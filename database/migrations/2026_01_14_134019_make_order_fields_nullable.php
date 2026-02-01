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
        Schema::table('order', function (Blueprint $table) {
            $table->foreignId('governorate_id')->nullable()->change();
            $table->foreignId('city_id')->nullable()->change();
            $table->foreignId('shipper_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->foreignId('governorate_id')->nullable(false)->change();
            $table->foreignId('city_id')->nullable(false)->change();
            $table->foreignId('shipper_id')->nullable(false)->change();
        });
    }
};
