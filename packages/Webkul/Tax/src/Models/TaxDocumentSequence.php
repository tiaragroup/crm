<?php

namespace Webkul\Tax\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Tax\Contracts\TaxDocumentSequence as TaxDocumentSequenceContract;

class TaxDocumentSequence extends Model implements TaxDocumentSequenceContract
{
    protected $table = 'tax_document_sequences';

    protected $guarded = ['*'];

    protected $casts = ['next_value' => 'integer'];

    public function seller()
    {
        return $this->belongsTo(TaxSellerProxy::modelClass(), 'seller_id');
    }

    protected static function booted(): void
    {
        static::saving(fn () => throw new \DomainException('This operation is unavailable in Sprint 1.'));
        static::deleting(fn () => throw new \DomainException('Financial records must be retained.'));
    }
}
