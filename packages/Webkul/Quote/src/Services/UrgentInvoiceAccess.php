<?php

namespace Webkul\Quote\Services;

use Webkul\Quote\Models\Quote;
use Webkul\Quote\Models\UrgentTaxInvoice;

class UrgentInvoiceAccess
{
    public function permission(string $permission): void
    {
        $user = auth()->guard('user')->user();
        abort_unless($user && $user->status && bouncer()->hasPermission($permission), 403);
    }

    public function owner(?int $owner): void
    {
        $ids = bouncer()->getAuthorizedUserIds();
        abort_unless($ids === null || ($owner !== null && in_array($owner, array_map('intval', $ids), true)), 403);
    }

    public function quote(int $id, string $permission = 'quotes.print', bool $lock = false): Quote
    {
        $this->permission($permission);
        $quote = Quote::query()->when($lock, fn ($query) => $query->lockForUpdate())->findOrFail($id);
        $this->owner($quote->user_id);

        return $quote;
    }

    public function invoice(UrgentTaxInvoice $invoice, string $permission = 'quotes.print'): void
    {
        $this->permission($permission);
        $this->owner($invoice->source_owner_user_id);
    }
}
