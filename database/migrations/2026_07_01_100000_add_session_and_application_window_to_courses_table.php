<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedSmallInteger('session_year')->default((int) date('Y'))->after('code');
            $table->date('application_opens_at')->nullable()->after('session_year');
            $table->date('application_deadline_at')->nullable()->after('application_opens_at');
            $table->boolean('is_published')->default(false)->after('is_active');
            $table->timestamp('published_at')->nullable()->after('is_published');
        });

        DB::table('courses')->update([
            'session_year' => (int) date('Y'),
        ]);

        DB::statement('ALTER TABLE courses DROP CONSTRAINT IF EXISTS courses_code_unique');

        Schema::table('courses', function (Blueprint $table) {
            $table->unique(['code', 'session_year']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['code', 'session_year']);
            $table->unique('code');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'session_year',
                'application_opens_at',
                'application_deadline_at',
                'is_published',
                'published_at',
            ]);
        });
    }
};
