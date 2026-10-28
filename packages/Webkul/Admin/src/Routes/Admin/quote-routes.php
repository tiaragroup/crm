<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Quote\QuoteController;
use Webkul\Admin\Http\Controllers\Quote\UrgentInvoicePreviewController;

Route::controller(QuoteController::class)->prefix('quotes')->group(function () {
    Route::get('', 'index')->name('admin.quotes.index');

    Route::get('create/{lead_id?}', 'create')->name('admin.quotes.create');

    Route::post('create', 'store')->name('admin.quotes.store');

    Route::get('edit/{id?}', 'edit')->name('admin.quotes.edit');

    Route::put('edit/{id}', 'update')->name('admin.quotes.update');

    Route::get('print/{id?}', 'print')->name('admin.quotes.print');

    Route::get('word/{id}', 'word')->name('admin.quotes.word');

    Route::delete('{id}', 'destroy')->name('admin.quotes.delete');

    Route::get('search', 'search')->name('admin.quotes.search');

    Route::get('contacts/search', 'searchContacts')->name('admin.quotes.contacts.search');

    Route::post('mass-destroy', 'massDestroy')->name('admin.quotes.mass_delete');

    Route::get('urgent-invoice/{id}', [
        UrgentInvoicePreviewController::class,
        'show',
    ])->name('admin.quotes.urgent_invoice.preview');

    Route::post('urgent-invoice/{id}/review', [
        UrgentInvoicePreviewController::class,
        'review',
    ])->name('admin.quotes.urgent_invoice.review');

    Route::post('urgent-invoice/{id}/issue', [UrgentInvoicePreviewController::class, 'issue'])
        ->middleware('throttle:10,1')->name('admin.quotes.urgent_invoice.issue');

    Route::get('urgent-invoices/{uuid}/download', [UrgentInvoicePreviewController::class, 'download'])
        ->whereUuid('uuid')->name('admin.quotes.urgent_invoice.download');

});
