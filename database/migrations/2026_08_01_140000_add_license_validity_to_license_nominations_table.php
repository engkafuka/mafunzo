<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('license_nominations', function (Blueprint $table) {
            $table->string('license_number')->nullable()->after('organization_name');
            $table->timestamp('license_issued_at')->nullable()->after('responded_at');
            $table->date('license_valid_from')->nullable()->after('license_issued_at');
            $table->date('license_valid_until')->nullable()->after('license_valid_from');
        });
    }

    public function down(): void
    {
        Schema::table('license_nominations', function (Blueprint $table) {
            $table->dropColumn([
                'license_number',
                'license_issued_at',
                'license_valid_from',
                'license_valid_until',
            ]);
        });
    }
};
