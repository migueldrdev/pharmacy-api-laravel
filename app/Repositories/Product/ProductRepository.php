<?php

namespace App\Repositories\Product;

use App\Models\Product;
use Carbon\Carbon;

class ProductRepository
{
    public function all()
    {
        // Cargar relaciones para el listado
        return Product::with(['category', 'lab', 'type', 'presentation', 'storageCondition'])->get();
    }

    public function find($id)
    {
        // Cargar relaciones para el detalle
        return Product::with(['category', 'lab', 'type', 'presentation', 'storageCondition'])->findOrFail($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product;
    }

    public function delete(Product $product, $userId): bool
    {
        return $product->update([
            'active' => 0,
            'user_updated' => $userId,
            'updated_at' => Carbon::now(),
        ]);
    }

    public function getActiveForCombo()
    {
        return Product::where('active', 1)->get();
    }
}
