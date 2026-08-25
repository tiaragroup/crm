<?php

namespace Webkul\Quote\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\Product;

class QuoteMenuItem extends Model
{
    protected $table = 'quote_menu_items';

    protected $fillable = [
        'quote_menu_section_id',
        'product_id',
        'name',
        'description',
        'sort_order',
    ];

    public function section()
    {
        return $this->belongsTo(QuoteMenuSection::class, 'quote_menu_section_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
