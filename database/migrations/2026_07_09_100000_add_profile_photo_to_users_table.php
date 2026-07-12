<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('company_address');
            }
            if (! Schema::hasColumn('users', 'profile_photo_uploaded_at')) {
                $table->timestamp('profile_photo_uploaded_at')->nullable()->after('profile_photo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('users', 'profile_photo_path') ? 'profile_photo_path' : null,
                Schema::hasColumn('users', 'profile_photo_uploaded_at') ? 'profile_photo_uploaded_at' : null,
            ]);
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
