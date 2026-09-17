<x-admin::layouts>
    <x-slot:title>@lang('admin::app.document-imports.title')</x-slot>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 dark:border-gray-800 dark:bg-gray-900">
            <div><h1 class="text-xl font-bold dark:text-white">@lang('admin::app.document-imports.title')</h1></div>
            @if (bouncer()->hasPermission('document_imports.create'))
                <a class="primary-button" href="{{ route('admin.document_imports.create') }}">@lang('admin::app.document-imports.upload')</a>
            @endif
        </div>
        <x-admin::datagrid src="{{ route('admin.document_imports.get') }}" :isMultiRow="true" />
    </div>
</x-admin::layouts>
