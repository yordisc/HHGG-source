<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCertificateTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_certificate_template(): void
    {
        $this->asAdmin()
            ->post(route('admin.certificates.templates.store'), [
                'slug' => 'modern-template',
                'name' => 'Modern Design',
                'html_template' => '<div>{{nombre}}</div>',
                'css_template' => '.certificate { font-family: sans-serif; }',
                'is_default' => 0,
            ])
            ->assertRedirect(route('admin.certificates.templates.index'));

        $this->assertDatabaseHas('certificate_templates', [
            'slug' => 'modern-template',
            'name' => 'Modern Design',
        ]);
    }

    public function test_admin_can_update_certificate_template(): void
    {
        $template = CertificateTemplate::create([
            'slug' => 'test-template',
            'name' => 'Test Template',
            'html_template' => '<div>Old</div>',
            'css_template' => null,
            'is_default' => false,
        ]);

        $this->asAdmin()
            ->put(route('admin.certificates.templates.update', $template), [
                'slug' => 'test-template',
                'name' => 'Updated Template',
                'html_template' => '<div>New Content</div>',
                'css_template' => '.certificate { color: red; }',
                'is_default' => 0,
            ])
            ->assertRedirect(route('admin.certificates.templates.edit', $template));

        $template->refresh();

        $this->assertSame('Updated Template', $template->name);
        $this->assertSame('<div>New Content</div>', $template->html_template);
        $this->assertSame('.certificate { color: red; }', $template->css_template);
    }

    public function test_admin_can_delete_certificate_template(): void
    {
        $template = CertificateTemplate::create([
            'slug' => 'deletable',
            'name' => 'Deletable',
            'html_template' => '<div>Content</div>',
            'css_template' => null,
            'is_default' => false,
        ]);

        $this->asAdmin()
            ->delete(route('admin.certificates.templates.destroy', $template))
            ->assertRedirect(route('admin.certificates.templates.index'));

        $this->assertDatabaseMissing('certificate_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_admin_can_view_template_preview(): void
    {
        $template = CertificateTemplate::create([
            'slug' => 'preview-test',
            'name' => 'Preview Test',
            'html_template' => '<div>Nombre: {{nombre_completo}} | DNI: {{documento_identidad}} | País: {{pais_origen}} | Certificación: {{nombre_certificacion}} | Firmado por: {{firma_director_nombre}}</div>',
            'css_template' => null,
            'is_default' => false,
        ]);

        $this->asAdmin()
            ->get(route('admin.certificates.templates.preview', $template))
            ->assertOk()
            ->assertSee('Juan Pérez')
            ->assertSee('CC 12345678')
            ->assertSee('Colombia')
            ->assertSee('Competencia Ejemplar')
            ->assertSee('Benjamin Netanyahu');
    }

    public function test_admin_can_view_template_preview_with_spaced_placeholders(): void
    {
        $template = CertificateTemplate::create([
            'slug' => 'preview-test-spaced',
            'name' => 'Preview Test Spaced',
            'html_template' => '<div>Nombre: {{ nombre_completo }} | DNI: {{ documento_identidad }} | País: {{ pais_origen }} | Certificación: {{ nombre_certificacion }} | Firmado por: {{ firma_director_nombre }}</div>',
            'css_template' => null,
            'is_default' => false,
        ]);

        $this->asAdmin()
            ->get(route('admin.certificates.templates.preview', $template))
            ->assertOk()
            ->assertSee('Juan Pérez')
            ->assertSee('CC 12345678')
            ->assertSee('Colombia')
            ->assertSee('Competencia Ejemplar')
            ->assertSee('Benjamin Netanyahu');
    }

    public function test_admin_can_create_custom_certification_template(): void
    {
        $cert = Certification::create([
            'slug' => 'test-cert',
            'name' => 'Test Cert',
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 60,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
        ]);

        $this->asAdmin()
            ->post(route('admin.certificates.templates.certification.save', $cert), [
                'use_custom' => 1,
                'html_template' => '<div>Custom for {{nombre}}</div>',
                'css_template' => '.custom { color: blue; }',
            ])
            ->assertRedirect(route('admin.certifications.edit', $cert));

        $this->assertDatabaseHas('certificate_templates', [
            'certification_id' => $cert->id,
            'slug' => 'test-cert_custom',
        ]);
    }

    public function test_admin_can_remove_custom_certification_template(): void
    {
        $cert = Certification::create([
            'slug' => 'test-cert-2',
            'name' => 'Test Cert 2',
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 60,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
        ]);

        CertificateTemplate::create([
            'certification_id' => $cert->id,
            'slug' => 'test-cert-2_custom',
            'name' => 'Custom',
            'html_template' => '<div>Custom</div>',
            'is_default' => false,
        ]);

        $this->asAdmin()
            ->post(route('admin.certificates.templates.certification.save', $cert), [
                'use_custom' => 0,
            ])
            ->assertRedirect(route('admin.certifications.edit', $cert));

        $this->assertDatabaseMissing('certificate_templates', [
            'certification_id' => $cert->id,
        ]);
    }

    public function test_setting_default_template_unsets_previous_default(): void
    {
        $first = CertificateTemplate::create([
            'slug' => 'first-default',
            'name' => 'First Default',
            'html_template' => '<div>First</div>',
            'is_default' => true,
        ]);

        $this->asAdmin()
            ->post(route('admin.certificates.templates.store'), [
                'slug' => 'second-default',
                'name' => 'Second Default',
                'html_template' => '<div>Second</div>',
                'is_default' => 1,
            ])
            ->assertRedirect(route('admin.certificates.templates.index'));

        $first->refresh();
        $second = CertificateTemplate::query()->where('slug', 'second-default')->first();

        $this->assertFalse($first->is_default);
        $this->assertTrue($second->is_default);
    }

    public function test_admin_can_view_create_template_form(): void
    {
        $this->asAdmin()
            ->get(route('admin.certificates.templates.create'))
            ->assertStatus(200)
            ->assertSee('Nueva plantilla de certificado')
            ->assertSee('Contenido de la plantilla (HTML + CSS)')
            ->assertSee('Media disponible')
            ->assertSee('nombre_completo')
            ->assertSee('template_content');
    }

    public function test_create_template_form_renders_with_html_placeholder_without_error(): void
    {
        // This test specifically validates that the fixed view rendering works correctly.
        // The old syntax caused HTTP 500 due to conflicting {{ }} delimiters.
        // The fix passes the default template from the controller.
        $response = $this->asAdmin()
            ->get(route('admin.certificates.templates.create'));

        $response->assertStatus(200);
        // Verify the form renders correctly with all required elements
        $response->assertSee('Nueva plantilla de certificado');
        $response->assertSee('html_template');
        $response->assertSee('Variables disponibles');
    }
}
