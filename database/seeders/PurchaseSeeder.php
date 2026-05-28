<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\PurchaseDocumentType;
use App\Models\User;
use App\Models\Batch;
use Illuminate\Support\Facades\Auth;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $suppliers = Supplier::all();
        $purchaseDocTypes = PurchaseDocumentType::all();
        $user = User::first();

        if ($products->isEmpty()) {
            $this->call(ProductSeeder::class);
            $products = Product::all();
        }
        if ($suppliers->isEmpty()) {
            $this->call(SupplierSeeder::class);
            $suppliers = Supplier::all();
        }

        Auth::loginUsingId($user->id);

        $purchases = [
            [
                'supplier_name' => 'Distribuidora Farmacéutica Nacional',
                'purchase_document_type_code' => '01',
                'details' => [
                    ['product_code' => 'PRD00001', 'quantity' => 50, 'price' => 2.50, 'batch_number' => 'LOTE-001-2026', 'expiration_date' => '2027-06-15'],
                    ['product_code' => 'PRD00004', 'quantity' => 40, 'price' => 6.00, 'batch_number' => 'LOTE-002-2026', 'expiration_date' => '2027-12-01'],
                    ['product_code' => 'PRD00012', 'quantity' => 30, 'price' => 4.50, 'batch_number' => 'LOTE-003-2026', 'expiration_date' => '2027-09-10'],
                ],
            ],
            [
                'supplier_name' => 'Genéricos del Centro',
                'purchase_document_type_code' => '01',
                'details' => [
                    ['product_code' => 'PRD00002', 'quantity' => 25, 'price' => 3.50, 'batch_number' => 'LOTE-004-2026', 'expiration_date' => '2026-08-20'],
                    ['product_code' => 'PRD00017', 'quantity' => 50, 'price' => 3.00, 'batch_number' => 'LOTE-005-2026', 'expiration_date' => '2027-06-30'],
                    ['product_code' => 'PRD00006', 'quantity' => 15, 'price' => 5.00, 'batch_number' => 'LOTE-006-2026', 'expiration_date' => '2026-09-30'],
                ],
            ],
            [
                'supplier_name' => 'Importadora Médica del Perú',
                'purchase_document_type_code' => '01',
                'details' => [
                    ['product_code' => 'PRD00007', 'quantity' => 100, 'price' => 3.00, 'batch_number' => 'LOTE-007-2026', 'expiration_date' => '2027-07-10'],
                    ['product_code' => 'PRD00020', 'quantity' => 80, 'price' => 1.50, 'batch_number' => 'LOTE-008-2026', 'expiration_date' => '2029-01-01'],
                ],
            ],
            [
                'supplier_name' => 'Suplementos y Más',
                'purchase_document_type_code' => '01',
                'details' => [
                    ['product_code' => 'PRD00008', 'quantity' => 60, 'price' => 4.00, 'batch_number' => 'LOTE-009-2026', 'expiration_date' => '2028-01-01'],
                    ['product_code' => 'PRD00009', 'quantity' => 30, 'price' => 7.00, 'batch_number' => 'LOTE-010-2026', 'expiration_date' => '2027-11-20'],
                    ['product_code' => 'PRD00023', 'quantity' => 100, 'price' => 18.00, 'batch_number' => 'LOTE-011-2026', 'expiration_date' => '2028-06-01'],
                ],
            ],
            [
                'supplier_name' => 'Material Sanitario Express',
                'purchase_document_type_code' => '01',
                'details' => [
                    ['product_code' => 'PRD00021', 'quantity' => 60, 'price' => 2.50, 'batch_number' => 'LOTE-012-2026', 'expiration_date' => '2028-03-15'],
                    ['product_code' => 'PRD00011', 'quantity' => 20, 'price' => 5.50, 'batch_number' => 'LOTE-013-2026', 'expiration_date' => '2027-05-01'],
                ],
            ],
        ];

        foreach ($purchases as $purchaseData) {
            $supplier = $suppliers->firstWhere('name', $purchaseData['supplier_name']);
            $docType = $purchaseDocTypes->firstWhere('code', $purchaseData['purchase_document_type_code']);

            $details = [];
            foreach ($purchaseData['details'] as $d) {
                $product = $products->firstWhere('code', $d['product_code']);
                $subtotal = $d['quantity'] * $d['price'];
                $details[] = [
                    'product_id' => $product->id,
                    'quantity' => $d['quantity'],
                    'price' => $d['price'],
                    'subtotal' => $subtotal,
                    'batch_number' => $d['batch_number'],
                    'expiration_date' => $d['expiration_date'],
                    'user_created' => $user->id,
                    'user_updated' => $user->id,
                    'active' => 1,
                ];
            }

            $total = collect($details)->sum('subtotal');

            $purchase = Purchase::create([
                'supplier_id' => $supplier->id,
                'purchase_document_type_id' => $docType->id,
                'purchase_date' => now()->subDays(rand(1, 60)),
                'total' => $total,
                'user_id' => $user->id,
                'user_created' => $user->id,
                'user_updated' => $user->id,
                'active' => 1,
            ]);

            foreach ($details as $detail) {
                // Crear o actualizar el lote
                if (isset($detail['batch_number']) && isset($detail['expiration_date'])) {
                    $batch = Batch::firstOrCreate(
                        [
                            'product_id' => $detail['product_id'],
                            'batch_number' => $detail['batch_number'],
                        ],
                        [
                            'stock' => 0,
                            'initial_stock' => 0,
                            'expiration_date' => $detail['expiration_date'],
                            'active' => 1,
                            'user_created' => $user->id,
                            'user_updated' => $user->id,
                        ]
                    );

                    $batch->stock += $detail['quantity'];
                    $batch->initial_stock += $detail['quantity'];
                    $batch->save();

                    $detail['batch_id'] = $batch->id;
                }

                unset($detail['batch_number'], $detail['expiration_date']);
                $purchase->purchaseDetails()->create($detail);
            }
        }
    }
}
