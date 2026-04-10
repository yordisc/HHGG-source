<?php

namespace App\Livewire;

use App\Models\Certificate;
use App\Models\Certification;
use App\Models\Question;
use App\Support\AutoResultRuleService;
use App\Support\CertificationDataRetentionService;
use App\Support\CertificationExpirationService;
use App\Support\CertificationResultResolverService;
use App\Support\CertificationScoringService;
use App\Support\QuestionBankAvailabilityService;
use App\Support\SuddenDeathRuleService;
use App\Support\WeightedScoringService;
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
    
    // Phase 3 fields
    public string $currentLocale;
    public bool $showLanguageSelector = false;
    public array $availableLanguages = [];
    public array $answerDetails = [];

    public function mount(string $certType): void
    {
        $this->certType = $certType;
        $this->currentLocale = app()->getLocale();

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

        // 2. Validar banco de preguntas
        $bankService = app(QuestionBankAvailabilityService::class);

        if (!$bankService->isBankAvailable($certification, $this->currentLocale)) {
            $this->availableLanguages = $bankService->getAvailableLanguages($certification);

            if (empty($this->availableLanguages)) {
                Log::warning('quiz.blocked.no_bank', [
                    'cert_type' => $certType,
                    'locale' => $this->currentLocale,
                ]);
                $this->redirectRoute('quiz.register', ['certType' => $certType], true);
                return;
            }

            if ($bankService->shouldShowLanguageSelector($certification, $this->currentLocale)) {
                $this->showLanguageSelector = true;
                return;
            }

            if (count($this->availableLanguages) === 1) {
                $this->currentLocale = $this->availableLanguages[0];
                session()->put('quiz_locale_override.'.$certType, $this->currentLocale);
            }
        }

        $attempt = session($this->attemptKey());

        if (!is_array($attempt)) {
            try {
                $attempt = $this->buildAttempt($certification);
                session([$this->attemptKey() => $attempt]);
            } catch (\Exception $e) {
                Log::error('quiz.attempt.build.failed', [
                    'cert_type' => $certType,
                    'locale' => $this->currentLocale,
                    'error' => $e->getMessage(),
                ]);
                $this->redirectRoute('quiz.register', ['certType' => $certType], true);
                return;
            }
        }

        $this->total = count($attempt['questions']);
        $this->setCurrentQuestion($attempt);
    }

    public function selectLanguage(string $locale): void
    {
        $certification = Certification::find($this->certificationId);
        if (!$certification) {
            return;
        }

        $bankService = app(QuestionBankAvailabilityService::class);
        $availableLanguages = $bankService->getAvailableLanguages($certification);

        if (!in_array($locale, $availableLanguages)) {
            return;
        }

        $this->currentLocale = $locale;
        session()->put('quiz_locale_override.'.$this->certType, $locale);
        $this->showLanguageSelector = false;

        try {
            $attempt = $this->buildAttempt($certification);
            session([$this->attemptKey() => $attempt]);
            $this->total = count($attempt['questions']);
            $this->setCurrentQuestion($attempt);
        } catch (\Exception $e) {
            Log::error('quiz.language.change.failed', [
                'cert_type' => $this->certType,
                'locale' => $locale,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function answer(int $selectedOption): void
    {
        $attempt = session($this->attemptKey());
        if (!is_array($attempt)) {
            return;
        }

        $question = $attempt['questions'][$this->currentIndex] ?? null;
        if (!is_array($question)) {
            return;
        }

        // 4. Validar según tipo de pregunta
        $questionType = $question['type'] ?? 'mcq_4';
        $maxOptions = ($questionType === 'mcq_2') ? 2 : 4;

        if ($selectedOption < 1 || $selectedOption > $maxOptions) {
            return;
        }

        $isCorrect = ($selectedOption === $question['correct_displayed']);

        if ($isCorrect) {
            $this->correctCount++;
        } else {
            $this->incorrectCount++;
        }

        $this->answerDetails[] = [
            'question_id' => $question['id'],
            'correct' => $isCorrect,
            'weight' => $question['weight'] ?? 1.0,
            'sudden_death_mode' => $question['sudden_death_mode'] ?? 'none',
            'type' => $questionType,
        ];

        // 5. Evaluar muerte súbita
        $deathService = app(SuddenDeathRuleService::class);
        $deathResult = $deathService->evaluateForQuestion(
            Question::find($question['id']),
            $isCorrect
        );

        if ($deathResult['triggered']) {
            $this->finishAttemptWithSuddenDeath($deathResult);
            return;
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

    private function buildAttempt(Certification $certification): array
    {
        $locale = $this->currentLocale;
        $fallbackLocale = config('app.fallback_locale', 'en');
        $translationLanguages = array_values(array_unique([$locale, $fallbackLocale]));

        $requiredQuestions = $this->questionsRequired;

        $query = Question::query()
            ->where('certification_id', $certification->id)
            ->where('active', true)
            ->with(['translations' => function ($query) use ($translationLanguages) {
                $query->whereIn('language', $translationLanguages);
            }]);

        if ($certification->shuffle_questions) {
            $query->inRandomOrder();
        }

        $questions = $query->limit($requiredQuestions)->get();

        if ($questions->count() < $requiredQuestions) {
            throw new \Exception(
                "Question bank must contain at least {$requiredQuestions} active questions for certificate type '{$this->certType}'. "
                ."Currently has {$questions->count()} questions. Contact support if the problem persists."
            );
        }

        $prepared = [];

        foreach ($questions as $question) {
            $translation = $question->translations->firstWhere('language', $locale)
                ?? $question->translations->firstWhere('language', $fallbackLocale);

            $prompt = $translation?->prompt ?? $question->prompt;
            $option1 = $translation?->option_1 ?? $question->option_1;
            $option2 = $translation?->option_2 ?? $question->option_2;

            $questionType = $question->type ?? 'mcq_4';
            $option3 = ($questionType === 'mcq_2' || $translation === null)
                ? null
                : ($translation?->option_3 ?? $question->option_3);
            $option4 = ($questionType === 'mcq_2' || $translation === null)
                ? null
                : ($translation?->option_4 ?? $question->option_4);

            $options = [
                ['index' => 1, 'text' => $option1],
                ['index' => 2, 'text' => $option2],
            ];

            if ($questionType !== 'mcq_2') {
                $options[] = ['index' => 3, 'text' => $option3];
                $options[] = ['index' => 4, 'text' => $option4];
            }

            if ($certification->shuffle_options) {
                shuffle($options);
            }

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
                'type' => $questionType,
                'weight' => $question->weight ?? 1.0,
                'sudden_death_mode' => $question->sudden_death_mode ?? 'none',
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

    private function finishAttemptWithSuddenDeath(array $deathResult): void
    {
        $candidate = session("quiz_candidate.{$this->certType}");
        if (!is_array($candidate)) {
            $this->redirectRoute('home', [], true);
            return;
        }

        $certification = Certification::find($this->certificationId);
        $completedAt = now();

        $decision = $deathResult['decision'] === 'pass' ? 'passed' : 'failed';
        $failed = $decision === 'failed';
        $scoreNumeric = $failed ? 0.0 : 100.0;

        $resultKey = app(CertificationResultResolverService::class)->resolve(
            $this->certType,
            $this->resultMode,
            $failed,
            $this->resultSettings
        );

        $expirationService = app(CertificationExpirationService::class);
        $certificationExpiresAt = $expirationService->calculateCertificationExpiryDate($certification, $completedAt);
        $downloadExpiresAt = $expirationService->calculateDownloadExpiryDate($certification, $certificationExpiresAt);

        $serial = 'CERT-'.date('Y').'-'.strtoupper(substr($this->certType, 0, 2)).'-'.Str::upper(Str::random(6));
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
            'certification_expires_at' => $certificationExpiresAt,
            'download_expires_at' => $downloadExpiresAt,
            'result_decision_source' => 'sudden_death',
            'result_decision_reason' => $deathResult['reason'],
        ]);

        $integrity = app(\App\Support\CertificateIntegrityService::class);
        $certificate->update([
            'content_hash' => $integrity->contentHash($certificate),
            'verification_token_hash' => $integrity->verificationTokenHash($certificate),
        ]);

        $this->incrementMetric('quiz.completed');
        $this->incrementMetric('quiz.completed.'.$decision);
        $this->incrementMetric('quiz.completed.sudden_death');

        Log::info('quiz.completed.sudden_death', [
            'serial' => $certificate->serial,
            'cert_type' => $this->certType,
            'result_key' => $resultKey,
            'decision' => $decision,
            'reason' => $deathResult['reason'],
        ]);

        session()->forget($this->attemptKey());
        session()->forget("quiz_candidate.{$this->certType}");
        session()->forget('quiz_locale_override.'.$this->certType);

        $this->redirectRoute('result.show', ['serial' => $certificate->serial], true);
    }

    private function finishAttempt(): void
    {
        $candidate = session("quiz_candidate.{$this->certType}");
        if (!is_array($candidate)) {
            $this->redirectRoute('home', [], true);
            return;
        }

        $certification = Certification::find($this->certificationId);
        $completedAt = now();

        $resultDecisionSource = 'scoring';
        $resultDecisionReason = '';
        $failed = false;
        $scoreNumeric = 0.0;

        // 11. Orden de precedencia: regla automática > scoring ponderado
        $autoRuleService = app(AutoResultRuleService::class);
        $autoRuleResult = $autoRuleService->evaluate(
            $certification,
            $candidate['first_name'],
            $candidate['last_name']
        );

        if ($autoRuleResult['decision'] !== 'none') {
            $resultDecisionSource = 'auto_name_rule';
            $resultDecisionReason = $autoRuleResult['reason'];
            $failed = $autoRuleResult['decision'] === 'fail';
            $scoreNumeric = $failed ? 0.0 : 100.0;
        } else {
            $scoringService = app(WeightedScoringService::class);
            $scoreNumeric = $scoringService->calculateWeightedScore($this->answerDetails);
            $resultDecisionSource = 'scoring';
            $resultDecisionReason = "Scoring ponderado: {$scoreNumeric}%";
            $failed = $scoreNumeric < $this->passScorePercentage;
        }

        $resultKey = app(CertificationResultResolverService::class)->resolve(
            $this->certType,
            $this->resultMode,
            $failed,
            $this->resultSettings
        );

        $expirationService = app(CertificationExpirationService::class);
        $certificationExpiresAt = $expirationService->calculateCertificationExpiryDate($certification, $completedAt);
        $downloadExpiresAt = $expirationService->calculateDownloadExpiryDate($certification, $certificationExpiresAt);

        $serial = 'CERT-'.date('Y').'-'.strtoupper(substr($this->certType, 0, 2)).'-'.Str::upper(Str::random(6));
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
            'certification_expires_at' => $certificationExpiresAt,
            'download_expires_at' => $downloadExpiresAt,
            'result_decision_source' => $resultDecisionSource,
            'result_decision_reason' => $resultDecisionReason,
        ]);

        $integrity = app(\App\Support\CertificateIntegrityService::class);
        $certificate->update([
            'content_hash' => $integrity->contentHash($certificate),
            'verification_token_hash' => $integrity->verificationTokenHash($certificate),
        ]);

        $this->incrementMetric('quiz.completed');
        $this->incrementMetric($failed ? 'quiz.completed.failed' : 'quiz.completed.passed');
        $this->incrementMetric('quiz.completed.'.$resultDecisionSource);

        Log::info('quiz.completed', [
            'serial' => $certificate->serial,
            'cert_type' => $this->certType,
            'result_key' => $resultKey,
            'score_numeric' => $scoreNumeric,
            'decision_source' => $resultDecisionSource,
            'decision_reason' => $resultDecisionReason,
            'failed' => $failed,
        ]);

        session()->forget($this->attemptKey());
        session()->forget("quiz_candidate.{$this->certType}");
        session()->forget('quiz_locale_override.'.$this->certType);

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
