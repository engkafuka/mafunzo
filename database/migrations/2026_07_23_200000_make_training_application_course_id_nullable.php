<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_applications', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE training_applications ALTER COLUMN course_id DROP NOT NULL');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE training_applications MODIFY course_id BIGINT UNSIGNED NULL');
        } else {
            // sqlite and others: best-effort via schema builder when supported
            Schema::table('training_applications', function (Blueprint $table) {
                $table->unsignedBigInteger('course_id')->nullable()->change();
            });
        }

        Schema::table('training_applications', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('training_applications', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE training_applications ALTER COLUMN course_id SET NOT NULL');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE training_applications MODIFY course_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('training_applications', function (Blueprint $table) {
                $table->unsignedBigInteger('course_id')->nullable(false)->change();
            });
        }

        Schema::table('training_applications', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });
    }
};
