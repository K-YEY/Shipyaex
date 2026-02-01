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
        Schema::create('collected_shipper', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipper_id')->constrained('users')->cascadeOnDelete();
            $table->date('collection_date');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->integer('number_of_orders')->default(0);
            $table->decimal('shipper_fees',10,2)->default(0);
            $table->decimal('net_amount',10,2)->default(0);
            $table->enum('status',['pending','completed','cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collected_shipper');
    }
};
