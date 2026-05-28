<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lab;

class LabSeeder extends Seeder
{
    public function run(): void
    {
        $labs = [
            'Bayer',
            'Pfizer',
            'Roche',
            'Novartis',
            'Sanofi',
            'GlaxoSmithKline',
            'Merck',
            'Genéricos Nacionales',
        ];

        foreach ($labs as $name) {
            Lab::firstOrCreate(['name' => $name], ['active' => 1]);
        }
    }
}
