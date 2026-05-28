<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Analgésicos',
            'Antibióticos',
            'Antiinflamatorios',
            'Antigripales',
            'Vitaminas y Suplementos',
            'Dermatológicos',
            'Gastrointestinales',
            'Cardiovasculares',
            'Antialérgicos',
            'Material de Curación',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name], ['active' => 1]);
        }
    }
}
