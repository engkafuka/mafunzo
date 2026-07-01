<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * One row per lot; commodity, winner, consent, release_order stored as columns for simpler management.
     */
    public function up(): void
    {
        Schema::create('tmx_auction_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained('tmx_auctions')->cascadeOnDelete();
            $table->unsignedBigInteger('tmx_lot_id')->comment('TMX internal lot ID');
            $table->string('lot_uuid', 36)->nullable();
            $table->string('lot_number')->nullable();
            $table->string('lot_status', 32)->nullable();
            // Commodity (denormalized per lot)
            $table->unsignedBigInteger('commodity_tmx_id')->nullable();
            $table->string('commodity_tmx_name')->nullable();
            $table->string('commodity_wrrb_name')->nullable();
            $table->string('commodity_wrrb_grade', 16)->nullable();
            // Winner
            $table->unsignedBigInteger('winner_user_id')->nullable();
            $table->unsignedBigInteger('winner_company_id')->nullable();
            $table->string('winner_name')->nullable();
            // Consent
            $table->unsignedBigInteger('consent_id')->nullable();
            $table->string('consent_status', 32)->nullable();
            $table->boolean('agree_to_sale')->nullable();
            $table->string('consent_type', 32)->nullable();
            $table->timestamp('consent_deadline')->nullable();
            // Release order
            $table->unsignedBigInteger('release_order_id')->nullable();
            $table->string('release_order_number')->nullable();
            $table->string('release_order_status', 32)->nullable();
            $table->date('release_order_date')->nullable();
            $table->timestamps();

            $table->index(['auction_id', 'tmx_lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tmx_auction_lots');
    }
};
