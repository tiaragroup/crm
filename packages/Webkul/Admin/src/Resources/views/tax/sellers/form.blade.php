<x-admin::layouts>
    <x-slot:title>@lang('admin::tax.sellers')</x-slot>
    <div class="flex flex-col gap-4">
        <h1 class="text-xl font-bold">@lang('admin::tax.sellers')</h1>
        @include('admin::tax.partials.notice')
        <p>@lang('admin::tax.seller-notice')</p>
        <form method="POST" action="{{ $seller ? route('admin.tax_sellers.update', $seller->id) : route('admin.tax_sellers.store') }}" class="flex flex-col gap-4 rounded border p-4">
            @csrf
            @if ($seller) @method('PUT') <input type="hidden" name="lock_version" value="{{ old('lock_version', $seller->lock_version) }}"> @endif
            <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                @foreach (['code', 'legal_name_ar', 'legal_name_en', 'vat_number', 'registration_number', 'branch_name'] as $field)
                    @include('admin::tax.partials.field', ['name' => $field, 'value' => $seller?->{$field}, 'required' => in_array($field, ['code', 'legal_name_ar', 'vat_number'])])
                @endforeach
                @include('admin::tax.partials.field', ['name' => 'registration_scheme', 'value' => $seller?->registration_scheme, 'options' => ['' => trans('admin::tax.choose'), 'CRN' => 'CRN', 'MOM' => 'MOM', 'MLS' => 'MLS', 'SAG' => 'SAG', 'OTH' => 'OTH']])
                @foreach (['street', 'building_number', 'additional_number', 'district', 'city', 'postal_code'] as $field)
                    @include('admin::tax.partials.field', ['name' => 'address.'.$field, 'value' => data_get($seller?->address, $field), 'required' => $field !== 'additional_number'])
                @endforeach
                <input type="hidden" name="address[country_code]" value="SA">
                @include('admin::tax.partials.field', ['name' => 'is_active', 'value' => $seller?->is_active ?? 1, 'options' => [1 => trans('admin::tax.active'), 0 => trans('admin::tax.inactive')]])
            </div>
            <p>@lang('admin::tax.currency')</p>
            <div class="flex gap-4"><button class="primary-button" type="submit">@lang('admin::tax.save-settings')</button><a class="secondary-button" href="{{ route('admin.tax_sellers.index') }}">@lang('admin::tax.back')</a></div>
        </form>
    </div>
</x-admin::layouts>

