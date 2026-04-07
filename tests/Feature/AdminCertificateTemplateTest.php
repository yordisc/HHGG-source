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
        $this->withSession(['admin_authenticated' => true])
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

        $this->withSession(['admin_authenticated' => true])
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

        $this->withSession(['admin_authenticated' => true])
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
            'html_template' => '<div>Nombre: {{nombre}}, Fecha: {{fecha}}</div>',
            'css_template' => null,
            'is_default' => false,
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->get(route('admin.certificates.templates.preview', $template))
            ->assertOk()
            ->assertSee('Nombre:');
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

        $this->withSession(['admin_authenticated' => true])
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

        $this->withSession(['admin_authenticated' => true])
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

        $this->withSession(['admin_authenticated' => true])
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
}
