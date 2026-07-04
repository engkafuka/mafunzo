<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('position', 'other')->update(['position' => 'managing_director']);
        DB::table('training_applications')->where('position', 'other')->update(['position' => 'managing_director']);
    }

    public function down(): void
    {
        DB::table('users')->where('position', 'managing_director')->update(['position' => 'other']);
        DB::table('training_applications')->where('position', 'managing_director')->update(['position' => 'other']);
    }
};
