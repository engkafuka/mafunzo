<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_category')->default('new_applicant')->after('role');
            $table->string('registration_status')->default('approved')->after('registration_category');
            $table->timestamp('registration_reviewed_at')->nullable()->after('registration_status');
            $table->foreignId('registration_reviewed_by')->nullable()->after('registration_reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('registration_rejection_reason')->nullable()->after('registration_reviewed_by');
            $table->string('region')->nullable()->after('registration_rejection_reason');
            $table->string('district')->nullable()->after('region');
            $table->string('gender')->nullable()->after('district');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('position')->nullable()->after('date_of_birth');
            $table->string('company_or_private')->nullable()->after('position');
            $table->string('company_name')->nullable()->after('company_or_private');
            $table->string('company_address', 500)->nullable()->after('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registration_reviewed_by');
            $table->dropColumn([
                'registration_category',
                'registration_status',
                'registration_reviewed_at',
                'registration_rejection_reason',
                'region',
                'district',
                'gender',
                'date_of_birth',
                'position',
                'company_or_private',
                'company_name',
                'company_address',
            ]);
        });
    }
};
