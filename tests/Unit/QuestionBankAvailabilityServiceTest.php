<?php

namespace Tests\Unit;

use App\Models\Certification;
use App\Models\Question;
use App\Models\QuestionTranslation;
use App\Support\QuestionBankAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuestionBankAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuestionBankAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QuestionBankAvailabilityService::class);
    }

    #[Test]
    public function it_checks_if_bank_is_available_for_locale(): void
    {
        $certification = Certification::factory()->create();

        $question = Question::factory()->create([
            'certification_id' => $certification->id,
            'active' => true,
        ]);

        QuestionTranslation::factory()->create([
            'question_id' => $question->id,
            'locale' => 'es',
        ]);

        $this->assertTrue($this->service->isBankAvailable($certification, 'es'));
        $this->assertFalse($this->service->isBankAvailable($certification, 'en'));
    }

    #[Test]
    public function it_gets_available_languages(): void
    {
        $certification = Certification::factory()->create();

        Question::factory()->count(3)->create([
            'certification_id' => $certification->id,
            'active' => true,
        ])->each(function ($question) {
            QuestionTranslation::factory()->create([
                'question_id' => $question->id,
                'locale' => 'es',
            ]);
            QuestionTranslation::factory()->create([
                'question_id' => $question->id,
                'locale' => 'en',
            ]);
        });

        $languages = $this->service->getAvailableLanguages($certification);

        $this->assertContains('es', $languages);
        $this->assertContains('en', $languages);
        $this->assertCount(2, $languages);
    }

    #[Test]
    public function it_determines_if_language_selector_should_show(): void
    {
        $certification = Certification::factory()->create([
            'require_question_bank_for_activation' => true,
        ]);

        // Create questions in multiple languages
        Question::factory()->count(2)->create([
            'certification_id' => $certification->id,
            'active' => true,
        ])->each(function ($question) {
            QuestionTranslation::factory()->create([
                'question_id' => $question->id,
                'locale' => 'es',
            ]);
            QuestionTranslation::factory()->create([
                'question_id' => $question->id,
                'locale' => 'en',
            ]);
        });

        // 2+ languages AND current locale not available = show selector
        $shouldShow = $this->service->shouldShowLanguageSelector($certification, 'fr');
        $this->assertTrue($shouldShow);

        // Current locale available = don't show
        $shouldNotShow = $this->service->shouldShowLanguageSelector($certification, 'es');
        $this->assertFalse($shouldNotShow);
    }

    #[Test]
    public function it_can_activate_when_bank_required_and_available(): void
    {
        $certification = Certification::factory()->create([
            'require_question_bank_for_activation' => true,
        ]);

        $question = Question::factory()->create([
            'certification_id' => $certification->id,
            'active' => true,
        ]);

        QuestionTranslation::factory()->create([
            'question_id' => $question->id,
            'locale' => 'es',
        ]);

        $canActivate = $this->service->canActivate($certification);
        // Should be true if at least one question exists
        $this->assertTrue($canActivate);
    }

    #[Test]
    public function it_blocks_activation_when_bank_required_but_empty(): void
    {
        $certification = Certification::factory()->create([
            'require_question_bank_for_activation' => true,
        ]);

        $canActivate = $this->service->canActivate($certification);
        $this->assertFalse($canActivate);
    }

    #[Test]
    public function it_gets_question_count_by_language(): void
    {
        $certification = Certification::factory()->create();

        Question::factory()->count(3)->create([
            'certification_id' => $certification->id,
            'active' => true,
        ])->each(function ($question) {
            QuestionTranslation::factory()->create([
                'question_id' => $question->id,
                'locale' => 'es',
            ]);
        });

        Question::factory()->count(2)->create([
            'certification_id' => $certification->id,
            'active' => true,
        ])->each(function ($question) {
            QuestionTranslation::factory()->create([
                'question_id' => $question->id,
                'locale' => 'en',
            ]);
        });

        $counts = $this->service->getQuestionCountByLanguage($certification);

        $this->assertEquals(3, $counts['es']);
        $this->assertEquals(2, $counts['en']);
    }
}
