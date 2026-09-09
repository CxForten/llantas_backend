<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController, ProductController, CategoryController,
    SaleController, CashSessionController, StockController,
    ReportController, SettingController, CustomerController
};

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('customers',  CustomerController::class);

    Route::apiResource('products', ProductController::class);
    Route::get('products/{product}/kardex', [ProductController::class, 'kardex']);
    Route::post('stock/adjust',             [StockController::class, 'adjust']);

    Route::get('stock/movements', [StockController::class, 'movements']);
    
    Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show']);
    Route::post('sales/{sale}/void', [SaleController::class, 'void']);
    
    Route::get('cash-sessions/current', [CashSessionController::class, 'current']);
    Route::post('cash-sessions/open',   [CashSessionController::class, 'open']);
    Route::post('cash-sessions/close',  [CashSessionController::class, 'close']);
    Route::get('cash-sessions',         [CashSessionController::class, 'index']);
    Route::get('cash-sessions/{cashSession}', [CashSessionController::class, 'show']);
    
    Route::get('reports/dashboard',    [ReportController::class, 'dashboard']);
    Route::get('reports/sales',        [ReportController::class, 'sales']);
    Route::get('reports/top-products', [ReportController::class, 'topProducts']);
    Route::get('reports/top-specs',    [ReportController::class, 'topSpecs']);

    Route::get('settings',  [SettingController::class, 'index']);
    Route::put('settings',  [SettingController::class, 'update']);
});