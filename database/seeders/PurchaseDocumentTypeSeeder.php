<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseDocumentType;
use App\Models\User;

class PurchaseDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::first()?->id ?? 1;

        $types = [
            ['name' => 'Factura', 'code' => '01'],
            ['name' => 'Boleta', 'code' => '03'],
            ['name' => 'Nota de Crédito', 'code' => '07'],
        ];

        foreach ($types as $type) {
            PurchaseDocumentType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => 1, 'user_created' => $userId]
            );
        }
    }
}
