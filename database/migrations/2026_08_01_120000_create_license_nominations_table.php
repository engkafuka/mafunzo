<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('registration_number');
            $table->string('final_position');
            $table->string('licensing_application_id');
            $table->string('organization_name')->nullable();
            $table->string('callback_url', 500)->nullable();
            $table->string('status')->default('pending'); // pending, accepted, rejected, expired, cancelled
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('callback_sent_at')->nullable();
            $table->text('callback_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['licensing_application_id', 'status']);
            $table->index('registration_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_nominations');
    }
};
