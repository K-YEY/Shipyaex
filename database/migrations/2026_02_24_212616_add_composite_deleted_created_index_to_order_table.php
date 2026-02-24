<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 🚀 CRITICAL PERFORMANCE FIX
     * 
     * Problem diagnosed via EXPLAIN:
     *   - Query type: ALL (Full Table Scan!)
     *   - Using filesort (no index for ORDER BY)
     *   - MySQL ignores idx_order_created_at when combined with WHERE deleted_at IS NULL
     * 
     * Solution:
     *   - Composite index (deleted_at, created_at DESC) — covers both WHERE + ORDER BY in one index
     *   - MySQL can directly use this index to get the first 25 rows without scanning the whole table
     *   - Expected improvement: from ~300K rows scan → direct 25 rows read
     */
    public function up(): void
    {
        // ✅ The CRITICAL index: covers WHERE deleted_at IS NULL ORDER BY created_at DESC
        // This is the MAIN query pattern for the orders table listing
        DB::statement('ALTER TABLE `order` ADD INDEX `idx_order_deleted_created` (`deleted_at`, `created_at` DESC)');

        // ✅ Also add composite for soft-deletes + status (for filtered views)
        DB::statement('ALTER TABLE `order` ADD INDEX `idx_order_deleted_status_created` (`deleted_at`, `status`, `created_at` DESC)');

        // ✅ Composite for client view: WHERE deleted_at IS NULL AND client_id = ? ORDER BY created_at DESC
        DB::statement('ALTER TABLE `order` ADD INDEX `idx_order_deleted_client_created` (`deleted_at`, `client_id`, `created_at` DESC)');

        // ✅ Composite for shipper view: WHERE deleted_at IS NULL AND shipper_id = ? ORDER BY created_at DESC
        DB::statement('ALTER TABLE `order` ADD INDEX `idx_order_deleted_shipper_created` (`deleted_at`, `shipper_id`, `created_at` DESC)');
    }

    public function down(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropIndex('idx_order_deleted_created');
            $table->dropIndex('idx_order_deleted_status_created');
            $table->dropIndex('idx_order_deleted_client_created');
            $table->dropIndex('idx_order_deleted_shipper_created');
        });
    }
};
