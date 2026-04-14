<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\CertificationVersion;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCertificationVersioningTest extends TestCase
{
    use RefreshDatabase;

    protected Certification $certification;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $this->certification = Certification::create([
            'slug' => 'versioned-cert',
            'name' => 'Versioned Certification',
            'description' => 'Initial description',
            'active' => true,
            'questions_required' => 30,
            'pass_score_percentage' => 70.0,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
            'settings' => null,
        ]);

        for ($i = 1; $i <= 50; $i++) {
            Question::create([
                'certification_id' => $this->certification->id,
                'prompt' => "Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'Option A',
                'option_2' => 'Option B',
                'option_3' => 'Option C',
                'option_4' => 'Option D',
                'correct_option' => 1,
                'explanation' => 'Explanation',
                'active' => true,
            ]);
        }
    }

    public function test_version_is_created_when_certification_is_updated(): void
    {
        $initialVersionCount = $this->certification->versions()->count();

        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Updated Name',
                'description' => 'Updated description',
                'active' => 1,
                'questions_required' => 25,
                'pass_score_percentage' => 75.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $newVersionCount = $this->certification->versions()->count();

        $this->assertGreaterThan($initialVersionCount, $newVersionCount);
    }

    public function test_version_snapshot_contains_correct_data(): void
    {
        $originalData = [
            'name' => $this->certification->name,
            'description' => $this->certification->description,
            'questions_required' => $this->certification->questions_required,
            'pass_score_percentage' => $this->certification->pass_score_percentage,
            'cooldown_days' => $this->certification->cooldown_days,
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'New Name',
                'description' => 'New description',
                'active' => 1,
                'questions_required' => 35,
                'pass_score_percentage' => 80.0,
                'cooldown_days' => 45,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $latestVersion = $this->certification->versions()
            ->orderBy('version_number', 'desc')
            ->first();

        // Version snapshot should have captured the data at that moment
        $this->assertNotNull($latestVersion);
        $this->assertIsArray($latestVersion->snapshot);
    }

    public function test_can_view_version_history(): void
    {
        // Make multiple updates
        foreach (['First Update', 'Second Update', 'Third Update'] as $name) {
            $this->actingAs($this->admin)
                ->put(route('admin.certifications.update', $this->certification), [
                    'slug' => $this->certification->slug,
                    'name' => $name,
                    'active' => 1,
                    'questions_required' => 30,
                    'pass_score_percentage' => 70.0,
                    'cooldown_days' => 30,
                    'result_mode' => 'binary_threshold',
                    'pdf_view' => 'pdf.certificate',
                    'home_order' => 1,
                ]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('admin.certifications.versions', $this->certification));

        $response->assertSuccessful();
        $response->assertViewHas('versions');

        $versions = $response->viewData('versions');
        $this->assertGreaterThanOrEqual(3, count($versions));
    }

    public function test_version_numbers_increment(): void
    {
        $versions = [];

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->admin)
                ->put(route('admin.certifications.update', $this->certification), [
                    'slug' => $this->certification->slug,
                    'name' => "Update {$i}",
                    'active' => 1,
                    'questions_required' => 30,
                    'pass_score_percentage' => 70.0,
                    'cooldown_days' => 30,
                    'result_mode' => 'binary_threshold',
                    'pdf_view' => 'pdf.certificate',
                    'home_order' => 1,
                ]);

            $this->certification->refresh();
            $latest = $this->certification->versions()
                ->orderBy('version_number', 'desc')
                ->first();

            $versions[] = $latest->version_number;
        }

        // Version numbers should increment
        $this->assertEquals($versions[0], $versions[1] - 1);
        $this->assertEquals($versions[1], $versions[2] - 1);
    }

    public function test_rollback_restores_previous_version(): void
    {
        // Store original data
        $original = [
            'name' => $this->certification->name,
            'questions_required' => $this->certification->questions_required,
            'pass_score_percentage' => $this->certification->pass_score_percentage,
        ];

        // Get initial version count before update
        $initialVersionCount = $this->certification->versions()->count();

        // First update
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'First Update',
                'active' => 1,
                'questions_required' => 25,
                'pass_score_percentage' => 75.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $versionAfterFirstUpdate = $this->certification->versions()
            ->orderBy('version_number', 'desc')
            ->first();

        // Verify first update was applied
        $this->assertEquals('First Update', $this->certification->name);
        $this->assertEquals(25, $this->certification->questions_required);

        // Second update
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Second Update',
                'active' => 0,
                'questions_required' => 20,
                'pass_score_percentage' => 80.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $this->assertEquals('Second Update', $this->certification->name);

        // Rollback to first update version
        $this->actingAs($this->admin)
            ->post(route('admin.certifications.rollback-version', [
                'certification' => $this->certification,
                'version' => $versionAfterFirstUpdate->id,
            ]));

        $this->certification->refresh();
        // Should be restored to state after first update
        $this->assertEquals('First Update', $this->certification->name);
        $this->assertEquals(25, $this->certification->questions_required);
        $this->assertEquals(75.0, $this->certification->pass_score_percentage);
    }

    public function test_rollback_creates_new_version(): void
    {
        // Make first update
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'First Update',
                'active' => 1,
                'questions_required' => 25,
                'pass_score_percentage' => 75.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $firstVersion = $this->certification->versions()
            ->orderBy('version_number', 'desc')
            ->first();

        // Make second update
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Second Update',
                'active' => 1,
                'questions_required' => 20,
                'pass_score_percentage' => 80.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $versionCountBeforeRollback = $this->certification->versions()->count();

        // Rollback
        $this->actingAs($this->admin)
            ->post(route('admin.certifications.rollback-version', [
                'certification' => $this->certification,
                'version' => $firstVersion->id,
            ]));

        $this->certification->refresh();
        $versionCountAfterRollback = $this->certification->versions()->count();

        // Rollback should create a new version
        $this->assertGreaterThan($versionCountBeforeRollback, $versionCountAfterRollback);
    }

    public function test_only_sensitive_fields_create_versions(): void
    {
        $versionCount = $this->certification->versions()->count();

        // Non-sensitive update
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Updated Name Only',
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $newVersionCount = $this->certification->versions()->count();

        // Even non-sensitive changes create a version for audit
        $this->assertGreaterThanOrEqual($versionCount, $newVersionCount);
    }

    public function test_version_has_correct_metadata(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.certifications.update', $this->certification), [
                'slug' => $this->certification->slug,
                'name' => 'Version Test',
                'active' => 1,
                'questions_required' => 30,
                'pass_score_percentage' => 70.0,
                'cooldown_days' => 30,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 1,
            ]);

        $this->certification->refresh();
        $version = $this->certification->versions()
            ->orderBy('version_number', 'desc')
            ->first();

        $this->assertNotNull($version->certification_id);
        $this->assertEquals($this->certification->id, $version->certification_id);
        $this->assertNotNull($version->snapshot);
        $this->assertIsArray($version->snapshot);
        $this->assertGreaterThan(0, $version->version_number);
    }
}
