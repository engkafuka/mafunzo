<?php

namespace Database\Seeders;

use App\Models\InterviewCompany;
use App\Models\InterviewQuestionSet;
use App\Models\InterviewSession;
use App\Models\InterviewUserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InterviewDemoSeeder extends Seeder
{
    public const PASSWORD = 'Interview@2026';

    public function run(): void
    {
        $password = Hash::make(self::PASSWORD);

        $accounts = [
            [
                'email' => 'interview.admin@wrrb.test',
                'name' => 'Interview Admin',
                'roles' => [InterviewUserRole::ROLE_ADMIN],
            ],
            [
                'email' => 'interview.panelist1@wrrb.test',
                'name' => 'Panelist One',
                'roles' => [InterviewUserRole::ROLE_PANELIST],
            ],
            [
                'email' => 'interview.panelist2@wrrb.test',
                'name' => 'Panelist Two',
                'roles' => [InterviewUserRole::ROLE_PANELIST],
            ],
            [
                'email' => 'interview.chair@wrrb.test',
                'name' => 'Interview Chair',
                'roles' => [InterviewUserRole::ROLE_CHAIR, InterviewUserRole::ROLE_PANELIST],
            ],
            [
                'email' => 'interview.approver@wrrb.test',
                'name' => 'Interview Approver',
                'roles' => [InterviewUserRole::ROLE_APPROVER],
            ],
            [
                'email' => 'interview.viewer@wrrb.test',
                'name' => 'Interview Viewer',
                'roles' => [InterviewUserRole::ROLE_VIEWER],
            ],
            [
                'email' => 'staff.nointerview@wrrb.test',
                'name' => 'Staff Without Interview Access',
                'roles' => [],
            ],
        ];

        foreach ($accounts as $account) {
            // Interview demo accounts are interview-only except the negative-control staff user
            $systemRole = $account['email'] === 'staff.nointerview@wrrb.test' ? 'staff' : 'interview';

            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $password,
                    'role' => $systemRole,
                    'email_verified_at' => now(),
                    'registration_status' => 'approved',
                ]
            );

            InterviewUserRole::where('user_id', $user->id)->delete();

            foreach ($account['roles'] as $role) {
                InterviewUserRole::create([
                    'user_id' => $user->id,
                    'role' => $role,
                ]);
            }
        }

        $company = InterviewCompany::updateOrCreate(
            ['name' => 'Demo Warehouse Company Ltd'],
            [
                'registration_number' => 'WRRB-DEMO-001',
                'contact_person' => 'Amina Mwinyi',
                'contact_email' => 'ceo@demowarehouse.test',
                'contact_phone' => '0754000000',
                'status' => 'active',
            ]
        );

        // Remove legacy English demo question set if it still exists
        $legacyDemoSet = InterviewQuestionSet::where('title', 'WRRB Operator Interview — Demo Set')->first();
        if ($legacyDemoSet) {
            InterviewSession::where('question_set_id', $legacyDemoSet->id)->each(fn ($s) => $s->delete());
            $legacyDemoSet->questions()->delete();
            $legacyDemoSet->delete();
        }

        $this->command?->info('Interview demo users password: '.self::PASSWORD);
        $this->command?->info('Demo company: '.$company->name);
        $this->command?->info('Use Swahili set: Mahojiano ya Kampuni ya mwendesha ghala — Seti Rasmi');
    }
}
