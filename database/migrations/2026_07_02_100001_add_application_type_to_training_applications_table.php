<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_applications', function (Blueprint $table) {
            $table->string('application_type')->default('standard')->after('course_id');
            $table->unsignedSmallInteger('trained_year')->nullable()->after('application_type');
            $table->string('legacy_registration_number')->nullable()->after('trained_year');
        });
    }

    public function down(): void
    {
        Schema::table('training_applications', function (Blueprint $table) {
            $table->dropColumn(['application_type', 'trained_year', 'legacy_registration_number']);
        });
    }
};
