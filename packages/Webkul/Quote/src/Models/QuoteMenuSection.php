<?php

namespace Webkul\Quote\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\CateringMenuCategory;

class QuoteMenuSection extends Model
{
    protected $table = 'quote_menu_sections';

    protected $fillable = [
        'quote_id',
        'catering_menu_category_id',
        'name',
        'sort_order',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function category()
    {
        return $this->belongsTo(CateringMenuCategory::class, 'catering_menu_category_id');
    }

    public function items()
    {
        return $this->hasMany(QuoteMenuItem::class, 'quote_menu_section_id')->orderBy('sort_order');
    }
}
