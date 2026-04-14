<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminQuestionsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_import_csv_creates_question_rows(): void
    {
        Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
        ]);

        $csv = implode("\n", [
            'cert_type,prompt,option_1,option_2,option_3,option_4,correct_option,language,active',
            'hetero,Test import question,Always,Sometimes,Rarely,Never,1,en,1',
        ]);

        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $response = $this->asAdmin()
            ->post(route('admin.questions.import.csv'), [
                'csv_file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('questions', [
            'prompt' => 'Test import question',
            'correct_option' => 1,
            'active' => 1,
        ]);

        $question = Question::query()->where('prompt', 'Test import question')->first();
        $this->assertNotNull($question);
        $this->assertNotNull($question->certification_id);
    }
}
