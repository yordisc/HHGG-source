<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class WebhookSchedulerTest extends TestCase
{
    public function test_scheduler_webhook_rejects_missing_token(): void
    {
        $this->setAdminAccessKey('test-admin-key');

        $this->postJson('/api/webhooks/scheduler')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_scheduler_webhook_rejects_invalid_token(): void
    {
        $this->setAdminAccessKey('test-admin-key');

        $this->postJson('/api/webhooks/scheduler', [], [
            'X-Admin-Access-Key' => 'invalid-key',
        ])->assertForbidden();
    }

    public function test_scheduler_webhook_runs_schedule_with_valid_token(): void
    {
        $this->setAdminAccessKey('test-admin-key');

        Artisan::shouldReceive('call')
            ->once()
            ->with('schedule:run')
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('No scheduled commands are ready to run.');

        $this->postJson('/api/webhooks/scheduler', [], [
            'X-Admin-Access-Key' => 'test-admin-key',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('exit_code', 0);
    }

    private function setAdminAccessKey(string $value): void
    {
        putenv('ADMIN_ACCESS_KEY=' . $value);
        $_ENV['ADMIN_ACCESS_KEY'] = $value;
        $_SERVER['ADMIN_ACCESS_KEY'] = $value;
    }
}
