<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicLegalPagesTest extends TestCase
{
    public function test_public_information_page_is_available_without_authentication(): void
    {
        $this->get('/informacion')
            ->assertOk()
            ->assertSee('Gestión de facturas y cobranzas')
            ->assertSee('/politica-privacidad');
    }

    public function test_privacy_policy_is_available_without_authentication(): void
    {
        $this->get('/politica-privacidad')
            ->assertOk()
            ->assertSee('Política de privacidad')
            ->assertSee('gmail.send');
    }
}
