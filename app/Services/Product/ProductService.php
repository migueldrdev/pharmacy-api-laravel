<?php

namespace App\Services\Product;

use App\Repositories\Product\ProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    protected ProductRepository $repo;

    public function __construct(ProductRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }

    public function create(array $data): Product
    {
        DB::beginTransaction();

        try {
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                // Modificado para usar storage s3 o local estandarizado
                $data['image'] = Storage::disk(config('filesystems.default'))->putFile('products', $data['image']);
            }

            $product = $this->repo->create($data);
            DB::commit();
            return $product;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Product $product, array $data): Product
    {
        DB::beginTransaction();

        try {
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                if ($product->image) {
                    Storage::disk(config('filesystems.default'))->delete($product->image);
                }
                $data['image'] = Storage::disk(config('filesystems.default'))->putFile('products', $data['image']);
            } else {
                unset($data['image']);
            }

            $updated = $this->repo->update($product, $data);
            DB::commit();
            return $updated;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(Product $product): void
    {
        DB::beginTransaction();

        try {
            $this->repo->delete($product);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getCombo()
    {
        return $this->repo->getActiveForCombo();
    }
    }
}
