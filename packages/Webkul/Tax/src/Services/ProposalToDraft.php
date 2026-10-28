<?php

namespace Webkul\Tax\Services;

use Brick\Math\BigDecimal;
use Illuminate\Support\Str;
use Webkul\Quote\Models\QuoteProxy;
use Webkul\User\Contracts\User;

class ProposalToDraft
{
    public function __construct(protected TaxAccess $access) {}

    public function source(int $id, User $actor, bool $lock = false): array
    {
        $this->access->authorize($actor, 'quotes');
        $query = $this->access->scope(QuoteProxy::query(), $actor, 'user_id');
        if ($lock) {
            $query->lockForUpdate();
        }
        $quote = $query->with('items')->findOrFail($id);
        abort_unless($quote->status === 'accepted', 422, trans('admin::tax.accepted-only'));
        $source = [
            'quote' => $quote->getAttributes(),
            'items' => $quote->items->map(fn ($item) => $item->getAttributes())->all(),
        ];

        return [$quote, $source, hash('sha256', json_encode($source, JSON_THROW_ON_ERROR))];
    }

    public function preview(int $id, User $actor): array
    {
        $this->access->authorize($actor, 'tax_documents.create');
        [$quote, $source, $fingerprint] = $this->source($id, $actor);
        $quote->loadMissing('person.organization');

        return [
            'uuid'               => (string) Str::uuid(), 'quote_id' => $id,
            'source_fingerprint' => $fingerprint,
            'source_reference'   => $quote->proposal_reference,
            'source_snapshot'    => $source,
            'person_id'          => $quote->person_id, 'organization_id' => $quote->person?->organization_id,
            'subject'            => $quote->subject, 'profile' => 'standard', 'purpose' => 'ordinary',
            'buyer'              => ['name_ar' => '', 'name_en' => $quote->client_company ?: $quote->attention_name, 'vat_number' => '', 'address' => []],
            // Included menus are descriptive; only commercial charge lines are copied.
            'items' => $quote->items->filter(fn ($item) => ! $item->is_included && $item->pricing_type !== 'included')
                ->map(fn ($item) => [
                    'source_quote_item_id' => $item->id, 'product_id' => $item->product_id,
                    'description_en'       => $item->name, 'description_ar' => '',
                    'quantity'             => (string) BigDecimal::of((string) $item->quantity)->toScale(6),
                    'unit_price'           => (string) BigDecimal::of((string) $item->price)->toScale(6),
                    'discount_amount'      => (string) BigDecimal::of((string) $item->discount_amount)->toScale(2),
                    'price_basis'          => 'exclusive', 'unit_code' => 'C62', 'tax_category' => null, 'tax_rate' => null,
                ])->values()->all(),
        ];
    }
}
