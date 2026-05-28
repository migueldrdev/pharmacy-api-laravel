<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $role = Role::create(['name' => 'Administrador', 'description' => 'Acceso completo']);
        User::factory()->create([
            'email' => 'admin@farmacia.com',
            'password' => bcrypt('admin123'),
            'role_id' => $role->id,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@farmacia.com',
            'password' => 'admin123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'title',
                'message',
                'data' => ['token'],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'noexiste@farmacia.com',
            'password' => 'incorrecto',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Credenciales inválidas');
    }

    public function test_unauthenticated_user_cannot_access_api(): void
    {
        $response = $this->getJson('/api/v1/product');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_api(): void
    {
        $role = Role::create(['name' => 'Administrador', 'description' => 'Acceso completo']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/product');

        $response->assertOk();
    }
}
