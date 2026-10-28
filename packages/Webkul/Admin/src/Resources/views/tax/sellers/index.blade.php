<x-admin::layouts>
    <x-slot:title>@lang('admin::tax.sellers')</x-slot>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">@lang('admin::tax.sellers')</h1>
            @if (bouncer()->hasPermission('tax_settings.manage'))<a class="primary-button" href="{{ route('admin.tax_sellers.create') }}">@lang('admin::tax.create-seller')</a>@endif
        </div>
        @include('admin::tax.partials.notice')
        <div class="overflow-x-auto rounded border p-4">
            <table class="w-full text-start"><thead><tr>
                <th class="p-2 text-start">@lang('admin::tax.code')</th><th class="p-2 text-start">@lang('admin::tax.legal_name_ar')</th><th class="p-2 text-start">@lang('admin::tax.vat_number')</th><th class="p-2 text-start">@lang('admin::tax.status')</th><th></th>
            </tr></thead><tbody>
            @forelse ($sellers as $seller)
                <tr><td class="p-2">{{ $seller->code }}</td><td class="p-2">{{ $seller->legal_name_ar }}</td><td class="p-2">{{ $seller->vat_number }}</td><td class="p-2">{{ trans('admin::tax.'.($seller->is_active ? 'active' : 'inactive')) }}</td>
                    <td class="p-2">@if (bouncer()->hasPermission('tax_settings.manage'))<a href="{{ route('admin.tax_sellers.edit', $seller->id) }}">@lang('admin::tax.edit')</a>@endif</td></tr>
            @empty<tr><td colspan="5" class="p-4">@lang('admin::tax.no-results')</td></tr>@endforelse
            </tbody></table>
            {{ $sellers->links() }}
        </div>
    </div>
</x-admin::layouts>

