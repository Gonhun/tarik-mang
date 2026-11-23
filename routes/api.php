<?php

use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AssetRequestController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\StockOpnameController;
use Illuminate\Support\Facades\Route;

Route::get('/check', function () {
    return response()->json(['status' => 'API OK']);
});

/*
| API aplikasi mobile (Flutter). Autentikasi: header "Authorization: Bearer {token}" dari /v1/auth/login.
| Dokumentasi endpoint: docs/api-mobile.md
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('auth.login');

    Route::middleware(['auth:sanctum', 'throttle:180,1'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        // Aset
        Route::get('assets/mine', [AssetController::class, 'mine'])->name('assets.mine');
        Route::get('assets/lookup', [AssetController::class, 'lookup'])->name('assets.lookup');
        Route::get('assets/{asset}', [AssetController::class, 'show'])->whereNumber('asset')->name('assets.show');

        // Katalog item pengajuan
        Route::get('catalog/asset-types', [AssetRequestController::class, 'types'])->name('catalog.types');
        Route::get('catalog/asset-brands', [AssetRequestController::class, 'brands'])->name('catalog.brands');
        Route::get('catalog/asset-series', [AssetRequestController::class, 'series'])->name('catalog.series');

        // Pengajuan aset
        Route::get('asset-requests', [AssetRequestController::class, 'index'])->name('asset-requests.index');
        Route::get('asset-requests/form', [AssetRequestController::class, 'form'])->name('asset-requests.form');
        Route::get('asset-requests/beneficiaries', [AssetRequestController::class, 'beneficiaries'])->name('asset-requests.beneficiaries');
        Route::post('asset-requests', [AssetRequestController::class, 'store'])->name('asset-requests.store');
        Route::get('asset-requests/{assetRequest}', [AssetRequestController::class, 'show'])->whereNumber('assetRequest')->name('asset-requests.show');

        // Approval
        Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::get('approvals/transactions/{approval}', [ApprovalController::class, 'showTransaction'])->whereNumber('approval')->name('approvals.transactions.show');
        Route::post('approvals/asset-requests/{approval}/decision', [ApprovalController::class, 'decideAssetRequest'])->whereNumber('approval')->name('approvals.asset-requests.decide');
        Route::post('approvals/transactions/{approval}/decision', [ApprovalController::class, 'decideTransaction'])->whereNumber('approval')->name('approvals.transactions.decide');

        // Stock opname (izin sama dengan menu web)
        Route::middleware('can:Stock Opname')->group(function () {
            Route::get('stock-opnames', [StockOpnameController::class, 'index'])->name('stock-opnames.index');
            Route::get('stock-opnames/{opname}', [StockOpnameController::class, 'show'])->whereNumber('opname')->name('stock-opnames.show');
            Route::get('stock-opnames/{opname}/items', [StockOpnameController::class, 'items'])->whereNumber('opname')->name('stock-opnames.items');
            Route::post('stock-opnames/{opname}/scan', [StockOpnameController::class, 'scan'])->whereNumber('opname')->middleware('throttle:240,1')->name('stock-opnames.scan');
            Route::patch('stock-opnames/{opname}/items/{item}', [StockOpnameController::class, 'updateItem'])->whereNumber(['opname', 'item'])->name('stock-opnames.items.update');
            Route::delete('stock-opnames/{opname}/items/{item}', [StockOpnameController::class, 'destroyItem'])->whereNumber(['opname', 'item'])->name('stock-opnames.items.destroy');
        });
    });
});
