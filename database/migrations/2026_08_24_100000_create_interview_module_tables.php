<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('interview_question_sets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('version')->default('1.0');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('interview_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_set_id')->constrained('interview_question_sets')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('category');
            $table->text('question_text');
            $table->unsignedSmallInteger('max_mark')->default(10);
            $table->decimal('weight', 8, 2)->default(1);
            $table->text('rubric')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('interview_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // admin, panelist, chair, approver, viewer
            $table->timestamps();
            $table->unique(['user_id', 'role']);
        });

        Schema::create('interview_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_code')->unique();
            $table->foreignId('company_id')->constrained('interview_companies')->cascadeOnDelete();
            $table->foreignId('question_set_id')->constrained('interview_question_sets');
            $table->string('interviewee_name');
            $table->string('interviewee_title')->nullable();
            $table->string('interview_type')->default('new_operator');
            $table->date('interview_date')->nullable();
            $table->time('interview_time')->nullable();
            $table->string('venue')->nullable();
            $table->decimal('pass_mark', 5, 2)->default(50);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scoring_opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('interview_session_panelists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('interview_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_chair')->default(false);
            $table->string('submission_status')->default('pending'); // pending, submitted
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'user_id']);
        });

        Schema::create('interview_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('interview_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('interview_questions')->cascadeOnDelete();
            $table->foreignId('panelist_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('draft'); // draft, submitted
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'question_id', 'panelist_user_id']);
        });

        Schema::create('interview_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->unique()->constrained('interview_sessions')->cascadeOnDelete();
            $table->decimal('total_score', 10, 2)->nullable();
            $table->decimal('max_possible_score', 10, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->string('recommendation')->nullable();
            $table->text('chair_notes')->nullable();
            $table->string('decision_status')->default('pending'); // pending, confirmed, approved, rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('calculation_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('interview_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('interview_sessions')->nullOnDelete();
            $table->string('action');
            $table->text('description');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_audit_logs');
        Schema::dropIfExists('interview_results');
        Schema::dropIfExists('interview_scores');
        Schema::dropIfExists('interview_session_panelists');
        Schema::dropIfExists('interview_sessions');
        Schema::dropIfExists('interview_user_roles');
        Schema::dropIfExists('interview_questions');
        Schema::dropIfExists('interview_question_sets');
        Schema::dropIfExists('interview_companies');
    }
};
