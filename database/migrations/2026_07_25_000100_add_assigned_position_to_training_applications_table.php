<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_applications', function (Blueprint $table) {
            $table->string('assigned_position')->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('training_applications', function (Blueprint $table) {
            $table->dropColumn('assigned_position');
        });
    }
};
