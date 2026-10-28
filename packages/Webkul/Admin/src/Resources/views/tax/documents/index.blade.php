<x-admin::layouts>
    <x-slot:title>@lang('admin::tax.documents')</x-slot>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">@lang('admin::tax.documents')</h1>
            @if (bouncer()->hasPermission('tax_documents.create'))<a class="primary-button" href="{{ route('admin.tax_documents.create') }}">@lang('admin::tax.create-draft')</a>@endif
        </div>
        @include('admin::tax.partials.notice')
        <form method="GET" class="flex gap-2"><label><span class="sr-only">@lang('admin::tax.search')</span><input name="q" value="{{ request('q') }}" placeholder="{{ trans('admin::tax.search') }}" class="rounded border p-2"></label><button class="secondary-button">@lang('admin::tax.search')</button></form>
        <div class="overflow-x-auto rounded border p-4">
            <table class="w-full"><thead><tr>
                @foreach (['subject','seller_id','profile','purpose','status','created'] as $label)<th class="p-2 text-start">@lang('admin::tax.'.$label)</th>@endforeach<th></th>
            </tr></thead><tbody>
            @forelse ($documents as $document)
                <tr><td class="p-2">{{ $document->subject }}</td><td class="p-2">{{ $document->seller->legal_name_ar }}</td><td class="p-2">@lang('admin::tax.'.$document->profile)</td><td class="p-2">@lang('admin::tax.'.$document->purpose)</td><td class="p-2">@lang('admin::tax.'.$document->status)</td><td class="p-2">{{ $document->created_at->timezone('Asia/Riyadh')->format('Y-m-d H:i') }}</td>
                <td class="p-2">@if ($document->status === 'draft' && bouncer()->hasPermission('tax_documents.edit'))<a href="{{ route('admin.tax_documents.edit', $document->id) }}">@lang('admin::tax.edit')</a>@endif</td></tr>
            @empty<tr><td colspan="7" class="p-4">@lang('admin::tax.no-results')</td></tr>@endforelse
            </tbody></table>
            {{ $documents->links() }}
        </div>
    </div>
</x-admin::layouts>

