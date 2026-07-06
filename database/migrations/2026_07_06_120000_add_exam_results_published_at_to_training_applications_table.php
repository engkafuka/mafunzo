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
            $table->timestamp('exam_results_published_at')->nullable()->after('exam_uploaded_at');
        });

        // Existing saved results were visible to trainees; keep them published.
        DB::table('training_applications')
            ->whereNotNull('exam_uploaded_at')
            ->whereNull('exam_results_published_at')
            ->update(['exam_results_published_at' => DB::raw('exam_uploaded_at')]);
    }

    public function down(): void
    {
        Schema::table('training_applications', function (Blueprint $table) {
            $table->dropColumn('exam_results_published_at');
        });
    }
};
