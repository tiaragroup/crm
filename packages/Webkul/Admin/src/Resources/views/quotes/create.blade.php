@php
    $quote = app('\Webkul\Quote\Repositories\QuoteRepository')->getModel();

    if (isset($lead)) {
        $quote->fill([
            'person_id'      => $lead->person_id,
            'user_id'        => $lead->user_id,
            'attention_name' => $lead->person?->name,
            'client_company' => $lead->person?->organization?->name,
            'client_email'   => data_get($lead->person?->emails, '0.value'),
            'client_mobile'  => data_get($lead->person?->contact_numbers, '0.value'),
            'subject'        => 'Catering Function Proposal',
        ]);
    }
@endphp

<x-admin::layouts>
    <x-slot:title>@lang('admin::app.quotes.form.create-title')</x-slot>

    <x-admin::form :action="route('admin.quotes.store').'?'.http_build_query(array_merge(request()->route()->parameters(), request()->all()))">
        @include('admin::quotes.form', ['isEdit' => false])
    </x-admin::form>
</x-admin::layouts>
