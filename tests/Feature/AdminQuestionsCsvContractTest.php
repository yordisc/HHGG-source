<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminQuestionsCsvContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_csv_includes_base_and_translation_rows_with_certification_slug(): void
    {
        $certification = Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
        ]);

        $question = Question::query()->create([
            'certification_id' => $certification->id,
            'prompt' => 'Question EN',
            'option_1' => 'Always',
            'option_2' => 'Sometimes',
            'option_3' => 'Rarely',
            'option_4' => 'Never',
            'correct_option' => 1,
            'active' => true,
        ]);

        QuestionTranslation::query()->create([
            'question_id' => $question->id,
            'language' => 'es',
            'prompt' => 'Pregunta ES',
            'option_1' => 'Siempre',
            'option_2' => 'A veces',
            'option_3' => 'Raramente',
            'option_4' => 'Nunca',
        ]);

        $response = $this->asAdmin()
            ->get(route('admin.questions.export.csv', ['cert_type' => 'hetero']));

        $response->assertOk();
        $response->assertStreamed();

        $content = $response->streamedContent();

        $rows = array_values(array_filter(array_map(static fn(string $line): array => str_getcsv($line), preg_split('/\r\n|\n|\r/', trim($content)) ?: [])));

        $this->assertNotEmpty($rows);
        $this->assertSame(
            ['question_id', 'language', 'cert_type', 'prompt', 'option_1', 'option_2', 'option_3', 'option_4', 'correct_option', 'active'],
            $rows[0]
        );

        $this->assertContains(
            [(string) $question->id, 'en', 'hetero', 'Question EN', 'Always', 'Sometimes', 'Rarely', 'Never', '1', '1'],
            $rows
        );

        $this->assertContains(
            [(string) $question->id, 'es', 'hetero', 'Pregunta ES', 'Siempre', 'A veces', 'Raramente', 'Nunca', '1', '1'],
            $rows
        );
    }

    public function test_import_csv_rejects_when_required_columns_are_missing(): void
    {
        $certification = Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
        ]);

        $csv = implode("\n", [
            'cert_type,prompt,option_1,option_2,option_3,correct_option',
            'hetero,Question EN,Always,Sometimes,Rarely,1',
        ]);

        $file = UploadedFile::fake()->createWithContent('invalid_questions.csv', $csv);

        $response = $this->asAdmin()
            ->post(route('admin.questions.import.csv'), [
                'csv_file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['csv_file']);
        $this->assertDatabaseCount('questions', 0);

        $this->assertNotNull($certification);
    }

    public function test_template_csv_contains_contract_header_and_examples(): void
    {
        $response = $this->asAdmin()
            ->get(route('admin.questions.template.csv'));

        $response->assertOk();
        $response->assertStreamed();

        $content = $response->streamedContent();

        $this->assertStringContainsString('question_id,language,cert_type,prompt,option_1,option_2,option_3,option_4,correct_option,active', $content);
        $this->assertStringContainsString(',en,hetero,', $content);
        $this->assertStringContainsString(',en,good_girl,', $content);
    }
}
