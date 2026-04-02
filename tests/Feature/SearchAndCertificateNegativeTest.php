<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAndCertificateNegativeTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_back_with_message_when_no_match_exists(): void
    {
        $response = $this->from(route('home'))
            ->post(route('search'), [
                'query' => 'NOT-FOUND-QUERY',
            ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('search_message');
    }

    public function test_certificate_routes_return_404_for_unknown_serial(): void
    {
        $this->get(route('result.show', ['serial' => 'CERT-404-UNKNOWN']))
            ->assertNotFound();

        $this->get(route('cert.show', ['serial' => 'CERT-404-UNKNOWN']))
            ->assertNotFound();

        $this->get(route('cert.pdf', ['serial' => 'CERT-404-UNKNOWN']))
            ->assertNotFound();
    }
}
