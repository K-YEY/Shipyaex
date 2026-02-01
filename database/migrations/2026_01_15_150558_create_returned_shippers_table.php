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
        Schema::create('returned_shippers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipper_id')->constrained('users')->cascadeOnDelete();
            $table->date('return_date');
            $table->integer('number_of_orders')->default(0);
            $table->text('notes')->nullable();
            $table->enum('status',['pending','completed','cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returned_shippers');
    }
};
