<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ComboEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Administrador', 'description' => 'Acceso completo']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    public function test_categories_combo_returns_label_value_format(): void
    {
        $a = Category::create(['name' => 'Analgésicos', 'active' => 1]);
        Category::create(['name' => 'Vitaminas', 'active' => 1]);
        Category::create(['name' => 'Inactiva', 'active' => 0]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/categories-combo');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.label', 'Analgésicos')
            ->assertJsonPath('data.0.value', $a->id)
            ->assertJsonStructure([
                'data' => [['label', 'value']],
            ]);
    }

    public function test_combo_only_returns_active_records(): void
    {
        Category::create(['name' => 'Activa', 'active' => 1]);
        Category::create(['name' => 'Inactiva', 'active' => 0]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/categories-combo');

        $response->assertJsonCount(1, 'data');
    }
}
