<x-admin::layouts>
    <x-slot:title>@lang('admin::tax.create-draft')</x-slot>
    <div class="flex flex-col gap-4">
        <h1 class="text-xl font-bold">{{ trans('admin::tax.'.($id ? 'edit-draft' : 'create-draft')) }}</h1>
        @include('admin::tax.partials.notice')
        @if (! $id && $proposals->isNotEmpty())
            <form method="GET" action="{{ route('admin.tax_documents.create') }}" class="flex flex-wrap items-end gap-2">
                <label class="flex flex-col gap-1"><span>@lang('admin::tax.accepted-proposal')</span><select name="quote_id" required class="rounded border p-2"><option value="">@lang('admin::tax.choose')</option>@foreach ($proposals as $proposal)<option value="{{ $proposal->id }}">{{ $proposal->proposal_reference }} — {{ $proposal->subject }}</option>@endforeach</select></label>
                <button class="secondary-button">@lang('admin::tax.load-proposal')</button>
            </form>
        @endif
        @if ($sellers->isEmpty())<p role="alert">@lang('admin::tax.no-seller')</p>@endif
        <p>@lang('admin::tax.review-notice')</p>
        @if (! empty($form['quote_id']))
            <div class="rounded border p-4">
                <p>@lang('admin::tax.source-notice')</p>
                <dl class="mt-2 grid grid-cols-2 gap-2">
                    <dt>@lang('admin::tax.source_reference')</dt><dd>{{ $form['source_reference'] ?? '' }}</dd>
                    <dt>@lang('admin::tax.source-adjustment')</dt><dd>{{ data_get($form, 'source_snapshot.quote.adjustment_amount') }}</dd>
                    <dt>@lang('admin::tax.source-total')</dt><dd>{{ data_get($form, 'source_snapshot.quote.grand_total') }}</dd>
                </dl>
            </div>
        @endif
        <form method="POST" action="{{ $id ? route('admin.tax_documents.update', $id) : route('admin.tax_documents.store') }}" class="flex flex-col gap-4 rounded border p-4">
            @csrf
            @if ($id) @method('PUT') @endif
            @foreach (['uuid','quote_id','source_fingerprint','lock_version'] as $field)<input type="hidden" name="{{ $field }}" value="{{ old($field, $form[$field] ?? '') }}">@endforeach
            <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                @include('admin::tax.partials.field', ['name' => 'subject', 'value' => $form['subject'] ?? '', 'required' => true])
                @include('admin::tax.partials.field', ['name' => 'seller_id', 'value' => $form['seller_id'] ?? '', 'required' => true, 'options' => ['' => trans('admin::tax.choose')] + $sellers->pluck('legal_name_ar', 'id')->all()])
                @include('admin::tax.partials.field', ['name' => 'profile', 'value' => $form['profile'], 'options' => ['standard' => trans('admin::tax.standard'), 'simplified' => trans('admin::tax.simplified')]])
                @include('admin::tax.partials.field', ['name' => 'purpose', 'value' => $form['purpose'], 'options' => collect(['ordinary','advance','final'])->mapWithKeys(fn ($value) => [$value => trans('admin::tax.'.$value)])->all()])
                @foreach (['supply_date','due_date'] as $field)@include('admin::tax.partials.field', ['name' => $field, 'type' => 'date', 'value' => $form[$field] ?? ''])@endforeach
            </div>
            <h2 class="text-lg font-bold">@lang('admin::tax.buyer')</h2>
            <p class="text-sm">@lang('admin::tax.contact-help')</p>
            <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                @foreach (['person_id','organization_id'] as $field)@include('admin::tax.partials.field', ['name' => $field, 'value' => $form[$field] ?? ''])@endforeach
                @foreach (['name_ar','name_en','vat_number'] as $field)@include('admin::tax.partials.field', ['name' => 'buyer.'.$field, 'value' => data_get($form, 'buyer.'.$field)])@endforeach
                @foreach (['street','building_number','district','city','postal_code','country_code'] as $field)@include('admin::tax.partials.field', ['name' => 'buyer.address.'.$field, 'value' => data_get($form, 'buyer.address.'.$field)])@endforeach
            </div>
            <h2 class="text-lg font-bold">@lang('admin::tax.items')</h2>
            <v-tax-draft-items :initial='@json(old("items", $form["items"]))'></v-tax-draft-items>
            @include('admin::tax.partials.field', ['name' => 'notes', 'value' => $form['notes'] ?? '', 'maxlength' => 5000])
            <div class="flex gap-4"><button class="primary-button" type="submit" @disabled($sellers->isEmpty())>@lang('admin::tax.save')</button><a class="secondary-button" href="{{ route('admin.tax_documents.index') }}">@lang('admin::tax.back')</a></div>
        </form>
    </div>
    @include('admin::tax.documents.items')
</x-admin::layouts>

