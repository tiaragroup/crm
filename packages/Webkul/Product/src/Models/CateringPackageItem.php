<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;

class CateringPackageItem extends Model
{
    protected $table = 'catering_package_items';

    protected $fillable = [
        'catering_package_id',
        'product_id',
        'sort_order',
    ];

    public function package()
    {
        return $this->belongsTo(CateringPackage::class, 'catering_package_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
