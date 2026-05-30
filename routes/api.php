<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\ProductTypeController;
use App\Http\Controllers\Api\ProductPresentationController;
use App\Http\Controllers\Api\DocumentTypeController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseDocumentTypeController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\StorageConditionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/welcome', fn() => dd('welcome'));
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);

        Route::apiResource('category', CategoryController::class);
        Route::apiResource('product', ProductController::class);

        Route::apiResource('lab', LabController::class);
        Route::apiResource('product-type', ProductTypeController::class); // Usamos 'product-type' para la URL
        Route::apiResource('product-presentation', ProductPresentationController::class); // Usamos 'product-presentation' para la URL

        Route::apiResource('document-type', DocumentTypeController::class);
        Route::apiResource('client', ClientController::class);
        Route::apiResource('supplier', SupplierController::class);
        Route::apiResource('purchase-document-type', PurchaseDocumentTypeController::class);
        Route::apiResource('purchase', PurchaseController::class);
        Route::apiResource('sale', SaleController::class);
        Route::apiResource('storage-condition', StorageConditionController::class);

        // Rutas para combos (filtradas por 'active' y formato label/value)
        Route::get('categories-combo', [CategoryController::class, 'combo']);
        Route::get('labs-combo', [LabController::class, 'combo']);
        Route::get('product-types-combo', [ProductTypeController::class, 'combo']);
        Route::get('product-presentations-combo', [ProductPresentationController::class, 'combo']);
        Route::get('products-combo', [ProductController::class, 'combo']);
        Route::get('document-types-combo', [DocumentTypeController::class, 'combo']);
        Route::get('clients-combo', [ClientController::class, 'combo']);
        Route::get('suppliers-combo', [SupplierController::class, 'combo']);
        Route::get('purchase-document-types-combo', [PurchaseDocumentTypeController::class, 'combo']);
        Route::get('storage-conditions-combo', [StorageConditionController::class, 'combo']);
    });
});
