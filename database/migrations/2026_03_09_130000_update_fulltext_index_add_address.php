<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ⚡ Update FULLTEXT index to include `address` column for super-fast global search.
     * The previous ft_order_search covered: code, name, phone, phone_2, external_code
     * Now also includes: address
     */
    public function up(): void
    {
        try {
            // Drop old FULLTEXT index if exists
            $existing = $this->getExistingIndexes();
            if (in_array('ft_order_search', $existing)) {
                DB::statement('ALTER TABLE `order` DROP INDEX `ft_order_search`');
            }

            // Re-create with address included
            DB::statement('ALTER TABLE `order` ADD FULLTEXT INDEX `ft_order_search` (`code`, `name`, `phone`, `phone_2`, `external_code`, `address`)');
        } catch (\Throwable $e) {
            // Silently skip if FULLTEXT not supported (e.g. SQLite)
        }
    }

    public function down(): void
    {
        try {
            $existing = $this->getExistingIndexes();
            if (in_array('ft_order_search', $existing)) {
                DB::statement('ALTER TABLE `order` DROP INDEX `ft_order_search`');
            }

            // Restore original FULLTEXT index without address
            DB::statement('ALTER TABLE `order` ADD FULLTEXT INDEX `ft_order_search` (`code`, `name`, `phone`, `phone_2`, `external_code`)');
        } catch (\Throwable $e) {}
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
