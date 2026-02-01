<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add performance indexes for 100K+ records
     */
    public function up(): void
    {
        Schema::table('order', function (Blueprint $table) {
            // Primary search/filter indexes
            $table->index('code', 'idx_order_code');
            $table->index('external_code', 'idx_order_external_code');
            $table->index('status', 'idx_order_status');
            $table->index('created_at', 'idx_order_created_at');
            $table->index('shipper_date', 'idx_order_shipper_date');
            
            // Foreign key indexes (critical for joins)
            $table->index('client_id', 'idx_order_client_id');
            $table->index('shipper_id', 'idx_order_shipper_id');
            $table->index('governorate_id', 'idx_order_governorate_id');
            $table->index('city_id', 'idx_order_city_id');
            $table->index('collected_shipper_id', 'idx_order_collected_shipper_id');
            $table->index('collected_client_id', 'idx_order_collected_client_id');
            $table->index('returned_shipper_id', 'idx_order_returned_shipper_id');
            
            // Boolean flag indexes (for filtering)
            $table->index('collected_shipper', 'idx_order_collected_shipper');
            $table->index('collected_client', 'idx_order_collected_client');
            $table->index('return_shipper', 'idx_order_return_shipper');
            $table->index('has_return', 'idx_order_has_return');
            
            // Composite indexes for common query patterns
            $table->index(['status', 'collected_shipper'], 'idx_order_status_collected_shipper');
            $table->index(['status', 'has_return'], 'idx_order_status_has_return');
            $table->index(['shipper_id', 'status'], 'idx_order_shipper_status');
            $table->index(['client_id', 'status'], 'idx_order_client_status');
            $table->index(['collected_shipper', 'status'], 'idx_order_collected_status');
            
            // Date range queries
            $table->index(['created_at', 'status'], 'idx_order_created_status');
            $table->index(['shipper_date', 'shipper_id'], 'idx_order_shipper_date_id');
            
            // Soft deletes index
            $table->index('deleted_at', 'idx_order_deleted_at');
        });

        // Other tables
        Schema::table('collected_shipper', function (Blueprint $table) {
            $table->index('status', 'idx_collected_shipper_status');
            $table->index('created_at', 'idx_collected_shipper_created_at');
        });

        Schema::table('collected_client', function (Blueprint $table) {
            $table->index('status', 'idx_collected_client_status');
            $table->index('created_at', 'idx_collected_client_created_at');
        });

        Schema::table('returned_shippers', function (Blueprint $table) {
            $table->index('status', 'idx_returned_shipper_status');
            $table->index('created_at', 'idx_returned_shipper_created_at');
        });

        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->index('order_id', 'idx_order_status_history_order_id');
            $table->index('created_at', 'idx_order_status_history_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropIndex('idx_order_code');
            $table->dropIndex('idx_order_external_code');
            $table->dropIndex('idx_order_status');
            $table->dropIndex('idx_order_created_at');
            $table->dropIndex('idx_order_shipper_date');
            $table->dropIndex('idx_order_client_id');
            $table->dropIndex('idx_order_shipper_id');
            $table->dropIndex('idx_order_governorate_id');
            $table->dropIndex('idx_order_city_id');
            $table->dropIndex('idx_order_collected_shipper_id');
            $table->dropIndex('idx_order_collected_client_id');
            $table->dropIndex('idx_order_returned_shipper_id');
            $table->dropIndex('idx_order_collected_shipper');
            $table->dropIndex('idx_order_collected_client');
            $table->dropIndex('idx_order_return_shipper');
            $table->dropIndex('idx_order_has_return');
            $table->dropIndex('idx_order_status_collected_shipper');
            $table->dropIndex('idx_order_status_has_return');
            $table->dropIndex('idx_order_shipper_status');
            $table->dropIndex('idx_order_client_status');
            $table->dropIndex('idx_order_collected_status');
            $table->dropIndex('idx_order_created_status');
            $table->dropIndex('idx_order_shipper_date_id');
            $table->dropIndex('idx_order_deleted_at');
        });

        Schema::table('collected_shipper', function (Blueprint $table) {
            $table->dropIndex('idx_collected_shipper_status');
            $table->dropIndex('idx_collected_shipper_created_at');
        });

        Schema::table('collected_client', function (Blueprint $table) {
            $table->dropIndex('idx_collected_client_status');
            $table->dropIndex('idx_collected_client_created_at');
        });

        Schema::table('returned_shippers', function (Blueprint $table) {
            $table->dropIndex('idx_returned_shipper_status');
            $table->dropIndex('idx_returned_shipper_created_at');
        });

        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropIndex('idx_order_status_history_order_id');
            $table->dropIndex('idx_order_status_history_created_at');
        });
    }
};
