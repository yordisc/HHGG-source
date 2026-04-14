<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SandboxUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) env('SANDBOX_TEST_PASSWORD', 'Sandbox123!');

        $users = [
            [
                'name' => '[TEST] Admin QA',
                'email' => 'qa.admin@example.test',
                'is_admin' => true,
            ],
            [
                'name' => '[TEST] Usuario QA 1',
                'email' => 'qa.user1@example.test',
                'is_admin' => false,
            ],
            [
                'name' => '[TEST] Usuario QA 2',
                'email' => 'qa.user2@example.test',
                'is_admin' => false,
            ],
        ];

        foreach ($users as $item) {
            User::query()->updateOrCreate(
                ['email' => $item['email']],
                [
                    'name' => $item['name'],
                    'email_verified_at' => now(),
                    'password' => $password,
                    'is_admin' => $item['is_admin'],
                ]
            );
        }
    }
}
