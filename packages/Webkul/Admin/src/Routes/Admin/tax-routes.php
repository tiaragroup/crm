<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Tax\DraftController;
use Webkul\Admin\Http\Controllers\Tax\SellerController;

Route::prefix('tax-documents')->name('admin.tax_documents.')->controller(DraftController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
    Route::put('/{id}', 'update')->whereNumber('id')->name('update');
});

Route::prefix('tax-sellers')->name('admin.tax_sellers.')->controller(SellerController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
    Route::put('/{id}', 'update')->whereNumber('id')->name('update');
});
