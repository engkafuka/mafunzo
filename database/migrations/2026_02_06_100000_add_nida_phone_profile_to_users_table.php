<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nida', 50)->nullable()->after('last_name');
            $table->string('phone', 50)->nullable()->after('email');
            $table->timestamp('profile_completed_at')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nida', 'phone', 'profile_completed_at']);
        });
    }
};
