<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class LifeStyleQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        if (Question::where('cert_type', 'life_style')->count() >= 30) {
            return;
        }

        $rows = [];
        for ($i = 1; $i <= 35; $i++) {
            $rows[] = [
                'cert_type' => 'life_style',
                'prompt' => "How do you usually handle your weekly plans? #{$i}",
                'option_1' => 'I plan tasks in advance',
                'option_2' => 'I improvise as things happen',
                'option_3' => 'I set priorities but stay flexible',
                'option_4' => 'I ask others to organize with me',
                'correct_option' => 1,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Question::insert($rows);
    }
}
