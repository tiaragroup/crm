<?php

namespace Webkul\Tax\Validation;

class SellerRules
{
    public static function rules(): array
    {
        return [
            'code'                      => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/'],
            'legal_name_ar'             => ['required', 'string', 'max:255', 'regex:/\p{Arabic}/u'],
            'legal_name_en'             => ['nullable', 'string', 'max:255'],
            'vat_number'                => ['required', 'regex:/^3\d{13}3$/'],
            'registration_scheme'       => ['nullable', 'required_with:registration_number', 'in:CRN,MOM,MLS,SAG,OTH'],
            'registration_number'       => ['nullable', 'required_with:registration_scheme', 'string', 'max:50'],
            'branch_name'               => ['nullable', 'string', 'max:255'],
            'address'                   => ['required', 'array:street,building_number,additional_number,district,city,postal_code,country_code'],
            'address.street'            => ['required', 'string', 'max:255'],
            'address.building_number'   => ['required', 'regex:/^\d{4}$/'],
            'address.additional_number' => ['nullable', 'regex:/^\d{4}$/'],
            'address.district'          => ['required', 'string', 'max:255'],
            'address.city'              => ['required', 'string', 'max:255'],
            'address.postal_code'       => ['required', 'regex:/^\d{5}$/'],
            'address.country_code'      => ['required', 'in:SA'],
            'is_active'                 => ['required', 'boolean'],
            'lock_version'              => ['nullable', 'integer', 'min:1'],
        ];
    }
}
