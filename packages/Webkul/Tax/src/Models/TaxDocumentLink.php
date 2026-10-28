<?php

namespace Webkul\Tax\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Tax\Contracts\TaxDocumentLink as TaxDocumentLinkContract;

class TaxDocumentLink extends Model implements TaxDocumentLinkContract
{
    protected $table = 'tax_document_links';

    protected $guarded = ['*'];

    protected $casts = ['net_amount' => 'decimal:2', 'vat_amount' => 'decimal:2', 'gross_amount' => 'decimal:2', 'tax_breakdown' => 'array', 'reference_snapshot' => 'array'];

    public function document()
    {
        return $this->belongsTo(TaxDocumentProxy::modelClass(), 'tax_document_id');
    }

    public function referencedDocument()
    {
        return $this->belongsTo(TaxDocumentProxy::modelClass(), 'referenced_document_id');
    }

    protected static function booted(): void
    {
        static::saving(fn () => throw new \DomainException('This operation is unavailable in Sprint 1.'));
        static::deleting(fn () => throw new \DomainException('Financial records must be retained.'));
    }
}
