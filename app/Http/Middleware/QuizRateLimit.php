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

        if ($document === '' || $certType === '') {
            return $next($request);
        }

        $now = now();

        $ipHash = $this->hashIdentifier((string) $request->ip());
        $docHash = $this->hashIdentifier($document);

        $blockedByIp = RateLimit::query()
            ->where('identifier_hash', $ipHash)
            ->where('scope', 'quiz_start_ip')
            ->where('attempted_at', '>=', $now->copy()->subDay())
            ->exists();

        $blockedByDoc = RateLimit::query()
            ->where('identifier_hash', $docHash)
            ->where('scope', 'quiz_start_doc')
            ->where('attempted_at', '>=', $now->copy()->subDay())
            ->exists();

        if ($blockedByIp || $blockedByDoc) {
            $this->incrementMetric('quiz.rate_limit.blocked_daily');
            Log::warning('quiz.rate_limit.blocked_daily', [
                'cert_type' => $certType,
                'blocked_by_ip' => $blockedByIp,
                'blocked_by_doc' => $blockedByDoc,
                'ip_hash_prefix' => substr($ipHash, 0, 12),
            ]);

            return back()->withErrors([
                'rate_limit' => __('app.rate_limit_daily'),
            ])->withInput();
        }

        $lookup = Certificate::documentLookupHash($document);

        $latestCertificate = Certificate::query()
            ->where('doc_lookup_hash', $lookup)
            ->where('cert_type', $certType)
            ->latest('issued_at')
            ->first();

        if ($latestCertificate !== null && !$latestCertificate->isExpired() && !$latestCertificate->canRenew(30)) {
            $this->incrementMetric('quiz.rate_limit.blocked_monthly');
            Log::warning('quiz.rate_limit.blocked_monthly', [
                'cert_type' => $certType,
                'serial' => $latestCertificate->serial,
                'expires_at' => $latestCertificate->expires_at?->toISOString(),
            ]);

            return back()->withErrors([
                'rate_limit' => __('app.rate_limit_monthly'),
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
