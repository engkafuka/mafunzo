<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_nomination_id')->constrained('license_nominations')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requested_by'); // trainee | company
            $table->string('change_type'); // update | release
            $table->string('current_final_position')->nullable();
            $table->string('proposed_final_position')->nullable();
            $table->string('current_organization_name')->nullable();
            $table->string('proposed_organization_name')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending | approved | rejected | cancelled
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('license_nomination_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_change_requests');
    }
};
