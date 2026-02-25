<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ⚡ Add FULLTEXT + regular indexes for search columns.
     * - code, external_code, name, phone, phone_2
     * FULLTEXT allows fast LIKE searches on MySQL InnoDB.
     */
    public function up(): void
    {
        $existing = $this->getExistingIndexes();

        Schema::table('order', function (Blueprint $table) use ($existing) {
            // Regular index on code (exact match + prefix LIKE 'SHP%')
            if (!in_array('idx_order_code', $existing)) {
                $table->index('code', 'idx_order_code');
            }

            // Regular index on external_code
            if (!in_array('idx_order_external_code', $existing)) {
                $table->index('external_code', 'idx_order_external_code');
            }

            // Regular index on name
            if (!in_array('idx_order_name', $existing)) {
                $table->index('name', 'idx_order_name');
            }

            // Regular index on phone
            if (!in_array('idx_order_phone', $existing)) {
                $table->index('phone', 'idx_order_phone');
            }

            // Regular index on phone_2
            if (!in_array('idx_order_phone_2', $existing)) {
                $table->index('phone_2', 'idx_order_phone_2');
            }
        });

        // ⚡ FULLTEXT index for fast LIKE '%search%' on text columns
        // Only works on MySQL/MariaDB InnoDB (Laravel >= 9 with fullText())
        $this->addFulltextIfNotExists($existing);
    }

    private function addFulltextIfNotExists(array $existing): void
    {
        if (in_array('ft_order_search', $existing)) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `order` ADD FULLTEXT INDEX `ft_order_search` (`code`, `name`, `phone`, `phone_2`, `external_code`)');
        } catch (\Throwable $e) {
            // Silently skip if FULLTEXT not supported (e.g. SQLite)
        }
    }

    public function down(): void
    {
        $existing = $this->getExistingIndexes();

        Schema::table('order', function (Blueprint $table) use ($existing) {
            foreach ([
                'idx_order_code',
                'idx_order_external_code',
                'idx_order_name',
                'idx_order_phone',
                'idx_order_phone_2',
            ] as $index) {
                if (in_array($index, $existing)) {
                    $table->dropIndex($index);
                }
            }
        });

        if (in_array('ft_order_search', $existing)) {
            try {
                DB::statement('ALTER TABLE `order` DROP INDEX `ft_order_search`');
            } catch (\Throwable $e) {}
        }
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
