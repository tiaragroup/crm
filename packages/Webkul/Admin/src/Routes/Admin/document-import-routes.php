<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\DocumentImport\DocumentImportController;

Route::controller(DocumentImportController::class)->prefix('document-imports')->group(function () {
    Route::get('', 'index')->name('admin.document_imports.index');
    Route::get('get', 'get')->name('admin.document_imports.get');
    Route::get('create', 'create')->name('admin.document_imports.create');
    Route::post('', 'store')->name('admin.document_imports.store');
    Route::get('{id}/review', 'review')->name('admin.document_imports.review');
    Route::put('{id}', 'update')->name('admin.document_imports.update');
    Route::match(['post', 'put'], '{id}/import', 'import')->name('admin.document_imports.import');
    Route::get('{id}/download', 'download')->name('admin.document_imports.download');
    Route::delete('{id}', 'destroy')->name('admin.document_imports.destroy');
});
