<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;
use App\Models\User;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::first()?->id ?? 1;

        $types = [
            ['name' => 'DNI', 'code' => '01'],
            ['name' => 'RUC', 'code' => '06'],
            ['name' => 'Pasaporte', 'code' => '04'],
            ['name' => 'Cédula de Identidad', 'code' => '03'],
        ];

        foreach ($types as $type) {
            DocumentType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => 1, 'user_created' => $userId]
            );
        }
    }
}
