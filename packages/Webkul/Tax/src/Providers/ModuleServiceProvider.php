<?php

namespace Webkul\Tax\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\Tax\Models\Payment;
use Webkul\Tax\Models\PaymentAllocation;
use Webkul\Tax\Models\TaxAuditEvent;
use Webkul\Tax\Models\TaxDocument;
use Webkul\Tax\Models\TaxDocumentArtifact;
use Webkul\Tax\Models\TaxDocumentItem;
use Webkul\Tax\Models\TaxDocumentLink;
use Webkul\Tax\Models\TaxDocumentSequence;
use Webkul\Tax\Models\TaxSeller;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        TaxSeller::class,
        TaxDocument::class,
        TaxDocumentItem::class,
        TaxDocumentSequence::class,
        Payment::class,
        PaymentAllocation::class,
        TaxDocumentLink::class,
        TaxDocumentArtifact::class,
        TaxAuditEvent::class,
    ];
}
