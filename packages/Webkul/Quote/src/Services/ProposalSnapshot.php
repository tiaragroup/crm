<?php

namespace Webkul\Quote\Services;

use Webkul\Quote\Models\ProposalSetting;
use Webkul\Quote\Models\Quote;

class ProposalSnapshot
{
    public function build(Quote $quote): array
    {
        $quote->loadMissing([
            'person.organization',
            'user',
            'items',
            'menuSections.items',
        ]);

        $settings = ProposalSetting::query()->first();

        return [
            'generated_at' => now()->toIso8601String(),
            'company'      => $settings?->toArray() ?? [],
            'sales_contact'=> [
                'name'  => $quote->user?->name,
                'email' => $quote->user?->email,
                'phone' => $quote->user?->phone,
            ],
            'proposal'     => [
                'id'                         => $quote->id,
                'reference'                  => $quote->proposal_reference,
                'status'                     => $quote->status,
                'revision'                   => $quote->revision,
                'subject'                    => $quote->subject,
                'description'                => $quote->description,
                'issued_at'                  => optional($quote->issued_at)->format('Y-m-d'),
                'valid_until'                => optional($quote->expired_at)->format('Y-m-d'),
                'client_company'             => $quote->client_company,
                'attention_name'             => $quote->attention_name,
                'client_mobile'              => $quote->client_mobile,
                'client_email'               => $quote->client_email,
                'greeting'                   => $quote->greeting,
                'event_type'                 => $quote->event_type,
                'event_at'                   => optional($quote->event_at)->format('Y-m-d H:i:s'),
                'venue'                      => $quote->venue,
                'setup_description'          => $quote->setup_description,
                'guest_count'                => $quote->guest_count,
                'service_inclusions'         => $this->lines($quote->service_inclusions),
                'vat_percent'                => (float) $quote->vat_percent,
                'pricing_terms'              => $quote->pricing_terms,
                'payment_terms'              => $quote->payment_terms,
                'changes_terms'              => $quote->changes_terms,
                'cancellation_terms'         => $quote->cancellation_terms,
                'bank_account_name'          => $quote->bank_account_name,
                'bank_name'                  => $quote->bank_name,
                'iban'                       => $quote->iban,
                'company_signatory_name'     => $quote->company_signatory_name,
                'company_signatory_title'    => $quote->company_signatory_title,
                'client_signatory_name'      => $quote->client_signatory_name,
                'client_signatory_title'     => $quote->client_signatory_title,
                'sub_total'                  => (float) $quote->sub_total,
                'discount_amount'            => (float) $quote->discount_amount,
                'tax_amount'                 => (float) $quote->tax_amount,
                'adjustment_amount'          => (float) $quote->adjustment_amount,
                'grand_total'                => (float) $quote->grand_total,
            ],
            'menu_sections' => $quote->menuSections->map(fn ($section) => [
                'name'       => $section->name,
                'sort_order' => $section->sort_order,
                'items'      => $section->items->map(fn ($item) => [
                    'name'        => $item->name,
                    'description' => $item->description,
                    'sort_order'  => $item->sort_order,
                ])->values()->all(),
            ])->values()->all(),
            'pricing_items' => $quote->items->map(fn ($item) => [
                'name'            => $item->name,
                'description'     => $item->description,
                'pricing_type'    => $item->pricing_type,
                'price'           => (float) $item->price,
                'quantity'        => $item->quantity,
                'guest_count'     => $item->guest_count,
                'is_included'     => (bool) $item->is_included,
                'discount_amount' => (float) $item->discount_amount,
                'total'           => (float) $item->total,
                'sort_order'      => $item->sort_order,
            ])->values()->all(),
        ];
    }

    private function lines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
