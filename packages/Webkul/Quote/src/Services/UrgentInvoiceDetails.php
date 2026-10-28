<?php

namespace Webkul\Quote\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UrgentInvoiceDetails
{
    public function buyer(array $input, array $preview): array
    {
        $registered = filter_var($input['buyer_vat_registered'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $standard = ($input['invoice_type'] ?? '') === 'standard';
        $received = ($input['payment_status'] ?? '') === 'received';
        $rules = [
            'invoice_type' => ['required', 'in:standard,simplified'],
            'invoice_type_confirmed' => ['required', 'accepted'],
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_name_ar' => ['required', 'string', 'max:255', 'regex:/\p{Arabic}/u'],
            'buyer_vat_registered' => ['nullable', 'boolean'],
            'buyer_vat_number' => [Rule::requiredIf($registered), 'nullable', 'regex:/^3\d{13}3$/'],
            'buyer_address' => [Rule::requiredIf($standard), 'nullable', 'string', 'max:500'],
            'buyer_address_ar' => [Rule::requiredIf($standard), 'nullable', 'string', 'max:500', 'regex:/\p{Arabic}/u'],
            'buyer_city' => [Rule::requiredIf($standard), 'nullable', 'string', 'max:100'],
            'payment_method' => ['required', 'in:bank_transfer,cash,card'],
            'payment_status' => ['required', 'in:pending,received'],
            'transaction_reference' => [Rule::requiredIf($received), 'nullable', 'string', 'max:100'],
            'payment_confirmed' => $received ? ['required', 'accepted'] : ['exclude'],
            'payment_received_at' => [Rule::requiredIf($received), 'nullable', 'date_format:Y-m-d\TH:i'],
            'supply_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Riyadh')->toDateString()],
            // This limited workflow does not implement advances or prepaid adjustments.
            'supply_confirmed' => ['required', 'accepted'],
            'no_prior_invoice' => ['required', 'accepted'],
            'item_names_ar' => ['required', 'array', 'size:'.count($preview['items'])],
            'item_names_ar.*' => ['required', 'string', 'max:255', 'regex:/\p{Arabic}/u'],
        ];
        $data = Validator::make($input, $rules)->validate();
        abort_if($received && \Carbon\CarbonImmutable::parse($data['payment_received_at'], 'Asia/Riyadh')->isFuture(), 422, 'Receipt time cannot be in the future.');
        abort_if(! $standard && $registered, 422, 'This limited workflow supports simplified invoices only for non-VAT-registered consumers.');
        abort_unless(array_keys($data['item_names_ar']) === range(0, count($preview['items']) - 1), 422, 'Item translations must match the reviewed items.');
        abort_if(! $registered && filled($data['buyer_vat_number'] ?? null), 422, 'Confirm VAT registration when providing a VAT number.');
        if (! $received) {
            $data['transaction_reference'] = null;
            $data['payment_received_at'] = null;
            $data['payment_confirmed'] = false;
        }
        $data['buyer_vat_registered'] = $registered;

        return $data;
    }

    public function seller(): array
    {
        $config = config('urgent_invoice.seller', []);
        $attributes = collect(array_keys($config))->mapWithKeys(fn ($field) => ['seller.'.$field => 'seller setting TIARA_INVOICE_'.strtoupper($field)])->all();
        $seller = Validator::make(['seller' => $config], ['seller' => ['required', 'array']] + collect([
            'legal_name_ar' => ['required', 'string', 'max:120', 'regex:/\p{Arabic}/u'],
            'legal_name_en' => ['required', 'string', 'max:255'],
            'vat_number' => ['required', 'regex:/^3\d{13}3$/'],
            'cr_number' => ['required', 'string', 'max:50'],
            'street' => ['required', 'string', 'max:255'],
            'building' => ['required', 'regex:/^\d{4}$/'],
            'district' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'regex:/^\d{5}$/'],
            'country_code' => ['required', 'in:SA'],
            'address_ar' => ['required', 'string', 'max:500', 'regex:/\p{Arabic}/u'],
            'address_en' => ['required', 'string', 'max:500'],
        ])->mapWithKeys(fn ($rules, $field) => ['seller.'.$field => $rules])->all(), [], $attributes)->validate()['seller'];
        abort_if(strlen($seller['legal_name_ar']) > 255, 422, 'Seller Arabic name exceeds the QR byte limit.');

        return $seller;
    }

    public function fingerprint(array $preview): string
    {
        return hash('sha256', json_encode($preview, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
