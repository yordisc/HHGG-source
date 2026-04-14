<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CertificationSeeder::class,
            HCertificationSeeder::class,
            GCertificactionSeeder::class,
            QuestionTranslationsSeeder::class,
            LocalizedQuestionTranslationsSeeder::class,
        ]);

        if (!app()->environment('production') && (bool) config('app.enable_sandbox_seed_data', false)) {
            $this->call([
                SandboxCertificationSeeder::class,
                QuestionTranslationsSeeder::class,
                LocalizedQuestionTranslationsSeeder::class,
                SandboxUsersSeeder::class,
            ]);
        }
    }
}
