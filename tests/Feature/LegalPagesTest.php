<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_terms_page_is_accessible(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Términos y condiciones')
            ->assertSee('normas de uso');
    }

    public function test_privacy_page_is_accessible(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Política de privacidad')
            ->assertSee('datos necesarios');
    }
}
