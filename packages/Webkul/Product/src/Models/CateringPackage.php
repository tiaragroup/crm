<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;

class CateringPackage extends Model
{
    protected $table = 'catering_packages';

    protected $fillable = [
        'name',
        'description',
        'setup_description',
        'service_inclusions',
        'price_per_person',
        'minimum_guests',
        'is_active',
    ];

    protected $casts = [
        'price_per_person' => 'decimal:4',
        'is_active'        => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(CateringPackageItem::class, 'catering_package_id')->orderBy('sort_order');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'catering_package_items', 'catering_package_id', 'product_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
