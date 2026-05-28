<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Distribuidora Farmacéutica Nacional',
                'email' => 'carlos.mendoza@dfn.com',
                'phone' => '999-888-777',
                'address' => 'Av. Industrial 123, Lima',
            ],
            [
                'name' => 'Importadora Médica del Perú',
                'email' => 'maria.lopez@importmed.com',
                'phone' => '999-777-666',
                'address' => 'Jr. Comercio 456, Arequipa',
            ],
            [
                'name' => 'Genéricos del Centro',
                'email' => 'jose.ramirez@genericos.com',
                'phone' => '999-666-555',
                'address' => 'Calle Salud 789, Trujillo',
            ],
            [
                'name' => 'Suplementos y Más',
                'email' => 'ana.torres@suplementos.com',
                'phone' => '999-555-444',
                'address' => 'Av. Los Olivos 321, Lima',
            ],
            [
                'name' => 'Material Sanitario Express',
                'email' => 'pedro.castillo@matsanitario.com',
                'phone' => '999-444-333',
                'address' => 'Jr. Botica 654, Cusco',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(
                ['email' => $supplier['email']],
                array_merge($supplier, ['active' => 1])
            );
        }
    }
}
