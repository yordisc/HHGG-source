<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_requires_authentication(): void
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_create_update_export_and_delete_users(): void
    {
        $existingUser = User::factory()->create([
            'name' => 'Primer Usuario',
            'email' => 'first@example.com',
        ]);

        $this->asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Nuevo Usuario',
                'password' => 'secret123',
                'email_verified' => 1,
            ])
            ->assertRedirect();

        $createdUser = User::query()->where('name', 'Nuevo Usuario')->firstOrFail();
        $this->assertNotEmpty($createdUser->email);
        $this->assertStringEndsWith('@users.local', $createdUser->email);
        $this->assertNotNull($createdUser->email_verified_at);

        $this->asAdmin()
            ->put(route('admin.users.update', $createdUser), [
                'name' => 'Usuario Modificado',
                'password' => 'secret456',
                'email_verified' => 0,
            ])
            ->assertRedirect(route('admin.users.edit', $createdUser));

        $createdUser->refresh();

        $this->assertSame('Usuario Modificado', $createdUser->name);
        $this->assertStringEndsWith('@users.local', $createdUser->email);
        $this->assertNull($createdUser->email_verified_at);

        $response = $this->asAdmin()
            ->get(route('admin.users.export.csv'));

        $response->assertOk();
        $response->assertStreamed();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('name,email,email_verified_at', $response->streamedContent());

        $this->asAdmin()
            ->delete(route('admin.users.destroy', $createdUser))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $createdUser->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
        ]);
    }
}
