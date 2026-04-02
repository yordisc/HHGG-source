<?php

namespace Database\Seeders;

use App\Models\Certification;
use Illuminate\Database\Seeder;

class CertificationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'slug' => 'hetero',
                'name' => 'Certificado Hetero',
                'description' => 'Perfil humoristico sobre presencia social y lectura romantica.',
                'home_order' => 10,
            ],
            [
                'slug' => 'good_girl',
                'name' => 'Certificado Good Girl',
                'description' => 'Perfil comico sobre obediencia, paciencia y limites.',
                'home_order' => 20,
            ],
        ];

        foreach ($items as $item) {
            Certification::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'active' => true,
                    'questions_required' => 30,
                    'pass_score_percentage' => 66.67,
                    'cooldown_days' => 30,
                    'result_mode' => 'binary_threshold',
                    'pdf_view' => 'pdf.certificate',
                    'home_order' => $item['home_order'],
                ]
            );
        }
    }
}
