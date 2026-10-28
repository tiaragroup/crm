<?php

namespace Webkul\Tax\Validation;

class DraftRules
{
    public static function rules(): array
    {
        $decimal = ['string', 'regex:/^\d{1,12}(\.\d{1,6})?$/'];

        return [
            'uuid'                         => ['required', 'uuid'],
            'seller_id'                    => ['required', 'integer', 'exists:tax_sellers,id'],
            'quote_id'                     => ['nullable', 'integer', 'exists:quotes,id'],
            'person_id'                    => ['nullable', 'integer', 'exists:persons,id'],
            'organization_id'              => ['nullable', 'integer', 'exists:organizations,id'],
            'source_fingerprint'           => ['nullable', 'string', 'size:64'],
            'subject'                      => ['required', 'string', 'max:255'],
            'profile'                      => ['required', 'in:standard,simplified'],
            'purpose'                      => ['required', 'in:ordinary,advance,final'],
            'supply_date'                  => ['nullable', 'date_format:Y-m-d'],
            'due_date'                     => ['nullable', 'date_format:Y-m-d'],
            'notes'                        => ['nullable', 'string', 'max:5000'],
            'lock_version'                 => ['nullable', 'integer', 'min:1'],
            'buyer'                        => ['required', 'array:name_ar,name_en,vat_number,address'],
            'buyer.name_ar'                => ['nullable', 'required_without:buyer.name_en', 'string', 'max:255'],
            'buyer.name_en'                => ['nullable', 'required_without:buyer.name_ar', 'string', 'max:255'],
            'buyer.vat_number'             => ['nullable', 'regex:/^3\d{13}3$/'],
            'buyer.address'                => ['nullable', 'array:street,building_number,district,city,postal_code,country_code'],
            'buyer.address.*'              => ['nullable', 'string', 'max:255'],
            'items'                        => ['required', 'array', 'min:1', 'max:200'],
            'items.*'                      => ['array:description_ar,description_en,product_id,source_quote_item_id,quantity,unit_price,price_basis,unit_code,discount_amount,tax_category,tax_rate,exemption_code,exemption_reason'],
            'items.*.description_ar'       => ['nullable', 'required_without:items.*.description_en', 'string', 'max:255'],
            'items.*.description_en'       => ['nullable', 'required_without:items.*.description_ar', 'string', 'max:255'],
            'items.*.product_id'           => ['nullable', 'integer', 'exists:products,id'],
            'items.*.source_quote_item_id' => ['nullable', 'integer'],
            'items.*.quantity'             => ['required', ...$decimal, 'gt:0'],
            'items.*.unit_price'           => ['required', ...$decimal],
            'items.*.discount_amount'      => ['nullable', 'string', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'items.*.price_basis'          => ['required', 'in:exclusive,inclusive'],
            'items.*.unit_code'            => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'items.*.tax_category'         => ['nullable', 'in:S,Z,E,O'],
            'items.*.tax_rate'             => ['nullable', 'string', 'regex:/^\d{1,2}(\.\d{1,6})?$/'],
            'items.*.exemption_code'       => ['nullable', 'string', 'max:80'],
            'items.*.exemption_reason'     => ['nullable', 'string', 'max:255'],
        ];
    }
}
