<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        session(['admin_user' => true]);
    }

    public function test_import_csv_form_shows_correctly(): void
    {
        $response = $this->get(route('admin.users.import.form'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.users.import-csv');
    }

    public function test_import_csv_creates_users(): void
    {
        $csvContent = "ID,Nombre,Email,Contraseña\n1,Juan Pérez,juan@example.com,password123\n2,María García,,";
        
        $path = tempnam(sys_get_temp_dir(), 'test_csv_');
        file_put_contents($path, $csvContent);
        
        $file = new \Illuminate\Http\UploadedFile($path, 'users.csv', 'text/csv', null, true);
        
        $response = $this->post(route('admin.users.import.csv'), ['file' => $file]);
        
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status');
        
        $this->assertDatabaseHas('users', ['name' => 'Juan Pérez']);
        $this->assertDatabaseHas('users', ['name' => 'María García']);
    }

    public function test_import_csv_logs_audit_entry(): void
    {
        $csvContent = "ID,Nombre\n1,Test User";
        
        $path = tempnam(sys_get_temp_dir(), 'test_csv_');
        file_put_contents($path, $csvContent);
        
        $file = new \Illuminate\Http\UploadedFile($path, 'users.csv', 'text/csv', null, true);
        
        $this->post(route('admin.users.import.csv'), ['file' => $file]);
        
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'import',
            'entity' => 'User',
        ]);
    }

    public function test_import_csv_requires_file(): void
    {
        $response = $this->post(route('admin.users.import.csv'), []);
        $response->assertSessionHasErrors('file');
    }

    public function test_import_csv_accepts_only_csv_files(): void
    {
        $file = new \Illuminate\Http\UploadedFile(
            $this->makeFile('test.txt', 'not a csv'),
            'test.txt',
            'text/plain',
            null,
            true
        );
        
        $response = $this->post(route('admin.users.import.csv'), ['file' => $file]);
        $response->assertSessionHasErrors('file');
    }

    private function makeFile(string $name, string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($path, $content);
        return $path;
    }
}
