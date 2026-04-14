<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use App\Models\QuestionTranslation;
use Database\Seeders\CertificationSeeder;
use Database\Seeders\CertificationSeederTemplate;
use Database\Seeders\HCertificationSeeder;
use Database\Seeders\LocalizedQuestionTranslationsSeeder;
use Database\Seeders\QuestionTranslationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeederRegressionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function social_energy_questions_seeder_is_idempotent_even_with_partial_data(): void
    {
        $this->seed(CertificationSeeder::class);

        $certification = Certification::query()->where('slug', 'hetero')->firstOrFail();

        Question::query()->create([
            'certification_id' => $certification->id,
            'prompt' => '¿Qué haces cuando ves una persona atractiva?',
            'option_1' => 'Siempre',
            'option_2' => 'A veces',
            'option_3' => 'Raramente',
            'option_4' => 'Nunca',
            'correct_option' => 1,
            'active' => true,
        ]);

        $this->seed(HCertificationSeeder::class);

        $questions = Question::query()
            ->where('certification_id', $certification->id)
            ->get();

        $this->assertCount(30, $questions);
        $this->assertCount(30, $questions->pluck('prompt')->unique());

        // Re-run should keep same count without duplicates.
        $this->seed(HCertificationSeeder::class);

        $questionsAfterReseed = Question::query()
            ->where('certification_id', $certification->id)
            ->get();

        $this->assertCount(30, $questionsAfterReseed);
        $this->assertCount(30, $questionsAfterReseed->pluck('prompt')->unique());
    }

    #[Test]
    public function localized_translations_preserve_question_prompt_and_cover_all_locales(): void
    {
        $this->seed([
            CertificationSeeder::class,
            HCertificationSeeder::class,
            QuestionTranslationsSeeder::class,
            LocalizedQuestionTranslationsSeeder::class,
        ]);

        $certification = Certification::query()->where('slug', 'hetero')->firstOrFail();
        $questions = Question::query()
            ->where('certification_id', $certification->id)
            ->orderBy('id')
            ->get();

        $this->assertGreaterThanOrEqual(2, $questions->count());

        $firstQuestion = $questions[0];
        $secondQuestion = $questions[1];

        $firstPt = QuestionTranslation::query()
            ->where('question_id', $firstQuestion->id)
            ->where('language', 'pt')
            ->firstOrFail();

        $secondPt = QuestionTranslation::query()
            ->where('question_id', $secondQuestion->id)
            ->where('language', 'pt')
            ->firstOrFail();

        $this->assertSame($firstQuestion->prompt, $firstPt->prompt);
        $this->assertSame($secondQuestion->prompt, $secondPt->prompt);
        $this->assertNotSame($firstPt->prompt, $secondPt->prompt);

        $this->assertSame('Sempre', $firstPt->option_1);
        $this->assertSame('As vezes', $firstPt->option_2);
        $this->assertSame('Raramente', $firstPt->option_3);
        $this->assertSame('Nunca', $firstPt->option_4);

        foreach ($questions as $question) {
            $localeCount = QuestionTranslation::query()
                ->where('question_id', $question->id)
                ->whereIn('language', ['es', 'pt', 'fr', 'zh', 'hi', 'ar'])
                ->count();

            $this->assertSame(6, $localeCount);
        }
    }

    #[Test]
    public function certification_template_seeder_can_run_multiple_times_without_duplicates(): void
    {
        $this->seed(CertificationSeederTemplate::class);
        $this->seed(CertificationSeederTemplate::class);

        $certifications = Certification::query()
            ->where('slug', 'marketing_101')
            ->get();

        $this->assertCount(1, $certifications);

        $certification = $certifications->first();
        $questions = Question::query()
            ->where('certification_id', $certification->id)
            ->get();

        $this->assertCount(3, $questions);
        $this->assertCount(3, $questions->pluck('prompt')->unique());
    }
}
