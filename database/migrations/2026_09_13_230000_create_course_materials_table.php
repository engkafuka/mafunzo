<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 32);
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 127)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['course_id', 'category']);
            $table->index(['course_id', 'published_at']);
        });

        if (Schema::hasColumn('courses', 'timetable_path')) {
            $courses = DB::table('courses')
                ->whereNotNull('timetable_path')
                ->get(['id', 'timetable_path', 'timetable_original_filename', 'timetable_uploaded_at', 'timetable_published_at', 'timetable_published_by']);

            foreach ($courses as $course) {
                DB::table('course_materials')->insert([
                    'course_id' => $course->id,
                    'title' => 'Training Timetable',
                    'category' => 'timetable',
                    'description' => null,
                    'file_path' => $course->timetable_path,
                    'original_filename' => $course->timetable_original_filename ?? 'timetable.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => null,
                    'sort_order' => 0,
                    'uploaded_by' => null,
                    'published_at' => $course->timetable_published_at,
                    'published_by' => $course->timetable_published_by,
                    'created_at' => $course->timetable_uploaded_at ?? now(),
                    'updated_at' => $course->timetable_uploaded_at ?? now(),
                ]);
            }

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
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'timetable_path')) {
                $table->string('timetable_path')->nullable()->after('published_at');
                $table->string('timetable_original_filename')->nullable()->after('timetable_path');
                $table->timestamp('timetable_uploaded_at')->nullable()->after('timetable_original_filename');
                $table->timestamp('timetable_published_at')->nullable()->after('timetable_uploaded_at');
                $table->foreignId('timetable_published_by')->nullable()->after('timetable_published_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::dropIfExists('course_materials');
    }
};
