<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasColumn('training_applications', 'legacy_registration_number')
            && ! Schema::hasColumn('training_applications', 'certificate_number')
        ) {
            Schema::table('training_applications', function (Blueprint $table) {
                $table->renameColumn('legacy_registration_number', 'certificate_number');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasColumn('training_applications', 'certificate_number')
            && ! Schema::hasColumn('training_applications', 'legacy_registration_number')
        ) {
            Schema::table('training_applications', function (Blueprint $table) {
                $table->renameColumn('certificate_number', 'legacy_registration_number');
            });
        }
    }
};
