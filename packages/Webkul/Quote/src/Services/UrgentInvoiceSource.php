<?php

namespace Webkul\Quote\Services;

use DomainException;
use Webkul\Quote\Models\Quote;

class UrgentInvoiceSource
{
    public function preview(int $quoteId, bool $allowUnaccepted = false, bool $lock = false): array
    {
        $quote = Quote::query()
            ->with(['person.organization'])
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->findOrFail($quoteId);

        if (! $allowUnaccepted && $quote->status !== 'accepted') {
            throw new DomainException(
                'Only accepted proposals can be invoiced.'
            );
        }

        $items = $quote->items()->orderBy('id')->when($lock, fn ($query) => $query->lockForUpdate())->get()
            ->filter(fn ($item) => ! $item->is_included
                && $item->pricing_type !== 'included'
            )
            ->map(fn ($item) => [
                'id'              => $item->id,
                'name'            => $item->name,
                'description'     => $item->description,
                'pricing_type'    => $item->pricing_type,
                'quantity'        => $item->quantity,
                'guest_count'     => $item->guest_count,
                'unit_price'      => (string) $item->getRawOriginal('price'),
                'discount_amount' => (string) $item->getRawOriginal('discount_amount'),
                'line_total'      => (string) $item->getRawOriginal('total'),
            ])
            ->values()
            ->all();

        if (empty($items) && ! $allowUnaccepted) {
            throw new DomainException(
                'The proposal has no chargeable items.'
            );
        }

        return [
            'source' => [
                'quote_id'   => $quote->id,
                'reference'  => $quote->proposal_reference,
                'revision'   => $quote->revision,
                'status'     => $quote->status,
            ],

            'seller' => config('urgent_invoice.seller'),

            'buyer' => [
                'company_name' => $quote->client_company
                    ?: $quote->person?->organization?->name,
                'contact_name' => $quote->attention_name
                    ?: $quote->person?->name,
                'email'  => $quote->client_email,
                'mobile' => $quote->client_mobile,
            ],

            'event' => [
                'subject'     => $quote->subject,
                'event_type'  => $quote->event_type,
                'event_date'  => $quote->event_at?->toDateString(),
                'venue'       => $quote->venue,
                'guest_count' => $quote->guest_count,
            ],

            'items' => $items,

            // Existing proposal values, not yet validated
            // as statutory invoice totals.
            'proposal_totals' => [
                'subtotal'   => (string) $quote->getRawOriginal('sub_total'),
                'discount'   => (string) $quote->getRawOriginal('discount_amount'),
                'adjustment' => (string) $quote->getRawOriginal('adjustment_amount'),
                'vat_rate'   => (string) $quote->getRawOriginal('vat_percent'),
                'vat_amount' => (string) $quote->getRawOriginal('tax_amount'),
                'grand_total'=> (string) $quote->getRawOriginal('grand_total'),
            ],
        ];
    }
}
