<?php

namespace Webkul\Tax\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Webkul\Contact\Models\OrganizationProxy;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Quote\Models\QuoteProxy;
use Webkul\Tax\Contracts\TaxDocument as TaxDocumentContract;
use Webkul\User\Models\UserProxy;

class TaxDocument extends Model implements TaxDocumentContract
{
    protected $table = 'tax_documents';

    protected $fillable = ['uuid', 'create_request_hash', 'seller_id', 'document_type', 'purpose', 'profile', 'status', 'lock_version', 'quote_id', 'person_id', 'organization_id', 'created_by', 'source_reference', 'source_revision', 'source_snapshot', 'source_fingerprint', 'subject', 'seller_snapshot', 'buyer_snapshot', 'currency_code', 'timezone', 'supply_date', 'due_date', 'notes'];

    protected $casts = ['seller_snapshot' => 'array', 'buyer_snapshot' => 'array', 'source_snapshot' => 'array', 'immutable_snapshot' => 'array', 'tax_breakdown' => 'array', 'supply_date' => 'date', 'due_date' => 'date', 'issued_at' => 'immutable_datetime', 'lock_version' => 'integer', 'line_net_total' => 'decimal:2', 'vat_total' => 'decimal:2', 'tax_inclusive_total' => 'decimal:2', 'payable_amount' => 'decimal:2'];

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

    public function creator()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'created_by');
    }

    public function items()
    {
        return $this->hasMany(TaxDocumentItemProxy::modelClass())->orderBy('line_number');
    }

    public function links()
    {
        return $this->hasMany(TaxDocumentLinkProxy::modelClass());
    }

    public function referencedBy()
    {
        return $this->hasMany(TaxDocumentLinkProxy::modelClass(), 'referenced_document_id');
    }

    public function artifacts()
    {
        return $this->hasMany(TaxDocumentArtifactProxy::modelClass());
    }

    public function events()
    {
        return $this->hasMany(TaxAuditEventProxy::modelClass());
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocationProxy::modelClass());
    }

    protected static function booted(): void
    {
        static::creating(function ($document) {
            $document->uuid ??= (string) Str::uuid();
        });
        static::saving(function ($document) {
            if (($document->exists && $document->getRawOriginal('status') !== 'draft')
                || $document->status !== 'draft'
                || $document->issued_at !== null || $document->document_number !== null
                || $document->sequence_id !== null || $document->sequence_number !== null
                || $document->immutable_snapshot !== null) {
                throw new \DomainException('Sprint 1 only permits unnumbered drafts.');
            }
        });
        static::deleting(fn () => throw new \DomainException('Tax documents must be retained.'));
    }
}
