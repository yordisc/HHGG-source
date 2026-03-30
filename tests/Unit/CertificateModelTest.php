<?php

namespace Tests\Unit;

use App\Models\Certificate;
use Carbon\Carbon;
use Tests\TestCase;

class CertificateModelTest extends TestCase
{
    public function test_is_expired_returns_true_when_expiration_is_in_past(): void
    {
        $certificate = new Certificate([
            'expires_at' => now()->subDay(),
            'issued_at' => now()->subDays(40),
        ]);

        $this->assertTrue($certificate->isExpired());
    }

    public function test_can_renew_depends_on_last_attempt_or_issued_at(): void
    {
        Carbon::setTestNow(now());

        $notReady = new Certificate([
            'issued_at' => now()->subDays(10),
            'last_attempt_at' => now()->subDays(10),
        ]);

        $ready = new Certificate([
            'issued_at' => now()->subDays(40),
            'last_attempt_at' => now()->subDays(40),
        ]);

        $this->assertFalse($notReady->canRenew(30));
        $this->assertTrue($ready->canRenew(30));

        Carbon::setTestNow();
    }

    public function test_document_hash_and_partial_are_consistent(): void
    {
        $hashA = Certificate::documentLookupHash('AB C12345');
        $hashB = Certificate::documentLookupHash('abc12345');

        $this->assertSame($hashA, $hashB);
        $this->assertSame('2345', Certificate::documentPartial('ABC12345'));
    }
}
