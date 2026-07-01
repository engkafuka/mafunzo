<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * One row per auction within a delivery.
     */
    public function up(): void
    {
        Schema::create('tmx_auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('tmx_deliveries')->cascadeOnDelete();
            $table->unsignedBigInteger('tmx_auction_id')->comment('TMX internal auction ID');
            $table->string('auction_uuid', 36)->nullable();
            $table->string('auction_title')->nullable();
            $table->date('auction_date')->nullable();
            $table->string('start_time', 16)->nullable();
            $table->string('auction_status', 32)->nullable();
            $table->string('auction_type', 32)->nullable();
            $table->string('execution_mode', 32)->nullable();
            $table->unsignedBigInteger('tick_size')->nullable();
            $table->unsignedInteger('initial_time_seconds')->nullable();
            $table->unsignedInteger('increment_time_seconds')->nullable();
            $table->boolean('open_auction')->nullable();
            $table->boolean('has_prebid')->nullable();
            $table->string('start_mode', 32)->nullable();
            $table->unsignedInteger('lot_rotation_count')->nullable();
            $table->timestamps();

            $table->index(['delivery_id', 'tmx_auction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tmx_auctions');
    }
};
