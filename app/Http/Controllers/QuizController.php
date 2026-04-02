<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartQuizRequest;
use App\Models\Certificate;
use App\Models\Certification;
use App\Models\Question;
use App\Support\CertificationEligibilityService;
use App\Support\CountryDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function register(string $certType): View
    {
        $certification = $this->resolveActiveCertification($certType);
        abort_unless($certification !== null, 404);

        return view('quiz.register', [
            'certType' => $certType,
            'countryOptions' => CountryDocumentService::countryOptions(app()->getLocale()),
            'documentTypeMap' => CountryDocumentService::documentTypeMap(app()->getLocale()),
            'documentHintMap' => CountryDocumentService::documentHintMap(),
            'specificFormatCountries' => CountryDocumentService::specificFormatCountries(),
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function start(StartQuizRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $certType = $data['cert_type'];
        $countryCode = strtoupper((string) $data['country_code']);
        $documentType = strtoupper((string) $data['document_type']);
        $document = (string) $data['document'];
        $certification = $this->resolveActiveCertification($certType);

        if ($certification === null) {
            return back()->withErrors([
                'cert_type' => __('app.certification_invalid'),
            ])->withInput();
        }

        $eligibility = app(CertificationEligibilityService::class)->evaluate(
            $countryCode,
            $documentType,
            $document,
            $certification
        );

        if (!$eligibility['can_start']) {
            return back()->withErrors([
                'eligibility' => __('app.quiz_blocked_until', [
                    'datetime' => optional($eligibility['next_available_at'])?->format('Y-m-d H:i:s') ?? __('app.not_available_now'),
                ]),
            ])->withInput();
        }

        $countryOptions = CountryDocumentService::countryOptions(app()->getLocale());
        $countryName = $countryOptions[$countryCode] ?? $countryCode;

        session([
            "quiz_candidate.{$certType}" => [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'country' => $countryName,
                'country_code' => $countryCode,
                'document_type' => $documentType,
                'doc_lookup_hash' => Certificate::documentLookupHash($document),
                'identity_lookup_hash' => Certificate::identityLookupHash($countryCode, $documentType, $document),
                'doc_partial' => Certificate::documentPartial($document),
                'document_hash' => bcrypt($document),
                'started_at' => now()->toISOString(),
            ],
        ]);

        $this->incrementMetric('quiz.start.requested');
        Log::info('quiz.start.requested', [
            'cert_type' => $certType,
            'country_code' => $countryCode,
            'locale' => app()->getLocale(),
        ]);

        return redirect()->route('quiz.show', ['certType' => $certType]);
    }

    public function eligibilityCheck(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country_code' => ['required', 'string'],
            'document_type' => ['required', 'string', 'max:30'],
            'document' => ['required', 'string', 'min:5', 'max:30'],
            'cert_type' => ['required', 'string', 'max:60'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'can_start' => false,
                'message' => __('app.complete_required_fields'),
            ], 422);
        }

        $data = $validator->validated();
        $countryCode = strtoupper((string) $data['country_code']);
        $documentType = strtoupper((string) $data['document_type']);
        $document = (string) $data['document'];
        $certType = (string) $data['cert_type'];
        $certification = $this->resolveActiveCertification($certType);

        if ($certification === null) {
            return response()->json([
                'can_start' => false,
                'message' => __('app.certification_invalid'),
            ], 422);
        }

        if (!in_array($countryCode, CountryDocumentService::countryCodes(), true)) {
            return response()->json([
                'can_start' => false,
                'message' => __('app.country_invalid'),
            ], 422);
        }

        $allowedDocTypes = array_keys(CountryDocumentService::documentTypes($countryCode, app()->getLocale()));
        if (!in_array($documentType, $allowedDocTypes, true)) {
            return response()->json([
                'can_start' => false,
                'message' => __('app.document_type_invalid'),
            ], 422);
        }

        $regex = CountryDocumentService::validationRegex($countryCode, $documentType);
        if (!preg_match($regex, mb_strtoupper(trim($document)))) {
            return response()->json([
                'can_start' => false,
                'message' => __('app.document_format_help', [
                    'example' => CountryDocumentService::documentHint($countryCode, $documentType),
                ]),
            ]);
        }

        $eligibility = app(CertificationEligibilityService::class)->evaluate($countryCode, $documentType, $document, $certification);
        if (!$eligibility['can_start']) {
            $nextDate = optional($eligibility['next_available_at'])?->format('Y-m-d H:i:s') ?? __('app.not_available_now');

            return response()->json([
                'can_start' => false,
                'next_available_at' => optional($eligibility['next_available_at'])?->toISOString(),
                'message' => __('app.quiz_blocked_until', ['datetime' => $nextDate]),
            ]);
        }

        return response()->json([
            'can_start' => true,
            'message' => __('app.quiz_ready_to_start'),
        ]);
    }

    public function show(string $certType): View|RedirectResponse
    {
        $certification = $this->resolveActiveCertification($certType);
        abort_unless($certification !== null, 404);

        if (!session()->has("quiz_candidate.{$certType}")) {
            return redirect()->route('quiz.register', ['certType' => $certType]);
        }

        // Verify that there are enough questions before showing the exam
        $requiredQuestions = (int) ($certification->questions_required ?: 30);
        $questionCount = Question::query()
            ->where('certification_id', $certification->id)
            ->where('active', true)
            ->count();

        if ($questionCount < $requiredQuestions) {
            Log::warning('quiz.insufficient_questions', [
                'cert_type' => $certType,
                'available' => $questionCount,
                'required' => $requiredQuestions,
            ]);

            return redirect()->route('quiz.register', ['certType' => $certType])
                ->withErrors([
                    'general' => "The exam for {$certType} could not be started. Please try again later or contact support.",
                ]);
        }

        return view('quiz.take', [
            'certType' => $certType,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    private function incrementMetric(string $metric): void
    {
        $key = 'metrics.'.now()->format('Ymd').'.'.$metric;

        if (!Cache::has($key)) {
            Cache::put($key, 0, now()->addDays(35));
        }

        Cache::increment($key);
    }

    private function resolveActiveCertification(string $certType): ?Certification
    {
        return Certification::query()
            ->active()
            ->where('slug', $certType)
            ->first();
    }
}
