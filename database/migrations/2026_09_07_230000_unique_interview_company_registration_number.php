<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize blanks/duplicates before adding the unique index.
        $companies = DB::table('interview_companies')->orderBy('id')->get(['id', 'registration_number']);
        $seen = [];

        foreach ($companies as $company) {
            $normalized = strtoupper(trim((string) ($company->registration_number ?? '')));

            if ($normalized === '') {
                $normalized = 'LEGACY-'.$company->id;
            }

            if (isset($seen[$normalized])) {
                $normalized = $normalized.'-'.$company->id;
            }

            $seen[$normalized] = true;

            if ($normalized !== (string) ($company->registration_number ?? '')) {
                DB::table('interview_companies')
                    ->where('id', $company->id)
                    ->update(['registration_number' => $normalized]);
            }
        }

        Schema::table('interview_companies', function (Blueprint $table) {
            $table->unique('registration_number');
        });
    }

    public function down(): void
    {
        Schema::table('interview_companies', function (Blueprint $table) {
            $table->dropUnique(['registration_number']);
        });
    }
};
