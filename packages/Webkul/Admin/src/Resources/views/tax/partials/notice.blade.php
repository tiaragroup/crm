<div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <p class="text-sm">@lang('admin::tax.draft-notice')</p>
</div>
@if ($errors->any())
    <div role="alert" class="rounded-lg border border-red-500 p-4">
        <p>@lang('admin::tax.errors')</p>
        <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </div>
@endif

