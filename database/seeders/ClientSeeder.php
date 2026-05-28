<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\DocumentType;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $dni = DocumentType::where('code', '01')->first();
        $ruc = DocumentType::where('code', '06')->first();

        $clients = [
            [
                'name' => 'María García López',
                'document_number' => '12345678',
                'document_type_id' => $dni->id,
                'email' => 'maria.garcia@email.com',
                'phone' => '987-654-321',
                'address' => 'Av. Arequipa 123, Lima',
            ],
            [
                'name' => 'Juan Pérez Torres',
                'document_number' => '87654321',
                'document_type_id' => $dni->id,
                'email' => 'juan.perez@email.com',
                'phone' => '987-123-456',
                'address' => 'Jr. Cusco 456, Lima',
            ],
            [
                'name' => 'Rosa Fernández Díaz',
                'document_number' => '45678912',
                'document_type_id' => $dni->id,
                'email' => 'rosa.fernandez@email.com',
                'phone' => '987-789-123',
                'address' => 'Calle Lima 789, Callao',
            ],
            [
                'name' => 'Carlos Martínez Ruiz',
                'document_number' => '34567891',
                'document_type_id' => $dni->id,
                'email' => 'carlos.martinez@email.com',
                'phone' => '987-456-789',
                'address' => 'Av. Brasil 321, Lima',
            ],
            [
                'name' => 'Ana Sánchez Vega',
                'document_number' => '23456789',
                'document_type_id' => $dni->id,
                'email' => 'ana.sanchez@email.com',
                'phone' => '987-321-654',
                'address' => 'Jr. Huancayo 654, Lima',
            ],
            [
                'name' => 'Farmacia Salud y Vida',
                'document_number' => '20123456789',
                'document_type_id' => $ruc->id,
                'email' => 'contacto@farmaciasaludyvida.com',
                'phone' => '01-456-7890',
                'address' => 'Av. Javier Prado 890, Lima',
            ],
            [
                'name' => 'Boticas del Centro',
                'document_number' => '20987654321',
                'document_type_id' => $ruc->id,
                'email' => 'ventas@boticasdelcentro.com',
                'phone' => '01-345-6789',
                'address' => 'Jr. Abancay 567, Lima',
            ],
        ];

        foreach ($clients as $data) {
            Client::firstOrCreate(
                ['document_number' => $data['document_number']],
                array_merge($data, ['active' => 1])
            );
        }
    }
}
