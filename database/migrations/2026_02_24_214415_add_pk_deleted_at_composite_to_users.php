<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ⚡ Auth User Query Optimization
     * 
     * Laravel fetches the authenticated user on every request using:
     * select * from users where id = ? and deleted_at is null limit 1
     * 
     * Even with id as PK, adding a composite index (id, deleted_at)
     * allows MySQL to satisfy the entire query (including the null check)
     * strictly from the index tree (Covering Index), skipping the table row lookup 
     * during the filtering phase.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['id', 'deleted_at'], 'idx_auth_user_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_auth_user_lookup');
        });
    }
};
