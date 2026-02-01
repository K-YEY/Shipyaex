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
        Schema::table('collected_shipper', function (Blueprint $table) {
            if (!Schema::hasColumn('collected_shipper', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });

        Schema::table('collected_client', function (Blueprint $table) {
            if (!Schema::hasColumn('collected_client', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collected_shipper', function (Blueprint $table) {
            if (Schema::hasColumn('collected_shipper', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        Schema::table('collected_client', function (Blueprint $table) {
            if (Schema::hasColumn('collected_client', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
