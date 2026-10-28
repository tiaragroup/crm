<?php

namespace Webkul\Quote\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\User;

class UrgentTaxInvoice extends Model
{
    protected $table = 'urgent_tax_invoices';

    // Invoice creation will be controlled by a dedicated service.
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(function (self $invoice) {
            $allowed = ['quote_id', 'issued_by_user_id', 'updated_at'];
            if ($invoice->getOriginal('archive_status') !== 'ready') {
                $allowed = array_merge($allowed, ['archive_status', 'pdf_path', 'pdf_sha256', 'archived_at']);
            }
            if (array_diff(array_keys($invoice->getDirty()), $allowed)) {
                throw new \DomainException('Issued invoice content is immutable.');
            }
        });
        static::deleting(fn () => throw new \DomainException('Issued invoices must be retained.'));
    }

    public function contentHash(): string
    {
        $data = [];
        foreach (['uuid', 'series', 'sequence_number', 'invoice_number', 'source_quote_id', 'source_quote_reference', 'source_quote_revision', 'source_owner_user_id', 'invoice_type', 'issued_at', 'currency_code', 'snapshot_version', 'seller_snapshot', 'buyer_snapshot', 'items_snapshot', 'totals_snapshot', 'qr_payload', 'issued_by_name'] as $key) {
            $data[$key] = $key === 'issued_at' ? $this->getAttributes()[$key] : $this->getAttribute($key);
        }
        // MySQL JSON may reorder object keys. Canonicalize recursively before hashing.
        $canonicalize = function ($value) use (&$canonicalize) {
            if (is_array($value)) {
                if (! array_is_list($value)) {
                    ksort($value);
                }

                return array_map($canonicalize, $value);
            }

            return $value;
        };

        return hash('sha256', json_encode($canonicalize($data), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    protected $casts = [
        'sequence_number'       => 'integer',
        'source_quote_id'       => 'integer',
        'source_quote_revision' => 'integer',
        'source_owner_user_id'  => 'integer',
        'snapshot_version'      => 'integer',
        'issued_at'             => 'datetime',
        'archived_at'           => 'datetime',
        'seller_snapshot'       => 'array',
        'buyer_snapshot'        => 'array',
        'items_snapshot'        => 'array',
        'totals_snapshot'       => 'array',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }
}
