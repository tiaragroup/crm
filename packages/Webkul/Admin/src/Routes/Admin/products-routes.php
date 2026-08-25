<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Products\CateringMenuController;
use Webkul\Admin\Http\Controllers\Products\ActivityController;
use Webkul\Admin\Http\Controllers\Products\ProductController;
use Webkul\Admin\Http\Controllers\Products\TagController;

Route::group(['middleware' => ['user']], function () {
    Route::controller(CateringMenuController::class)->prefix('catering-menus')->group(function () {
        Route::get('', 'index')->name('admin.catering.menus.index');
        Route::get('create', 'create')->name('admin.catering.menus.create');
        Route::post('', 'store')->name('admin.catering.menus.store');
        Route::get('{menu}/edit', 'edit')->name('admin.catering.menus.edit');
        Route::put('{menu}', 'update')->name('admin.catering.menus.update');
        Route::delete('{menu}', 'destroy')->name('admin.catering.menus.delete');
    });

    Route::controller(ProductController::class)->prefix('products')->group(function () {
        Route::get('', 'index')->name('admin.products.index');

        Route::get('create', 'create')->name('admin.products.create');

        Route::post('create', 'store')->name('admin.products.store');

        Route::get('view/{id}', 'view')->name('admin.products.view');

        Route::get('edit/{id}', 'edit')->name('admin.products.edit');

        Route::put('edit/{id}', 'update')->name('admin.products.update');

        Route::get('search', 'search')->name('admin.products.search');

        Route::get('{id}/warehouses', 'warehouses')->name('admin.products.warehouses');

        Route::post('{id}/inventories/{warehouseId?}', 'storeInventories')->name('admin.products.inventories.store');

        Route::delete('{id}', 'destroy')->name('admin.products.delete');

        Route::post('mass-destroy', 'massDestroy')->name('admin.products.mass_delete');

        Route::controller(ActivityController::class)->prefix('{id}/activities')->group(function () {
            Route::get('', 'index')->name('admin.products.activities.index');
        });

        Route::controller(TagController::class)->prefix('{id}/tags')->group(function () {
            Route::post('', 'attach')->name('admin.products.tags.attach');

            Route::delete('', 'detach')->name('admin.products.tags.detach');
        });
    });
});
