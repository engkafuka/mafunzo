<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('timetable_path')->nullable()->after('published_at');
            $table->string('timetable_original_filename')->nullable()->after('timetable_path');
            $table->timestamp('timetable_uploaded_at')->nullable()->after('timetable_original_filename');
            $table->timestamp('timetable_published_at')->nullable()->after('timetable_uploaded_at');
            $table->foreignId('timetable_published_by')->nullable()->after('timetable_published_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('timetable_published_by');
            $table->dropColumn([
                'timetable_path',
                'timetable_original_filename',
                'timetable_uploaded_at',
                'timetable_published_at',
            ]);
        });
    }
};
