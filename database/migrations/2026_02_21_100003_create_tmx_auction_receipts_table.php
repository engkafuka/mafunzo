<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * One row per receipt assigned to a lot (a lot can have multiple receipts).
     */
    public function up(): void
    {
        Schema::create('tmx_auction_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('tmx_auction_lots')->cascadeOnDelete();
            $table->unsignedBigInteger('receipt_no')->comment('WRRB warehouse receipt number');
            $table->unsignedBigInteger('wrrb_receipt_id')->nullable();
            $table->string('receipt_status', 32)->nullable();
            $table->unsignedInteger('bags')->nullable();
            $table->decimal('weight_kg', 12, 2)->nullable();
            $table->unsignedBigInteger('wrrb_warehouse_id')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->timestamps();

            $table->index(['lot_id', 'receipt_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tmx_auction_receipts');
    }
};
