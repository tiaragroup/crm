<?php

namespace Webkul\Tax\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Tax\Contracts\TaxAuditEvent as TaxAuditEventContract;
use Webkul\User\Models\UserProxy;

class TaxAuditEvent extends Model implements TaxAuditEventContract
{
    protected $table = 'tax_audit_events';

    protected $fillable = ['seller_id', 'tax_document_id', 'payment_id', 'actor_id', 'event', 'payload'];

    protected $casts = ['payload' => 'array'];

    public function document()
    {
        return $this->belongsTo(TaxDocumentProxy::modelClass(), 'tax_document_id');
    }

    public function seller()
    {
        return $this->belongsTo(TaxSellerProxy::modelClass(), 'seller_id');
    }

    public function actor()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'actor_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \DomainException('Audit events are append-only.'));
        static::deleting(fn () => throw new \DomainException('Audit events are append-only.'));
    }
}
