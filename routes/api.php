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
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StorageConditionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\PredictionController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/welcome', fn() => dd('welcome'));
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);

        // CRUD Principal
        Route::apiResource('category', CategoryController::class);
        Route::apiResource('product', ProductController::class);

        Route::apiResource('lab', LabController::class);
        Route::apiResource('product-type', ProductTypeController::class);
        Route::apiResource('product-presentation', ProductPresentationController::class);

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

        // Reportes
        Route::prefix('reports')->group(function () {
            Route::get('sales', [ReportController::class, 'sales']);
            Route::get('inventory', [ReportController::class, 'inventory']);
            Route::get('financial', [ReportController::class, 'financial']);
        });

        // Alertas
        Route::get('alerts', [AlertController::class, 'all']);
        Route::get('stock-alerts', [AlertController::class, 'stockAlerts']);
        Route::get('expiry-alerts', [AlertController::class, 'expiryAlerts']);

        // Usuarios
        Route::apiResource('user-management', UserController::class)->except(['show']);
        Route::get('user-management/{user}', [UserController::class, 'show']);
        Route::post('change-password', [UserController::class, 'changePassword']);
        Route::get('users-combo', [UserController::class, 'combo']);

        // Roles y Permisos
        Route::apiResource('role', RoleController::class);
        Route::get('roles-combo', [RoleController::class, 'combo']);
        Route::get('permissions', [RoleController::class, 'permissions']);

        // Lotes
        Route::apiResource('batch', BatchController::class)->except(['store', 'destroy']);
        Route::post('batch/{batch}/adjust', [BatchController::class, 'adjust']);
        Route::get('batches-combo', [BatchController::class, 'combo']);
        Route::get('batches-summary', [BatchController::class, 'summary']);

        // Predicciones AI
        Route::get('predictions', [PredictionController::class, 'index']);
        Route::post('predictions/regenerate', [PredictionController::class, 'regenerate']);

        // Auditoría
        Route::get('audit/logs', [AuditController::class, 'index']);
        Route::get('audit/logs/{auditLog}', [AuditController::class, 'show']);
        Route::get('audit/stats', [AuditController::class, 'stats']);

        // Facturación PDF
        Route::get('sale/{sale}/invoice', [InvoiceController::class, 'saleInvoice']);
        Route::get('purchase/{purchase}/receipt', [InvoiceController::class, 'purchaseReceipt']);
    });
});
