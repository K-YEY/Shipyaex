<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ⚡ Add performance indexes to the order table.
     * Checks if each index already exists before adding (idempotent).
     */
    public function up(): void
    {
        $existingIndexes = $this->getExistingIndexes();

        Schema::table('order', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('idx_order_status', $existingIndexes)) {
                $table->index('status', 'idx_order_status');
            }
            if (!in_array('idx_order_client_id', $existingIndexes)) {
                $table->index('client_id', 'idx_order_client_id');
            }
            if (!in_array('idx_order_shipper_id', $existingIndexes)) {
                $table->index('shipper_id', 'idx_order_shipper_id');
            }
            if (!in_array('idx_order_created_at', $existingIndexes)) {
                $table->index('created_at', 'idx_order_created_at');
            }
            if (!in_array('idx_order_collected_shipper', $existingIndexes)) {
                $table->index('collected_shipper', 'idx_order_collected_shipper');
            }
            if (!in_array('idx_order_collected_client', $existingIndexes)) {
                $table->index('collected_client', 'idx_order_collected_client');
            }
            if (!in_array('idx_order_return_shipper', $existingIndexes)) {
                $table->index('return_shipper', 'idx_order_return_shipper');
            }
            if (!in_array('idx_order_return_client', $existingIndexes)) {
                $table->index('return_client', 'idx_order_return_client');
            }
            if (!in_array('idx_order_has_return', $existingIndexes)) {
                $table->index('has_return', 'idx_order_has_return');
            }
            if (!in_array('idx_order_client_status', $existingIndexes)) {
                $table->index(['client_id', 'status'], 'idx_order_client_status');
            }
            if (!in_array('idx_order_shipper_status', $existingIndexes)) {
                $table->index(['shipper_id', 'status'], 'idx_order_shipper_status');
            }
            if (!in_array('idx_order_status_collected_shipper', $existingIndexes)) {
                $table->index(['status', 'collected_shipper'], 'idx_order_status_collected_shipper');
            }
            if (!in_array('idx_order_deleted_at', $existingIndexes)) {
                $table->index('deleted_at', 'idx_order_deleted_at');
            }
        });
    }

    public function down(): void
    {
        $existingIndexes = $this->getExistingIndexes();

        Schema::table('order', function (Blueprint $table) use ($existingIndexes) {
            foreach ([
                'idx_order_status', 'idx_order_client_id', 'idx_order_shipper_id',
                'idx_order_created_at', 'idx_order_collected_shipper', 'idx_order_collected_client',
                'idx_order_return_shipper', 'idx_order_return_client', 'idx_order_has_return',
                'idx_order_client_status', 'idx_order_shipper_status',
                'idx_order_status_collected_shipper', 'idx_order_deleted_at',
            ] as $index) {
                if (in_array($index, $existingIndexes)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    private function getExistingIndexes(): array
    {
        $dbName = DB::getDatabaseName();
        $rows = DB::select("
            SELECT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'order'
        ", [$dbName]);

        return array_map(fn($r) => $r->INDEX_NAME, $rows);
    }
};
