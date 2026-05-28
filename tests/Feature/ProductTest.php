<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\Category;
use App\Models\Lab;
use App\Models\ProductType;
use App\Models\ProductPresentation;
use App\Models\StorageCondition;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;
    private Lab $lab;
    private ProductType $type;
    private ProductPresentation $presentation;
    private StorageCondition $storageCondition;
    private array $baseData;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Administrador', 'description' => 'Acceso completo']);
        $this->user = User::factory()->create(['role_id' => $role->id]);

        $this->category = Category::create(['name' => 'Analgésicos']);
        $this->lab = Lab::create(['name' => 'Bayer']);
        $this->type = ProductType::create(['name' => 'Medicamento de Marca']);
        $this->presentation = ProductPresentation::create(['name' => 'Caja x 20 tabletas']);
        $this->storageCondition = StorageCondition::create([
            'label' => 'Temperatura Ambiente (15-25°C)',
            'value' => 'room_temperature',
        ]);

        $this->baseData = [
            'name' => 'Paracetamol 500 mg',
            'code' => 'TEST-001',
            'description' => 'Analgésico de prueba',
            'stock' => 50,
            'min_stock' => 10,
            'max_stock' => 100,
            'price' => 5.00,
            'concentration' => '500mg',
            'pharmaceutical_form' => 'Tableta',
            'administration_route' => 'Oral',
            'category_id' => $this->category->id,
            'lab_id' => $this->lab->id,
            'type_id' => $this->type->id,
            'presentation_id' => $this->presentation->id,
            'storage_condition_id' => $this->storageCondition->id,
        ];
    }

    public function test_admin_can_list_products(): void
    {
        Product::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'lab_id' => $this->lab->id,
            'type_id' => $this->type->id,
            'presentation_id' => $this->presentation->id,
            'storage_condition_id' => $this->storageCondition->id,
            'active' => 1,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/product');

        $response->assertOk()
            ->assertJsonPath('title', 'Listado de productos')
            ->assertJsonCount(5, 'data');
    }

    public function test_admin_can_create_product(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/product', $this->baseData);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'Producto creado');

        $this->assertDatabaseHas('products', [
            'name' => 'Paracetamol 500 mg',
            'active' => 1,
            'user_created' => $this->user->id,
        ]);
    }

    public function test_admin_can_view_product(): void
    {
        $product = Product::create($this->baseData + ['active' => 1, 'user_created' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/product/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Paracetamol 500 mg');
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::create($this->baseData + ['active' => 1, 'user_created' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/product/{$product->id}", [
                'name' => 'Paracetamol 750 mg',
                'code' => 'TEST-001',
                'stock' => 75,
                'price' => 8.00,
                'pharmaceutical_form' => 'Tableta',
                'min_stock' => 10,
                'category_id' => $this->category->id,
                'lab_id' => $this->lab->id,
                'type_id' => $this->type->id,
                'presentation_id' => $this->presentation->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('title', 'Producto actualizado');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Paracetamol 750 mg',
            'user_updated' => $this->user->id,
        ]);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::create($this->baseData + ['active' => 1, 'user_created' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/product/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('title', 'Producto eliminado');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'active' => 0,
        ]);
    }

    public function test_product_code_must_be_unique(): void
    {
        Product::create($this->baseData + ['active' => 1, 'user_created' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/product', $this->baseData);

        $response->assertStatus(422);
    }
}
