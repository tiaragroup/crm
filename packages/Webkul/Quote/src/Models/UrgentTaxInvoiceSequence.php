<?php

namespace Webkul\Quote\Models;

use Illuminate\Database\Eloquent\Model;

class UrgentTaxInvoiceSequence extends Model
{
    protected $table = 'urgent_tax_invoice_sequences';

    protected $guarded = ['*'];

    protected $casts = [
        'next_number' => 'integer',
    ];
}
