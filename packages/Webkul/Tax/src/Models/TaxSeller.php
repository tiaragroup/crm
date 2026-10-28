<?php

namespace Webkul\Tax\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Tax\Contracts\TaxSeller as TaxSellerContract;

class TaxSeller extends Model implements TaxSellerContract
{
    protected $table = 'tax_sellers';

    protected $fillable = ['code', 'legal_name_ar', 'legal_name_en', 'vat_number', 'registration_scheme', 'registration_number', 'branch_name', 'address', 'currency_code', 'timezone', 'lock_version', 'is_active', 'created_by'];

    protected $casts = ['address' => 'array', 'is_active' => 'boolean'];

    public function documents()
    {
        return $this->hasMany(TaxDocumentProxy::modelClass(), 'seller_id');
    }

    public function sequences()
    {
        return $this->hasMany(TaxDocumentSequenceProxy::modelClass(), 'seller_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentProxy::modelClass(), 'seller_id');
    }
}
