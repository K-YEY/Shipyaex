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
            $table->decimal('fees', 10, 2)->default(0)->after('total_amount')->comment('إجمالي مصاريف الشحن');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collected_shipper', function (Blueprint $table) {
            //
        });
    }
};
