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
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaleFifoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private Batch $batchProximo;
    private Batch $batchLejano;

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
            'code' => 'PARA-FIFO',
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

        $this->batchProximo = Batch::create([
            'product_id' => $this->product->id,
            'batch_number' => 'LOTE-PROXIMO',
            'stock' => 10,
            'initial_stock' => 10,
            'expiration_date' => '2026-06-30',
            'active' => 1,
            'user_created' => $this->user->id,
            'user_updated' => $this->user->id,
        ]);

        $this->batchLejano = Batch::create([
            'product_id' => $this->product->id,
            'batch_number' => 'LOTE-LEJANO',
            'stock' => 50,
            'initial_stock' => 50,
            'expiration_date' => '2027-12-31',
            'active' => 1,
            'user_created' => $this->user->id,
            'user_updated' => $this->user->id,
        ]);
    }

    public function test_sale_consumes_fifo_expiring_batch_first(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/sale', [
                'sale_date' => now()->format('Y-m-d'),
                'total' => 25.00,
                'details' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 5,
                        'price' => 5.00,
                        'subtotal' => 25.00,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $this->batchProximo->refresh();
        $this->batchLejano->refresh();

        $this->assertEquals(5, $this->batchProximo->stock);
        $this->assertEquals(50, $this->batchLejano->stock);
    }

    public function test_sale_spans_multiple_batches_when_first_is_insufficient(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/sale', [
                'sale_date' => now()->format('Y-m-d'),
                'total' => 75.00,
                'details' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 15,
                        'price' => 5.00,
                        'subtotal' => 75.00,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $this->batchProximo->refresh();
        $this->batchLejano->refresh();

        $this->assertEquals(0, $this->batchProximo->stock);
        $this->assertEquals(45, $this->batchLejano->stock);
    }

    public function test_sale_fails_when_insufficient_batch_stock(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/sale', [
                'sale_date' => now()->format('Y-m-d'),
                'total' => 500.00,
                'details' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 100,
                        'price' => 5.00,
                        'subtotal' => 500.00,
                    ],
                ],
            ]);

        $response->assertStatus(500);
    }
}
