<?php

namespace Webkul\Quote\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Quote\Contracts\QuoteItem as QuoteItemContract;

class QuoteItem extends Model implements QuoteItemContract
{
    protected $table = 'quote_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sku',
        'name',
        'description',
        'pricing_type',
        'quantity',
        'guest_count',
        'is_included',
        'sort_order',
        'price',
        'coupon_code',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'total',
        'product_id',
        'quote_id',
    ];

    protected $casts = [
        'is_included' => 'boolean',
    ];

    /**
     * Get the quote record associated with the quote item.
     */
    public function quote()
    {
        return $this->belongsTo(QuoteProxy::modelClass());
    }
}
