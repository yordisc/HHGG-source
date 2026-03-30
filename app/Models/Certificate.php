<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial',
        'cert_type',
        'result_key',
        'first_name',
        'last_name',
        'country',
        'document_hash',
        'doc_lookup_hash',
        'doc_partial',
        'score_correct',
        'score_incorrect',
        'total_questions',
        'issued_at',
        'expires_at',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function canRenew(int $days = 30): bool
    {
        $pivot = $this->last_attempt_at ?? $this->issued_at;

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
}
