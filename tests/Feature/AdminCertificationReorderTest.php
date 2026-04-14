<?php

namespace Tests\Feature;

use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCertificationReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reorder_certifications(): void
    {
        $first = Certification::create([
            'slug' => 'first',
            'name' => 'First',
            'description' => null,
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 66.67,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
            'settings' => [],
        ]);

        $second = Certification::create([
            'slug' => 'second',
            'name' => 'Second',
            'description' => null,
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 66.67,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 2,
            'settings' => [],
        ]);

        $third = Certification::create([
            'slug' => 'third',
            'name' => 'Third',
            'description' => null,
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 66.67,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 3,
            'settings' => [],
        ]);

        $this->asAdmin()
            ->post(route('admin.certifications.reorder'), [
                'certifications' => [$third->id, $first->id, $second->id],
            ])
            ->assertRedirect(route('admin.certifications.index'));

        $this->assertSame(1, $third->refresh()->home_order);
        $this->assertSame(2, $first->refresh()->home_order);
        $this->assertSame(3, $second->refresh()->home_order);
    }
}
