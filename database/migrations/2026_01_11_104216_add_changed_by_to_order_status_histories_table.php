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
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->foreignId('changed_by')->nullable()->after('note')->constrained('users')->nullOnDelete();
            $table->string('old_status')->nullable()->after('status');
            $table->string('action_type')->nullable()->after('old_status'); // created, status_changed, collected_shipper, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropForeign(['changed_by']);
            $table->dropColumn(['changed_by', 'old_status', 'action_type']);
        });
    }
};
