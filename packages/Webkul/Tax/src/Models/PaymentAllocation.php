<?php

namespace Webkul\Tax\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Tax\Contracts\PaymentAllocation as PaymentAllocationContract;

class PaymentAllocation extends Model implements PaymentAllocationContract
{
    protected $table = 'payment_allocations';

    protected $guarded = ['*'];

    protected $casts = ['amount' => 'decimal:2'];

    public function payment()
    {
        return $this->belongsTo(PaymentProxy::modelClass());
    }

    public function document()
    {
        return $this->belongsTo(TaxDocumentProxy::modelClass(), 'tax_document_id');
    }

    public function reversalOf()
    {
        return $this->belongsTo(PaymentAllocationProxy::modelClass(), 'reversal_of_id');
    }

    protected static function booted(): void
    {
        static::saving(fn () => throw new \DomainException('This operation is unavailable in Sprint 1.'));
        static::deleting(fn () => throw new \DomainException('Financial records must be retained.'));
    }
}
