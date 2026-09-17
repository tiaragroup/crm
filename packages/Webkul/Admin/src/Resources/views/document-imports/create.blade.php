<x-admin::layouts>
    <x-slot:title>@lang('admin::app.document-imports.upload')</x-slot>
    <form method="POST" action="{{ route('admin.document_imports.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                <h1 class="text-xl font-bold dark:text-white">@lang('admin::app.document-imports.upload')</h1>
                <button class="primary-button" type="submit">@lang('admin::app.document-imports.actions.process')</button>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">@lang('admin::app.document-imports.file')</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="file" name="document" rules="required" accept=".pdf,.docx,.xlsx,.xls,.csv" />
                    <x-admin::form.control-group.error control-name="document" />
                </x-admin::form.control-group>
                <p class="mt-2 text-sm text-gray-500">@lang('admin::app.document-imports.file-help', ['size' => round(config('document_imports.max_upload_kb') / 1024)])</p>
            </div>
        </div>
    </form>
</x-admin::layouts>
