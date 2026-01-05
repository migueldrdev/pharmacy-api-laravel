<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Services\Product\ProductService;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Product\ProductResource; // Importa tu Resource
use Throwable;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $products = $this->service->list();
            return ResponseHelper::success(
                data: ProductResource::collection($products),
                message: 'Consulta exitosa',
                title: 'Listado de productos'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo listar',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_created'] = Auth::id(); // Agrega el usuario autenticado
            $product = $this->service->create($data);
            return ResponseHelper::success(
                data: $product,
                message: 'Producto creado exitosamente',
                title: 'Producto creado',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear el producto',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(Product $product)
    {
        return ResponseHelper::success(
            data: $product,
            message: 'Consulta exitosa',
            title: 'Producto'
        );
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            $data = $request->validated();
            $data['user_updated'] = Auth::id();
            Log::info($data);
            $updated = $this->service->update($product, $data);
            return ResponseHelper::success(
                data: $updated,
                message: 'Producto actualizado correctamente',
                title: 'Producto actualizado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar el producto',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(Product $product)
    {
        try {
            $this->service->delete($product, Auth::id());
            return ResponseHelper::success(
                message: 'Producto eliminado correctamente',
                title: 'Producto eliminado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar el producto',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }
}
