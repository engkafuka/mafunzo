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
        Schema::create('training_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('region')->nullable();
            $table->string('district')->nullable();
            $table->string('company_or_private')->nullable(); // company / private
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('position')->nullable(); // quality_assurance, manager, etc.
            $table->string('control_number')->nullable()->unique();
            $table->timestamp('payment_completed_at')->nullable();
            $table->string('registration_number')->nullable()->unique();
            $table->string('status')->default('draft'); // draft, pending_payment, payment_completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_applications');
    }
};
