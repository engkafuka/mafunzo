<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warehouse_identity_cards')) {
            return;
        }

        Schema::create('warehouse_identity_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('verification_token', 64)->unique();
            $table->string('registration_number');
            $table->unsignedSmallInteger('session_year')->nullable();
            $table->unsignedSmallInteger('trained_year')->nullable();
            $table->string('full_name');
            $table->string('position')->nullable();
            $table->string('course_name');
            $table->string('company_name')->nullable();
            $table->string('photo_path');
            $table->string('pdf_path')->nullable();
            $table->string('status', 20)->default('draft');
            $table->date('issued_at');
            $table->date('expires_at');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique('training_application_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_identity_cards');
    }
};
