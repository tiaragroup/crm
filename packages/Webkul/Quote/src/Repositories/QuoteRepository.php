<?php

namespace Webkul\Quote\Repositories;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Repositories\AttributeValueRepository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Quote\Contracts\Quote;
use Webkul\Quote\Models\ProposalSetting;
use Webkul\Quote\Models\QuoteMenuSection;
use Webkul\Quote\Services\ProposalCalculator;
use Webkul\Quote\Services\ProposalSnapshot;

class QuoteRepository extends Repository
{
    /**
     * Searchable fields.
     */
    protected $fieldSearchable = [
        'subject',
        'description',
        'person_id',
        'person.name',
        'user_id',
        'user.name',
    ];

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeValueRepository $attributeValueRepository,
        protected QuoteItemRepository $quoteItemRepository,
        protected ProposalCalculator $proposalCalculator,
        protected ProposalSnapshot $proposalSnapshot,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     *
     * @return mixed
     */
    public function model()
    {
        return Quote::class;
    }

    /**
     * Create.
     *
     * @return Quote
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data = $this->prepareProposalData($data);
            $data = $this->proposalCalculator->calculate($data);

            $quote = parent::create($data);

            if (empty($quote->proposal_reference)) {
                $quote->proposal_reference = $this->generateReference($quote->id, $quote->issued_at);
                $quote->save();
            }

            $this->attributeValueRepository->save(array_merge($data, [
                'entity_id' => $quote->id,
            ]));

            foreach ($data['items'] ?? [] as $itemData) {
                $this->quoteItemRepository->create(array_merge($itemData, [
                    'quote_id' => $quote->id,
                ]));
            }

            $this->syncMenuSections($quote->id, $data['menu_sections'] ?? []);
            $this->refreshSnapshot($quote);

            return $quote->fresh();
        });
    }

    /**
     * Update.
     *
     * @param  int  $id
     * @param  array  $attribute
     * @return Quote
     */
    public function update(array $data, $id, $attributes = [])
    {
        return DB::transaction(function () use ($data, $id, $attributes) {
            $quote = $this->find($id);

            if (! empty($attributes)) {
                parent::update($data, $id);

                $conditions = ['entity_type' => $data['entity_type']];

                if (isset($data['quick_add'])) {
                    $conditions['quick_add'] = 1;
                }

                $attributeModels = $this->attributeRepository->where($conditions)
                    ->whereIn('code', $attributes)
                    ->get();

                $this->attributeValueRepository->save(array_merge($data, [
                    'entity_id' => $quote->id,
                ]), $attributeModels);

                return $quote->fresh();
            }

            $data = $this->prepareProposalData($data, $quote);
            $data = $this->proposalCalculator->calculate($data);

            parent::update($data, $id);

            $this->attributeValueRepository->save(array_merge($data, [
                'entity_id' => $quote->id,
            ]));

            $previousItemIds = $quote->items->pluck('id');

            if (isset($data['items'])) {
                foreach ($data['items'] as $itemId => $itemData) {
                    if (Str::contains((string) $itemId, 'item_') || ! is_numeric($itemId)) {
                        $this->quoteItemRepository->create(array_merge($itemData, [
                            'quote_id' => $id,
                        ]));
                    } else {
                        if (is_numeric($index = $previousItemIds->search($itemId))) {
                            $previousItemIds->forget($index);
                        }

                        $this->quoteItemRepository->update($itemData, $itemId);
                    }
                }
            }

            foreach ($previousItemIds as $itemId) {
                $this->quoteItemRepository->delete($itemId);
            }

            if (array_key_exists('menu_sections', $data)) {
                $this->syncMenuSections($quote->id, $data['menu_sections']);
            }

            $this->refreshSnapshot($quote->fresh());

            return $quote->fresh();
        });
    }

    protected function prepareProposalData(array $data, $quote = null): array
    {
        if (array_key_exists('event_at', $data)
            && (blank($data['event_at']) || str_starts_with((string) $data['event_at'], '0000-00-00'))
        ) {
            $data['event_at'] = null;
        }

        $settings = ProposalSetting::query()->first();
        $issuedAt = $data['issued_at'] ?? $quote?->issued_at ?? now()->toDateString();

        return array_merge([
            'status'                   => $quote?->status ?? 'draft',
            'revision'                 => $quote?->revision ?? 1,
            'issued_at'                => $issuedAt,
            'expired_at'               => $quote?->expired_at ?? now()->parse($issuedAt)->addDays($settings?->validity_days ?? 30),
            'vat_percent'              => $settings?->vat_percent ?? 15,
            'greeting'                 => $settings?->greeting_template,
            'pricing_terms'            => $settings?->pricing_terms,
            'payment_terms'            => $settings?->payment_terms,
            'changes_terms'            => $settings?->changes_terms,
            'cancellation_terms'       => $settings?->cancellation_terms,
            'bank_account_name'        => $settings?->bank_account_name,
            'bank_name'                => $settings?->bank_name,
            'iban'                     => $settings?->iban,
            'company_signatory_name'   => $settings?->signatory_name,
            'company_signatory_title'  => $settings?->signatory_title,
        ], $data);
    }

    protected function generateReference(int $quoteId, $issuedAt): string
    {
        $prefix = ProposalSetting::query()->value('reference_prefix') ?: 'TC';
        $date = Carbon::parse($issuedAt ?: now());

        return sprintf('%s-%s-%04d', $prefix, $date->format('ym'), $quoteId);
    }

    protected function syncMenuSections(int $quoteId, array $sections): void
    {
        QuoteMenuSection::query()->where('quote_id', $quoteId)->delete();

        foreach (array_values($sections) as $sectionSort => $sectionData) {
            if (empty($sectionData['name'])) {
                continue;
            }

            $section = QuoteMenuSection::query()->create([
                'quote_id'                   => $quoteId,
                'catering_menu_category_id'  => $sectionData['catering_menu_category_id'] ?? null,
                'name'                       => $sectionData['name'],
                'sort_order'                 => $sectionData['sort_order'] ?? $sectionSort + 1,
            ]);

            foreach (array_values($sectionData['items'] ?? []) as $itemSort => $itemData) {
                if (empty($itemData['name'])) {
                    continue;
                }

                $section->items()->create([
                    'product_id'  => $itemData['product_id'] ?? null,
                    'name'        => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'sort_order'  => $itemData['sort_order'] ?? $itemSort + 1,
                ]);
            }
        }
    }

    protected function refreshSnapshot($quote): void
    {
        $quote->document_snapshot = $this->proposalSnapshot->build($quote);
        $quote->saveQuietly();
    }

    /**
     * Retrieves customers count based on date.
     *
     * @return number
     */
    public function getQuotesCount($startDate, $endDate)
    {
        return $this
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->count();
    }
}
