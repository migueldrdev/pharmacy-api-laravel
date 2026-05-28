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
use App\Models\Supplier;
use App\Models\PurchaseDocumentType;
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseBatchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private Supplier $supplier;
    private PurchaseDocumentType $docType;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Administrador', 'description' => 'Acceso completo']);
        $this->user = User::factory()->create(['role_id' => $role->id]);

        $category = Category::create(['name' => 'Analgésicos']);
        $lab = Lab::create(['name' => 'Bayer']);
        $type = ProductType::create(['name' => 'Medicamento de Marca']);
        $presentation = ProductPresentation::create(['name' => 'Caja x 20 tabletas']);
        $storage = StorageCondition::create([
            'label' => 'Temperatura Ambiente (15-25°C)',
            'value' => 'room_temperature',
        ]);

        $this->product = Product::create([
            'name' => 'Paracetamol 500 mg',
            'code' => 'PARA-001',
            'stock' => 0,
            'min_stock' => 10,
            'max_stock' => 100,
            'price' => 5.00,
            'category_id' => $category->id,
            'lab_id' => $lab->id,
            'type_id' => $type->id,
            'presentation_id' => $presentation->id,
            'storage_condition_id' => $storage->id,
            'active' => 1,
            'user_created' => $this->user->id,
            'pharmaceutical_form' => 'Tableta',
            'administration_route' => 'Oral',
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Distribuidora Nacional',
            'email' => 'contacto@dist.com',
            'active' => 1,
        ]);

        $this->docType = PurchaseDocumentType::create([
            'name' => 'Factura',
            'code' => '01',
            'active' => 1,
        ]);
    }

    public function test_purchase_creates_new_batch(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/purchase', [
                'purchase_date' => now()->format('Y-m-d'),
                'total' => 150.00,
                'supplier_id' => $this->supplier->id,
                'purchase_document_type_id' => $this->docType->id,
                'details' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 30,
                        'price' => 5.00,
                        'subtotal' => 150.00,
                        'batch_number' => 'LOTE-TEST-001',
                        'expiration_date' => '2027-12-01',
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'Compra creada');

        $this->assertDatabaseHas('batches', [
            'product_id' => $this->product->id,
            'batch_number' => 'LOTE-TEST-001',
            'stock' => 30,
            'initial_stock' => 30,
        ]);
    }

    public function test_purchase_increments_existing_batch_stock(): void
    {
        Batch::create([
            'product_id' => $this->product->id,
            'batch_number' => 'LOTE-EXISTENTE',
            'stock' => 10,
            'initial_stock' => 10,
            'expiration_date' => '2027-06-15',
            'active' => 1,
            'user_created' => $this->user->id,
            'user_updated' => $this->user->id,
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/purchase', [
                'purchase_date' => now()->format('Y-m-d'),
                'total' => 100.00,
                'supplier_id' => $this->supplier->id,
                'purchase_document_type_id' => $this->docType->id,
                'details' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 20,
                        'price' => 5.00,
                        'subtotal' => 100.00,
                        'batch_number' => 'LOTE-EXISTENTE',
                        'expiration_date' => '2027-06-15',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('batches', [
            'batch_number' => 'LOTE-EXISTENTE',
            'stock' => 30,
            'initial_stock' => 30,
        ]);
    }

    public function test_purchase_without_batch_data(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/purchase', [
                'purchase_date' => now()->format('Y-m-d'),
                'total' => 50.00,
                'supplier_id' => $this->supplier->id,
                'purchase_document_type_id' => $this->docType->id,
                'details' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'price' => 5.00,
                        'subtotal' => 50.00,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $response->assertStatus(201);
    }
}
