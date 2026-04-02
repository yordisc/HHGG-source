<?php

namespace App\Livewire;

use App\Models\Certificate;
use App\Models\Certification;
use App\Models\Question;
use App\Support\CertificationResultResolverService;
use App\Support\CertificationScoringService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class QuizRunner extends Component
{
    public string $certType;
    public ?int $certificationId = null;
    public int $questionsRequired = 30;
    public float $passScorePercentage = 66.67;
    public int $cooldownDays = 30;
    public string $resultMode = 'binary_threshold';
    public array $resultSettings = [];
    public int $currentIndex = 0;
    public int $total = 30;
    public int $correctCount = 0;
    public int $incorrectCount = 0;
    public array $currentQuestion = [];

    public function mount(string $certType): void
    {
        $this->certType = $certType;
        $certification = Certification::query()->active()->where('slug', $certType)->first();

        if ($certification === null) {
            $this->redirectRoute('home', [], true);
            return;
        }

        $this->certificationId = $certification->id;
        $this->questionsRequired = (int) ($certification->questions_required ?: 30);
        $this->passScorePercentage = (float) ($certification->pass_score_percentage ?: 66.67);
        $this->cooldownDays = (int) ($certification->cooldown_days ?: 30);
        $this->resultMode = (string) ($certification->result_mode ?: 'binary_threshold');
        $this->resultSettings = is_array($certification->settings) ? $certification->settings : [];

        if (!session()->has("quiz_candidate.{$certType}")) {
            $this->redirectRoute('quiz.register', ['certType' => $certType], true);
            return;
        }

        $attempt = session($this->attemptKey());

        if (!is_array($attempt)) {
            try {
                $attempt = $this->buildAttempt();
                session([$this->attemptKey() => $attempt]);
            } catch (\Exception $e) {
                Log::error('quiz.attempt.build.failed', [
                    'cert_type' => $certType,
                    'error' => $e->getMessage(),
                ]);
                $this->redirectRoute('quiz.register', ['certType' => $certType], true);
                return;
            }
        }

        $this->total = count($attempt['questions']);
        $this->setCurrentQuestion($attempt);
    }

    public function answer(int $selectedOption): void
    {
        if ($selectedOption < 1 || $selectedOption > 4) {
            return;
        }

        $attempt = session($this->attemptKey());
        if (!is_array($attempt)) {
            return;
        }

        $question = $attempt['questions'][$this->currentIndex] ?? null;
        if (!is_array($question)) {
            return;
        }

        if ($selectedOption === $question['correct_displayed']) {
            $this->correctCount++;
        } else {
            $this->incorrectCount++;
        }

        $this->currentIndex++;

        if ($this->currentIndex >= $this->total) {
            $this->finishAttempt();
            return;
        }

        $this->setCurrentQuestion($attempt);
    }

    public function render()
    {
        return view('livewire.quiz-runner');
    }

    private function attemptKey(): string
    {
        return "quiz_attempt.{$this->certType}";
    }

    private function buildAttempt(): array
    {
        $locale = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');
        $translationLanguages = array_values(array_unique([$locale, $fallbackLocale]));

        $requiredQuestions = $this->questionsRequired;

        $questions = Question::query()
            ->where('certification_id', $this->certificationId)
            ->where('active', true)
            ->with(['translations' => function ($query) use ($translationLanguages) {
                $query->whereIn('language', $translationLanguages);
            }])
            ->inRandomOrder()
            ->limit($requiredQuestions)
            ->get();

        if ($questions->count() < $requiredQuestions) {
            throw new \Exception(
                "Question bank must contain at least {$requiredQuestions} active questions for certificate type '{$this->certType}'. ".
                "Currently has {$questions->count()} questions. Contact support if the problem persists."
            );
        }

        $prepared = [];

        foreach ($questions as $question) {
            $translation = $question->translations->firstWhere('language', $locale)
                ?? $question->translations->firstWhere('language', $fallbackLocale);

            $prompt = $translation?->prompt ?? $question->prompt;
            $option1 = $translation?->option_1 ?? $question->option_1;
            $option2 = $translation?->option_2 ?? $question->option_2;
            $option3 = $translation?->option_3 ?? $question->option_3;
            $option4 = $translation?->option_4 ?? $question->option_4;

            $options = [
                ['index' => 1, 'text' => $option1],
                ['index' => 2, 'text' => $option2],
                ['index' => 3, 'text' => $option3],
                ['index' => 4, 'text' => $option4],
            ];

            shuffle($options);

            $displayOptions = [];
            $correctDisplayed = 1;

            foreach ($options as $position => $item) {
                $displayOptions[] = $item['text'];
                if ($item['index'] === (int) $question->correct_option) {
                    $correctDisplayed = $position + 1;
                }
            }

            $prepared[] = [
                'id' => $question->id,
                'prompt' => $prompt,
                'options' => $displayOptions,
                'correct_displayed' => $correctDisplayed,
            ];
        }

        return ['questions' => $prepared];
    }

    private function setCurrentQuestion(array $attempt): void
    {
        $question = $attempt['questions'][$this->currentIndex] ?? null;
        if (!is_array($question)) {
            return;
        }

        $this->currentQuestion = [
            'prompt' => $question['prompt'],
            'options' => $question['options'],
        ];
    }

    private function finishAttempt(): void
    {
        $candidate = session("quiz_candidate.{$this->certType}");
        if (!is_array($candidate)) {
            $this->redirectRoute('home', [], true);
            return;
        }

        $scoring = app(CertificationScoringService::class)->evaluate(
            $this->correctCount,
            $this->total,
            $this->passScorePercentage
        );
        $scoreNumeric = $scoring['score_numeric'];
        $failed = $scoring['failed'];

        $resultKey = app(CertificationResultResolverService::class)->resolve(
            $this->certType,
            $this->resultMode,
            $failed,
            $this->resultSettings
        );

        $serial = 'CERT-'.date('Y').'-'.strtoupper(substr($this->certType, 0, 2)).'-'.Str::upper(Str::random(6));
        $completedAt = now();
        $cooldownDays = $this->cooldownDays > 0
            ? $this->cooldownDays
            : (int) config('quiz.cooldown_days', 30);

        $certificate = Certificate::create([
            'serial' => $serial,
            'certification_id' => $this->certificationId,
            'result_key' => $resultKey,
            'first_name' => $candidate['first_name'],
            'last_name' => $candidate['last_name'],
            'country' => $candidate['country'],
            'country_code' => $candidate['country_code'] ?? null,
            'document_type' => $candidate['document_type'] ?? null,
            'document_hash' => $candidate['document_hash'],
            'doc_lookup_hash' => $candidate['doc_lookup_hash'],
            'identity_lookup_hash' => $candidate['identity_lookup_hash'] ?? null,
            'doc_partial' => $candidate['doc_partial'],
            'score_correct' => $this->correctCount,
            'score_incorrect' => $this->incorrectCount,
            'total_questions' => $this->total,
            'score_numeric' => $scoreNumeric,
            'issued_at' => $completedAt,
            'completed_at' => $completedAt,
            'next_available_at' => $completedAt->copy()->addDays($cooldownDays),
            'expires_at' => now()->addYear(),
            'last_attempt_at' => $completedAt,
        ]);

        $this->incrementMetric('quiz.completed');
        $this->incrementMetric($failed ? 'quiz.completed.failed' : 'quiz.completed.passed');

        Log::info('quiz.completed', [
            'serial' => $certificate->serial,
            'cert_type' => $this->certType,
            'result_key' => $resultKey,
            'score_correct' => $this->correctCount,
            'score_incorrect' => $this->incorrectCount,
            'score_numeric' => $scoreNumeric,
            'total_questions' => $this->total,
            'failed' => $failed,
        ]);

        session()->forget($this->attemptKey());
        session()->forget("quiz_candidate.{$this->certType}");

        $this->redirectRoute('result.show', ['serial' => $certificate->serial], true);
    }

    private function incrementMetric(string $metric): void
    {
        $key = 'metrics.'.now()->format('Ymd').'.'.$metric;

        if (!Cache::has($key)) {
            Cache::put($key, 0, now()->addDays(35));
        }

        Cache::increment($key);
    }
}
