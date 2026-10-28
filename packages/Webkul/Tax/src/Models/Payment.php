<?php

namespace Webkul\Tax\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Contact\Models\OrganizationProxy;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Quote\Models\QuoteProxy;
use Webkul\Tax\Contracts\Payment as PaymentContract;

class Payment extends Model implements PaymentContract
{
    protected $table = 'payments';

    protected $guarded = ['*'];

    protected $casts = ['amount' => 'decimal:2', 'payer_snapshot' => 'array', 'received_at' => 'immutable_datetime', 'posted_at' => 'immutable_datetime'];

    public function seller()
    {
        return $this->belongsTo(TaxSellerProxy::modelClass(), 'seller_id');
    }

    public function quote()
    {
        return $this->belongsTo(QuoteProxy::modelClass());
    }

    public function person()
    {
        return $this->belongsTo(PersonProxy::modelClass());
    }

    public function organization()
    {
        return $this->belongsTo(OrganizationProxy::modelClass());
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocationProxy::modelClass());
    }

    public function originalPayment()
    {
        return $this->belongsTo(PaymentProxy::modelClass(), 'original_payment_id');
    }

    public function reversalOf()
    {
        return $this->belongsTo(PaymentProxy::modelClass(), 'reversal_of_id');
    }

    protected static function booted(): void
    {
        static::saving(fn () => throw new \DomainException('This operation is unavailable in Sprint 1.'));
        static::deleting(fn () => throw new \DomainException('Financial records must be retained.'));
    }
}
