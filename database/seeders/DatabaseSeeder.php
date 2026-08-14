<?php

namespace Database\Seeders;

use App\Models\User;
use Dom\Document;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            // Seeders de tablas maestras
            DocumentTypeSeeder::class,
            CategorySeeder::class,
            LabSeeder::class,
            StorageConditionSeeder::class,
            ProductTypeSeeder::class,
            ProductPresentationSeeder::class,
            SupplierSeeder::class,
            PurchaseDocumentTypeSeeder::class,
            ClientSeeder::class,
            // Productos
            ProductSeeder::class,
            // Transacciones: Compras primero (crean y abastecen lotes)
            PurchaseSeeder::class,
            // Ventas después (descuentan lotes mediante FIFO)
            SaleSeeder::class,
        ]);
    }
}
