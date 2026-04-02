<?php

namespace App\Models;

use App\Support\CountryDocumentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial',
        'certification_id',
        'result_key',
        'first_name',
        'last_name',
        'country',
        'country_code',
        'document_type',
        'document_hash',
        'doc_lookup_hash',
        'identity_lookup_hash',
        'doc_partial',
        'score_correct',
        'score_incorrect',
        'total_questions',
        'score_numeric',
        'issued_at',
        'completed_at',
        'next_available_at',
        'expires_at',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'completed_at' => 'datetime',
            'next_available_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'score_numeric' => 'decimal:2',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function canRenew(int $days = 30): bool
    {
        $pivot = $this->last_attempt_at ?? $this->issued_at;
        
        if ($pivot === null) {
            return false;
        }

        return Carbon::now()->greaterThanOrEqualTo($pivot->copy()->addDays($days));
    }

    public static function documentLookupHash(string $document): string
    {
        $normalized = preg_replace('/\s+/', '', mb_strtolower($document));

        return hash_hmac('sha256', (string) $normalized, (string) config('app.key'));
    }

    public static function documentPartial(string $document): string
    {
        return mb_substr($document, -4);
    }

    public static function identityLookupHash(string $countryCode, string $documentType, string $document): string
    {
        $normalizedDocument = CountryDocumentService::normalizeDocument($document);
        $payload = mb_strtolower(trim($countryCode)).'|'.mb_strtolower(trim($documentType)).'|'.$normalizedDocument;

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }
}
