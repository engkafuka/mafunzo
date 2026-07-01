<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Lifecycle history entries per lot (status transitions, re-auctions, etc.).
     */
    public function up(): void
    {
        Schema::create('tmx_auction_lot_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('tmx_auction_lots')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('status', 32)->nullable();
            $table->timestamp('event_timestamp')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('current_lot_id')->nullable();
            $table->string('current_lot_uuid', 36)->nullable();
            $table->unsignedBigInteger('previous_lot_id')->nullable();
            $table->string('previous_lot_uuid', 36)->nullable();
            $table->timestamps();

            $table->index(['lot_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tmx_auction_lot_history');
    }
};
