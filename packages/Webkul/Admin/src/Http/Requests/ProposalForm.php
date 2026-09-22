<?php

namespace Webkul\Admin\Http\Requests;

use Webkul\Contact\Models\Person;

class ProposalForm extends AttributeForm
{
    /**
     * Catalog products are optional for custom proposal and menu items.
     * Legacy quote rows used 0 to represent "no product", which must be
     * normalized before the exists validation rule is evaluated.
     */
    protected function prepareForValidation(): void
    {
        $items = collect((array) $this->input('items', []))
            ->map(function ($item) {
                $item = (array) $item;
                $item['product_id'] = $this->normalizeOptionalId($item['product_id'] ?? null);

                return $item;
            })
            ->all();

        $menuSections = collect((array) $this->input('menu_sections', []))
            ->map(function ($section) {
                $section = (array) $section;
                $section['catering_menu_category_id'] = $this->normalizeOptionalId(
                    $section['catering_menu_category_id'] ?? null
                );
                $section['items'] = collect((array) ($section['items'] ?? []))
                    ->map(function ($item) {
                        $item = (array) $item;
                        $item['product_id'] = $this->normalizeOptionalId($item['product_id'] ?? null);

                        return $item;
                    })
                    ->all();

                return $section;
            })
            ->all();

        $contact = Person::query()->find($this->input('person_id'));

        $contactData = $contact ? [
            'client_company' => $contact->organization?->name,
            'attention_name' => $contact->name,
            'client_mobile'  => data_get($contact->contact_numbers, '0.value'),
            'client_email'   => data_get($contact->emails, '0.value'),
        ] : [];

        $this->merge(array_merge([
            'items'         => $items,
            'menu_sections' => $menuSections,
        ], $contactData));
    }

    protected function normalizeOptionalId($value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    public function rules()
    {
        return array_merge(parent::rules(), [
            'proposal_reference'                       => ['nullable', 'string', 'max:255', 'unique:quotes,proposal_reference,'.$this->route('id')],
            'status'                                   => ['required', 'in:draft,sent,accepted,rejected,expired'],
            'issued_at'                                => ['required', 'date', 'after_or_equal:today'],
            'expired_at'                               => ['required', 'date', 'after_or_equal:issued_at'],
            'client_company'                           => ['nullable', 'string', 'max:255'],
            'attention_name'                           => ['nullable', 'string', 'max:255'],
            'client_mobile'                            => ['nullable', 'string', 'max:50'],
            'client_email'                             => ['nullable', 'email', 'max:255'],
            'event_type'                               => ['nullable', 'string', 'max:255'],
            'event_at'                                 => ['nullable', 'date', 'after_or_equal:now'],
            'venue'                                    => ['nullable', 'string', 'max:255'],
            'guest_count'                              => ['required', 'integer', 'min:1'],
            'vat_percent'                              => ['required', 'numeric', 'min:0', 'max:100'],
            'items'                                    => ['required', 'array', 'min:1'],
            'items.*.product_id'                       => ['nullable', 'integer', 'exists:products,id'],
            'items.*.name'                             => ['required_without:items.*.product_id', 'nullable', 'string', 'max:255'],
            'items.*.description'                      => ['nullable', 'string'],
            'items.*.pricing_type'                     => ['required', 'in:per_person,fixed,included'],
            'items.*.price'                            => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity'                         => ['nullable', 'integer', 'min:1'],
            'items.*.guest_count'                      => ['nullable', 'integer', 'min:1'],
            'items.*.discount_amount'                  => ['nullable', 'numeric', 'min:0'],
            'menu_sections'                            => ['nullable', 'array'],
            'menu_sections.*.name'                     => ['required', 'string', 'max:255'],
            'menu_sections.*.catering_menu_category_id'=> ['nullable', 'integer', 'exists:catering_menu_categories,id'],
            'menu_sections.*.items'                    => ['nullable', 'array'],
            'menu_sections.*.items.*.product_id'       => ['nullable', 'integer', 'exists:products,id'],
            'menu_sections.*.items.*.name'             => ['required', 'string', 'max:255'],
        ]);
    }
}
