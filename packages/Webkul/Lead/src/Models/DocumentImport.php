<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Contact\Models\OrganizationProxy;
use Webkul\Contact\Models\PersonProxy;
use Webkul\User\Models\UserProxy;

class DocumentImport extends Model
{
    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_EXTRACTING = 'extracting';

    public const STATUS_EXTRACTED = 'extracted';

    public const STATUS_REVIEW = 'review';

    public const STATUS_IMPORTED = 'imported';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id', 'original_filename', 'stored_path', 'mime_type', 'extension',
        'file_size', 'document_type', 'status', 'raw_text', 'extracted_data',
        'reviewed_data', 'ai_model', 'ai_provider', 'error_message',
        'organization_id', 'person_id', 'lead_id', 'imported_at',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'reviewed_data'  => 'array',
        'imported_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass());
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationProxy::modelClass());
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass());
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass());
    }
}
