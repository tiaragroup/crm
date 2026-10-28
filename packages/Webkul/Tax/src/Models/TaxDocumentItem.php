<?php

namespace Webkul\Tax\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Tax\Contracts\TaxDocumentItem as TaxDocumentItemContract;

class TaxDocumentItem extends Model implements TaxDocumentItemContract
{
    protected $table = 'tax_document_items';

    protected $fillable = ['tax_document_id', 'line_number', 'product_id', 'source_quote_item_id', 'description_ar', 'description_en', 'sku', 'unit_code', 'quantity', 'unit_price', 'price_basis', 'discount_amount', 'tax_category', 'tax_rate', 'exemption_code', 'exemption_reason', 'source_metadata'];

    protected $casts = ['quantity' => 'decimal:6', 'unit_price' => 'decimal:6', 'discount_amount' => 'decimal:2', 'tax_rate' => 'decimal:6', 'source_metadata' => 'array', 'net_amount' => 'decimal:2', 'vat_amount' => 'decimal:2'];

    public function document()
    {
        return $this->belongsTo(TaxDocumentProxy::modelClass(), 'tax_document_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    protected static function booted(): void
    {
        $guard = function ($item) {
            $ids = array_filter([$item->tax_document_id, $item->getRawOriginal('tax_document_id')]);
            foreach (array_unique($ids) as $id) {
                if (TaxDocumentProxy::query()->whereKey($id)->value('status') !== 'draft') {
                    throw new \DomainException('Only draft items can change.');
                }
            }
        };
        static::saving($guard);
        static::deleting($guard);
    }
}
