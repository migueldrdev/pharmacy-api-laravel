<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Services\Category\CategoryService;
use App\Models\Category;
use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\Category\CategoryComboResource;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CategoryController extends Controller
{
    protected CategoryService $service;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $categories = $this->service->list();
            return ResponseHelper::success(
                data: CategoryResource::collection($categories),
                message: 'Consulta exitosa',
                title: 'Listado de categorías'
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

    public function store(StoreCategoryRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_created'] = Auth::id();
            $category = $this->service->create($data);
            return ResponseHelper::success(
                data: new CategoryResource($category),
                message: 'Categoría creada exitosamente',
                title: 'Categoría creada',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear la categoría',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(Category $category)
    {
        return ResponseHelper::success(
            data: new CategoryResource($category),
            message: 'Consulta exitosa',
            title: 'Categoría'
        );
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        try {
            $data = $request->validated();
            $data['user_updated'] = Auth::id();
            $updated = $this->service->update($category, $data);
            return ResponseHelper::success(
                data: new CategoryResource($updated),
                message: 'Categoría actualizada correctamente',
                title: 'Categoría actualizada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar la categoría',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(Category $category)
    {
        try {
            $this->service->delete($category, Auth::id());
            return ResponseHelper::success(
                message: 'Categoría eliminada correctamente',
                title: 'Categoría eliminada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar la categoría',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    /**
     * Obtiene categorías activas para un combo (select).
     */
    public function combo()
    {
        try {
            $categories = $this->service->getActiveCategoriesForCombo();
            return ResponseHelper::success(
                data: CategoryComboResource::collection($categories),
                message: 'Consulta exitosa',
                title: 'Listado de categorías para combo'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo listar para combo',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }
}
