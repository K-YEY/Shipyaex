<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ⚡ Add missing performance indexes:
     * - updated_at: used in follow-up hours filter (delayed orders)
     * - (status, updated_at): composite for the delayed filter query
     * - (client_id, collected_client): for client collection queries
     * - (shipper_id, collected_shipper): for shipper collection queries
     */
    public function up(): void
    {
        $existing = $this->getExistingIndexes();

        Schema::table('order', function (Blueprint $table) use ($existing) {
            // updated_at — used in "delayed follow-up" filter
            if (!in_array('idx_order_updated_at', $existing)) {
                $table->index('updated_at', 'idx_order_updated_at');
            }

            // Composite: status + updated_at — for the follow-up filter
            if (!in_array('idx_order_status_updated_at', $existing)) {
                $table->index(['status', 'updated_at'], 'idx_order_status_updated_at');
            }

            // Composite: client_id + collected_client — for client collection filter
            if (!in_array('idx_order_client_collected', $existing)) {
                $table->index(['client_id', 'collected_client'], 'idx_order_client_collected');
            }

            // Composite: shipper_id + collected_shipper — for shipper collection filter
            if (!in_array('idx_order_shipper_collected', $existing)) {
                $table->index(['shipper_id', 'collected_shipper'], 'idx_order_shipper_collected');
            }
        });
    }

    public function down(): void
    {
        $existing = $this->getExistingIndexes();

        Schema::table('order', function (Blueprint $table) use ($existing) {
            foreach ([
                'idx_order_updated_at',
                'idx_order_status_updated_at',
                'idx_order_client_collected',
                'idx_order_shipper_collected',
            ] as $index) {
                if (in_array($index, $existing)) {
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
