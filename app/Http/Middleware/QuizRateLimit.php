<?php

namespace App\Http\Middleware;

use App\Models\Certificate;
use App\Models\RateLimit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QuizRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $document = (string) $request->input('document', '');
        $certType = (string) $request->input('cert_type', '');
        $countryCode = strtoupper((string) $request->input('country_code', ''));
        $documentType = strtoupper((string) $request->input('document_type', ''));

        if ($document === '' || $certType === '' || $countryCode === '' || $documentType === '') {
            return $next($request);
        }

        $now = now();
        $windowMinutes = (int) config('quiz.start_rate_limit_minutes', 2);

        $ipHash = $this->hashIdentifier((string) $request->ip());
        $identityHash = Certificate::identityLookupHash($countryCode, $documentType, $document);
        $docHash = $this->hashIdentifier($identityHash.'|'.$certType);

        $blockedByIp = RateLimit::query()
            ->where('identifier_hash', $ipHash)
            ->where('scope', 'quiz_start_ip')
            ->where('attempted_at', '>=', $now->copy()->subMinutes($windowMinutes))
            ->exists();

        $blockedByDoc = RateLimit::query()
            ->where('identifier_hash', $docHash)
            ->where('scope', 'quiz_start_doc')
            ->where('attempted_at', '>=', $now->copy()->subMinutes($windowMinutes))
            ->exists();

        if ($blockedByIp || $blockedByDoc) {
            $this->incrementMetric('quiz.rate_limit.blocked_short_window');
            Log::warning('quiz.rate_limit.blocked_short_window', [
                'cert_type' => $certType,
                'blocked_by_ip' => $blockedByIp,
                'blocked_by_doc' => $blockedByDoc,
                'ip_hash_prefix' => substr($ipHash, 0, 12),
                'window_minutes' => $windowMinutes,
            ]);

            return back()->withErrors([
                'rate_limit' => __('app.rate_limit_short_window', ['minutes' => $windowMinutes]),
            ])->withInput();
        }

        RateLimit::create([
            'identifier_hash' => $ipHash,
            'scope' => 'quiz_start_ip',
            'attempted_at' => $now,
        ]);

        RateLimit::create([
            'identifier_hash' => $docHash,
            'scope' => 'quiz_start_doc',
            'attempted_at' => $now,
        ]);

        $this->incrementMetric('quiz.rate_limit.allowed');
        Log::info('quiz.rate_limit.allowed', [
            'cert_type' => $certType,
            'country_code' => $countryCode,
            'document_type' => $documentType,
            'ip_hash_prefix' => substr($ipHash, 0, 12),
            'doc_hash_prefix' => substr($docHash, 0, 12),
        ]);

        return $next($request);
    }

    private function incrementMetric(string $metric): void
    {
        $key = 'metrics.'.now()->format('Ymd').'.'.$metric;

        if (!Cache::has($key)) {
            Cache::put($key, 0, now()->addDays(35));
        }

        Cache::increment($key);
    }

    private function hashIdentifier(string $value): string
    {
        $normalized = mb_strtolower(trim($value));

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }
}
