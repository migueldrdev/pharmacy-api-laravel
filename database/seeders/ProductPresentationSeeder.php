<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductPresentation;

class ProductPresentationSeeder extends Seeder
{
    public function run(): void
    {
        $presentations = [
            'Caja x 10 tabletas',
            'Caja x 20 tabletas',
            'Caja x 30 tabletas',
            'Frasco x 120 ml',
            'Blíster x 10 cápsulas',
            'Tubo x 30 g',
            'Unidad',
        ];

        foreach ($presentations as $name) {
            ProductPresentation::firstOrCreate(['name' => $name], ['active' => 1]);
        }
    }
}
