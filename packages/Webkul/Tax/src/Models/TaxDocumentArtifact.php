<?php

namespace Webkul\Tax\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Tax\Contracts\TaxDocumentArtifact as TaxDocumentArtifactContract;

class TaxDocumentArtifact extends Model implements TaxDocumentArtifactContract
{
    protected $table = 'tax_document_artifacts';

    protected $guarded = ['*'];

    protected $casts = ['version' => 'integer', 'byte_size' => 'integer'];

    public function document()
    {
        return $this->belongsTo(TaxDocumentProxy::modelClass(), 'tax_document_id');
    }

    protected static function booted(): void
    {
        static::saving(fn () => throw new \DomainException('This operation is unavailable in Sprint 1.'));
        static::deleting(fn () => throw new \DomainException('Financial records must be retained.'));
    }
}
