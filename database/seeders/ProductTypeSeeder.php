<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductType;

class ProductTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Medicamento de Marca',
            'Medicamento Genérico',
            'Material Sanitario',
            'Producto Natural',
            'Suplemento Alimenticio',
        ];

        foreach ($types as $name) {
            ProductType::firstOrCreate(['name' => $name], ['active' => 1]);
        }
    }
}
