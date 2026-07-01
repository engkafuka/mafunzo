<?php

namespace Database\Seeders;
use App\Models\User;
use Hash;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => 'edward.kafuka@wrrb.go.tz',
            ],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Furahini@12'), 
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );
    
    }
}
