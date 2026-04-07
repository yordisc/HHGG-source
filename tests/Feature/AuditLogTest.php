<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Certification;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        session(['admin_user' => true]);
    }

    public function test_audit_log_index_displays(): void
    {
        AuditLog::log('create', 'User', 1, 'Test User');
        
        $response = $this->get(route('admin.audit.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.audit.index');
    }

    public function test_audit_log_appears_after_user_creation(): void
    {
        $response = $this->post(route('admin.users.store'), [
            'name' => 'New User',
            'password' => 'password123',
            'email_verified' => false,
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'entity' => 'User',
            'entity_name' => 'New User',
        ]);
    }

    public function test_audit_log_appears_after_certification_update(): void
    {
        $certification = Certification::factory()->create(['name' => 'Original Name']);
        
        $response = $this->put(route('admin.certifications.update', $certification), [
            'name' => 'Updated Name',
            'slug' => $certification->slug,
            'description' => $certification->description,
            'time_limit_minutes' => $certification->time_limit_minutes,
            'min_score_percentage' => $certification->min_score_percentage,
            'max_score_percentage' => $certification->max_score_percentage,
            'is_active' => $certification->is_active,
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'entity' => 'Certification',
            'entity_id' => $certification->id,
        ]);
    }

    public function test_audit_log_appears_after_user_deletion(): void
    {
        $user = User::factory()->create(['name' => 'To Delete']);
        
        $response = $this->delete(route('admin.users.destroy', $user));
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'delete',
            'entity' => 'User',
            'entity_name' => 'To Delete',
        ]);
    }

    public function test_audit_log_can_filter_by_action(): void
    {
        AuditLog::log('create', 'User', 1, 'Test');
        AuditLog::log('update', 'Certification', 1, 'Test');
        AuditLog::log('delete', 'User', 2, 'Test');
        
        $response = $this->get(route('admin.audit.index', ['action' => 'create']));
        
        $response->assertStatus(200);
        $response->assertSee('create');
    }

    public function test_audit_log_can_filter_by_entity(): void
    {
        AuditLog::log('create', 'User', 1, 'Test');
        AuditLog::log('create', 'Certification', 2, 'Test');
        
        $response = $this->get(route('admin.audit.index', ['entity' => 'User']));
        
        $response->assertStatus(200);
    }

    public function test_audit_log_records_ip_address(): void
    {
        AuditLog::log('create', 'User', 1, 'Test User');
        
        $log = AuditLog::first();
        $this->assertNotNull($log->ip_address);
    }

    public function test_audit_log_records_user_agent(): void
    {
        AuditLog::log('create', 'User', 1, 'Test User');
        
        $log = AuditLog::first();
        $this->assertNotNull($log->user_agent);
    }

    public function test_audit_log_stores_changes_as_json(): void
    {
        AuditLog::log('update', 'User', 1, 'Test', ['email' => 'new@example.com']);
        
        $log = AuditLog::first();
        $this->assertIsArray($log->changes);
        $this->assertEquals('new@example.com', $log->changes['email']);
    }
}
