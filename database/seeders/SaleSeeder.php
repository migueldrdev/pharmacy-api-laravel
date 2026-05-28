<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Client;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Batch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $clients = Client::all();
        $documentTypes = DocumentType::all();
        $user = User::first();

        if ($products->isEmpty()) {
            $this->call(ProductSeeder::class);
            $products = Product::all();
        }
        if ($clients->isEmpty()) {
            $this->call(ClientSeeder::class);
            $clients = Client::all();
        }

        Auth::loginUsingId($user->id);

        $sales = [
            [
                'client_name' => 'María García López',
                'document_type_code' => null,
                'details' => [
                    ['product_code' => 'PRD00001', 'quantity' => 3],
                    ['product_code' => 'PRD00004', 'quantity' => 2],
                ],
            ],
            [
                'client_name' => 'Juan Pérez Torres',
                'document_type_code' => null,
                'details' => [
                    ['product_code' => 'PRD00007', 'quantity' => 5],
                    ['product_code' => 'PRD00002', 'quantity' => 2],
                    ['product_code' => 'PRD00012', 'quantity' => 1],
                ],
            ],
            [
                'client_name' => null,
                'document_type_code' => '01',
                'customer_name' => 'Pedro Alvarado',
                'document_number' => '44556677',
                'details' => [
                    ['product_code' => 'PRD00006', 'quantity' => 2],
                    ['product_code' => 'PRD00017', 'quantity' => 3],
                ],
            ],
            [
                'client_name' => 'Rosa Fernández Díaz',
                'document_type_code' => null,
                'details' => [
                    ['product_code' => 'PRD00001', 'quantity' => 5],
                    ['product_code' => 'PRD00008', 'quantity' => 3],
                    ['product_code' => 'PRD00009', 'quantity' => 2],
                ],
            ],
            [
                'client_name' => 'Farmacia Salud y Vida',
                'document_type_code' => null,
                'details' => [
                    ['product_code' => 'PRD00001', 'quantity' => 10],
                    ['product_code' => 'PRD00004', 'quantity' => 8],
                    ['product_code' => 'PRD00007', 'quantity' => 15],
                    ['product_code' => 'PRD00020', 'quantity' => 50],
                ],
            ],
            [
                'client_name' => null,
                'document_type_code' => '01',
                'customer_name' => 'Lucía Rojas',
                'document_number' => '55667788',
                'details' => [
                    ['product_code' => 'PRD00001', 'quantity' => 2],
                    ['product_code' => 'PRD00007', 'quantity' => 2],
                    ['product_code' => 'PRD00021', 'quantity' => 5],
                ],
            ],
            [
                'client_name' => 'Carlos Martínez Ruiz',
                'document_type_code' => null,
                'details' => [
                    ['product_code' => 'PRD00002', 'quantity' => 3],
                    ['product_code' => 'PRD00005', 'quantity' => 2],
                ],
            ],
            [
                'client_name' => 'Boticas del Centro',
                'document_type_code' => null,
                'details' => [
                    ['product_code' => 'PRD00004', 'quantity' => 10],
                    ['product_code' => 'PRD00013', 'quantity' => 5],
                    ['product_code' => 'PRD00022', 'quantity' => 2],
                ],
            ],
        ];

        foreach ($sales as $saleData) {
            $client = null;
            $docType = null;
            $customerName = null;
            $documentNumber = null;

            if ($saleData['client_name']) {
                $client = $clients->firstWhere('name', $saleData['client_name']);
            } else {
                $docType = $documentTypes->firstWhere('code', $saleData['document_type_code']);
                $customerName = $saleData['customer_name'] ?? null;
                $documentNumber = $saleData['document_number'] ?? null;
            }

            $sale = Sale::create([
                'client_id' => $client?->id,
                'document_type_id' => $docType?->id,
                'customer_name' => $customerName,
                'document_number' => $documentNumber,
                'sale_date' => now()->subDays(rand(1, 30)),
                'total' => 0,
                'user_id' => $user->id,
                'user_created' => $user->id,
                'user_updated' => $user->id,
                'active' => 1,
            ]);

            $total = 0;

            foreach ($saleData['details'] as $d) {
                $product = $products->firstWhere('code', $d['product_code']);
                $quantity = $d['quantity'];
                $price = $product->price;
                $subtotal = $quantity * $price;
                $total += $subtotal;

                $saleDetail = $sale->saleDetails()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);

                // Aplicar FIFO: descontar lotes más próximos a vencer
                $remaining = $quantity;
                $batches = Batch::where('product_id', $product->id)
                    ->where('stock', '>', 0)
                    ->where('active', 1)
                    ->orderBy('expiration_date', 'asc')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;

                    $take = min($batch->stock, $remaining);
                    $batch->stock -= $take;
                    $batch->save();

                    DB::table('batch_sale_detail')->insert([
                        'sale_detail_id' => $saleDetail->id,
                        'batch_id' => $batch->id,
                        'quantity' => $take,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $remaining -= $take;
                }
            }

            $sale->total = $total;
            $sale->save();
        }
    }
}
