<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;

class CateringMenuCategory extends Model
{
    protected $table = 'catering_menu_categories';

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'description_ar',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'catering_menu_category_id');
    }
}
