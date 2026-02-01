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
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('shipper_date')->nullable();
            $table->string('name');
            $table->text('phone');
            $table->text('phone_2')->nullable();
            $table->text('address');

            $table->foreignId('governorate_id')->constrained('governorates')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('city')->cascadeOnDelete();

            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('fees',10,2)->default(0);
            $table->decimal('shipper_fees',10,2)->default(0);
            $table->decimal('cop',10,2)->default(0);
            $table->decimal('cod',10,2)->default(0);

            $table->enum('status', ['out for delivery', 'deliverd', 'hold', 'undelivered'])->nullable();
            $table->text('status_note')->nullable();
            $table->longText('order_note')->nullable();

            $table->foreignId('shipper_id')->constrained('users')->cascadeOnDelete();

            $table->boolean('collected_shipper')->default(false);
            $table->date('collected_shipper_date')->nullable();
            $table->integer('collected_shipper_id')->nullable();

            $table->boolean('return_shipper')->default(false);
            $table->date('return_shipper_date')->nullable();

            $table->boolean('has_return')->default(false);
            $table->date('has_return_date')->nullable();

            $table->boolean('collected_client')->default(false);
            $table->date('collected_client_date')->nullable();
            $table->integer('collected_client_id')->nullable();

            $table->boolean('return_client')->default(false);
            $table->date('return_client_date')->nullable();

            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order');
    }
};
