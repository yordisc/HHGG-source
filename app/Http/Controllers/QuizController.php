<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartQuizRequest;
use App\Models\Certificate;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function register(string $certType): View
    {
        abort_unless(in_array($certType, ['hetero', 'good_girl'], true), 404);

        return view('quiz.register', [
            'certType' => $certType,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function start(StartQuizRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $certType = $data['cert_type'];

        session([
            "quiz_candidate.{$certType}" => [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'country' => $data['country'],
                'doc_lookup_hash' => Certificate::documentLookupHash($data['document']),
                'doc_partial' => Certificate::documentPartial($data['document']),
                'document_hash' => bcrypt($data['document']),
                'started_at' => now()->toISOString(),
            ],
        ]);

        $this->incrementMetric('quiz.start.requested');
        Log::info('quiz.start.requested', [
            'cert_type' => $certType,
            'country' => $data['country'],
            'locale' => app()->getLocale(),
        ]);

        return redirect()->route('quiz.show', ['certType' => $certType]);
    }

    public function show(string $certType): View|RedirectResponse
    {
        abort_unless(in_array($certType, ['hetero', 'good_girl'], true), 404);

        if (!session()->has("quiz_candidate.{$certType}")) {
            return redirect()->route('quiz.register', ['certType' => $certType]);
        }

        // Verify that there are enough questions before showing the exam
        $questionCount = Question::where('cert_type', $certType)
            ->where('active', true)
            ->count();

        if ($questionCount < 30) {
            Log::warning('quiz.insufficient_questions', [
                'cert_type' => $certType,
                'available' => $questionCount,
                'required' => 30,
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
}
