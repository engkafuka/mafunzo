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
        Schema::table('training_applications', function (Blueprint $table) {
            $table->string('application_review_status')->default('pending')->after('status'); // pending, approved, rejected
            $table->timestamp('application_reviewed_at')->nullable()->after('application_review_status');
            $table->timestamp('account_verified_at')->nullable()->after('application_reviewed_at');
            $table->timestamp('payment_verified_at')->nullable()->after('account_verified_at');
            $table->decimal('exam_score', 5, 2)->nullable()->after('payment_verified_at');
            $table->boolean('exam_passed')->nullable()->after('exam_score');
            $table->string('exam_result_path')->nullable()->after('exam_passed');
            $table->timestamp('exam_uploaded_at')->nullable()->after('exam_result_path');
            $table->timestamp('certificate_issued_at')->nullable()->after('exam_uploaded_at');
            $table->string('certificate_path')->nullable()->after('certificate_issued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_applications', function (Blueprint $table) {
            $table->dropColumn([
                'application_review_status', 'application_reviewed_at', 'account_verified_at',
                'payment_verified_at', 'exam_score', 'exam_passed', 'exam_result_path', 'exam_uploaded_at',
                'certificate_issued_at', 'certificate_path',
            ]);
        });
    }
};
