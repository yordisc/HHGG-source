<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class SocialEnergyQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        if (Question::where('cert_type', 'social_energy')->count() >= 30) {
            return;
        }

        $rows = [];
        for ($i = 1; $i <= 35; $i++) {
            $rows[] = [
                'cert_type' => 'social_energy',
                'prompt' => "At a social event, what describes you best? #{$i}",
                'option_1' => 'I greet many people quickly',
                'option_2' => 'I prefer one deep conversation',
                'option_3' => 'I observe first and then join',
                'option_4' => 'I stay near close friends',
                'correct_option' => 1,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Question::insert($rows);
    }
}
