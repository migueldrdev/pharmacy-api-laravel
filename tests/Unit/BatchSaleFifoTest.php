<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Category;
use App\Models\Lab;
use App\Models\ProductType;
use App\Models\ProductPresentation;
use App\Models\StorageCondition;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BatchSaleFifoTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Administrador', 'description' => 'Acceso completo']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->userId = $user->id;

        $category = Category::create(['name' => 'Analgésicos']);
        $lab = Lab::create(['name' => 'Bayer']);
        $type = ProductType::create(['name' => 'Medicamento Genérico']);
        $presentation = ProductPresentation::create(['name' => 'Caja x 20 tabletas']);
        $storage = StorageCondition::create([
            'label' => 'Temperatura Ambiente',
            'value' => 'room_temperature',
        ]);

        $this->product = Product::create([
            'name' => 'Paracetamol',
            'code' => 'PARA-U01',
            'stock' => 0,
            'price' => 5.00,
            'category_id' => $category->id,
            'lab_id' => $lab->id,
            'type_id' => $type->id,
            'presentation_id' => $presentation->id,
            'storage_condition_id' => $storage->id,
            'active' => 1,
            'pharmaceutical_form' => 'Tableta',
            'administration_route' => 'Oral',
            'min_stock' => 10,
            'max_stock' => 100,
            'user_created' => $this->userId,
        ]);

        Batch::create([
            'product_id' => $this->product->id,
            'batch_number' => 'LOTE-A',
            'stock' => 3,
            'initial_stock' => 3,
            'expiration_date' => '2026-01-01',
            'active' => 1,
            'user_created' => $this->userId,
            'user_updated' => $this->userId,
        ]);

        Batch::create([
            'product_id' => $this->product->id,
            'batch_number' => 'LOTE-B',
            'stock' => 10,
            'initial_stock' => 10,
            'expiration_date' => '2028-12-31',
            'active' => 1,
            'user_created' => $this->userId,
            'user_updated' => $this->userId,
        ]);
    }

    public function test_fifo_selects_expiring_batch_first(): void
    {
        $batches = Batch::where('product_id', $this->product->id)
            ->where('stock', '>', 0)
            ->where('active', 1)
            ->orderBy('expiration_date', 'asc')
            ->get();

        $this->assertEquals('LOTE-A', $batches->first()->batch_number);
        $this->assertEquals('LOTE-B', $batches->last()->batch_number);
    }

    public function test_fifo_consumes_correct_quantities(): void
    {
        $requestedQuantity = 5;
        $batches = Batch::where('product_id', $this->product->id)
            ->where('stock', '>', 0)
            ->where('active', 1)
            ->orderBy('expiration_date', 'asc')
            ->get();

        $remaining = $requestedQuantity;
        $consumed = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $take = min($batch->stock, $remaining);
            $consumed[$batch->batch_number] = $take;
            $remaining -= $take;
        }

        $this->assertEquals(3, $consumed['LOTE-A']);
        $this->assertEquals(2, $consumed['LOTE-B']);
        $this->assertEquals(0, $remaining);
    }

    public function test_fifo_exhausts_before_moving_to_next(): void
    {
        $batches = Batch::where('product_id', $this->product->id)
            ->where('stock', '>', 0)
            ->where('active', 1)
            ->orderBy('expiration_date', 'asc')
            ->get();

        $remaining = 3;
        $consumedFromFirst = 0;
        $consumedFromSecond = 0;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $take = min($batch->stock, $remaining);
            if ($batch->batch_number === 'LOTE-A') {
                $consumedFromFirst = $take;
            } else {
                $consumedFromSecond = $take;
            }
            $remaining -= $take;
        }

        $this->assertEquals(3, $consumedFromFirst);
        $this->assertEquals(0, $consumedFromSecond);
    }
}
