<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Top-level: one row per API response item (request_id, updated_at, source_system).
     */
    public function up(): void
    {
        Schema::create('tmx_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->unique()->comment('TMX X-Request-ID / request_id');
            $table->timestamp('tmx_updated_at')->nullable()->comment('updated_at from TMX payload');
            $table->string('source_system', 32)->default('TMX');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tmx_deliveries');
    }
};
