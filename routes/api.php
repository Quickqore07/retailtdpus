<?php

use App\Http\Controllers\Api\CustomerImportController;
use App\Http\Controllers\Api\CustomerPaymentImportController;
use App\Http\Controllers\Api\QuickqoreApInvoiceReverseSyncController;
use App\Http\Controllers\Api\QuickqoreSendController;
use App\Http\Controllers\Api\SalesInvoiceImportController;
use App\Services\WorkbrightService;
use Illuminate\Support\Facades\Route;

Route::post('/workbright-webhook', [WorkbrightService::class, 'workbrightWebhook'])->name('workbright-webhook');
Route::get('/workbright-webhook', function () {
    return 'ok';
})->name('workbright-webhook-test');

/** Quickqore server-to-server: push bill changes back into upload-portal AP invoices. */
Route::middleware([\App\Http\Middleware\SecurityKeyCheckMiddleware::class])->group(function () {
    Route::match(['put', 'post'], '/quickqore/ap-invoices/sync', [QuickqoreApInvoiceReverseSyncController::class, 'sync'])
        ->name('quickqore.ap-invoices.sync');

    Route::post('/quickqore/ar-invoices/sync', [SalesInvoiceImportController::class, 'store'])
        ->name('quickqore.ar-invoices.sync');

    Route::post('/quickqore/ar-invoices/update', [SalesInvoiceImportController::class, 'update'])
        ->name('quickqore.ar-invoices.update');

    Route::post('/quickqore/ar-invoices/delete', [SalesInvoiceImportController::class, 'destroy'])
        ->name('quickqore.ar-invoices.delete');

    Route::post('/quickqore/ar-payments/sync', [CustomerPaymentImportController::class, 'store'])
        ->name('quickqore.ar-payments.sync');

    Route::post('/quickqore/ar-payments/update', [CustomerPaymentImportController::class, 'update'])
        ->name('quickqore.ar-payments.update');

    Route::post('/quickqore/ar-payments/delete', [CustomerPaymentImportController::class, 'destroy'])
        ->name('quickqore.ar-payments.delete');

    Route::post('/quickqore/ar-customers/sync', [CustomerImportController::class, 'store'])
        ->name('quickqore.ar-customers.sync');

    Route::post('/quickqore/ar-customers/update', [CustomerImportController::class, 'update'])
        ->name('quickqore.ar-customers.update');

    Route::post('/quickqore/ar-customers/delete', [CustomerImportController::class, 'destroy'])
        ->name('quickqore.ar-customers.delete');

    Route::post('/quickqore/ideal-costs', [QuickqoreSendController::class, 'idealCosts'])
        ->name('api.quickqore.ideal-costs');
});
