<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAndCertificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_access_dashboard_after_login(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Panel Admin');
    }

    public function test_admin_can_create_update_and_delete_certification(): void
    {
        $this->asAdmin()
            ->post(route('admin.certifications.store'), [
                'slug' => 'wellbeing',
                'name' => 'Wellbeing Certification',
                'description' => 'Certificacion de bienestar',
                'active' => 1,
                'questions_required' => 18,
                'pass_score_percentage' => 72.5,
                'cooldown_days' => 21,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 4,
                'settings' => '{"theme":"calm"}',
            ])
            ->assertRedirect();

        $certification = Certification::query()->where('slug', 'wellbeing')->firstOrFail();

        $this->asAdmin()
            ->put(route('admin.certifications.update', $certification), [
                'slug' => 'wellbeing-updated',
                'name' => 'Wellbeing Certification Updated',
                'description' => 'Certificacion actualizada',
                'active' => 0,
                'questions_required' => 20,
                'pass_score_percentage' => 80,
                'cooldown_days' => 10,
                'result_mode' => 'custom',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 3,
                'settings' => '{"theme":"soft"}',
            ])
            ->assertRedirect(route('admin.certifications.edit', $certification));

        $certification->refresh();

        $this->assertSame('wellbeing-updated', $certification->slug);
        $this->assertSame('Wellbeing Certification Updated', $certification->name);
        $this->assertFalse($certification->active);
        $this->assertSame(20, $certification->questions_required);
        $this->assertSame('custom', $certification->result_mode);

        $this->asAdmin()
            ->patch(route('admin.certifications.toggle', $certification))
            ->assertRedirect(route('admin.certifications.index'));

        $certification->refresh();

        $this->assertTrue($certification->active);

        $this->asAdmin()
            ->delete(route('admin.certifications.destroy', $certification))
            ->assertRedirect(route('admin.certifications.index'));

        $this->assertDatabaseMissing('certifications', [
            'id' => $certification->id,
        ]);
    }
}
