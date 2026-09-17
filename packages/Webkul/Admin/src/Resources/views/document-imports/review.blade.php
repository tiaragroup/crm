<x-admin::layouts>
    <x-slot:title>@lang('admin::app.document-imports.review-title')</x-slot>
    @php($isBulk = count($rows) > 1)
    <form method="POST" action="{{ route('admin.document_imports.update', $import->id) }}">
        @csrf @method('PUT')
        <input type="hidden" name="mode" value="{{ $isBulk ? 'bulk' : 'single' }}">
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                <div><h1 class="text-xl font-bold dark:text-white">@lang('admin::app.document-imports.review-title')</h1><p class="text-sm text-gray-500">{{ $import->original_filename }} · {{ strtoupper($import->extension) }} · {{ $import->user?->name }} · @lang('admin::app.document-imports.status.'.$import->status)</p></div>
                <div class="flex gap-2"><a class="secondary-button" href="{{ route('admin.document_imports.index') }}">@lang('admin::app.document-imports.actions.cancel')</a><a class="secondary-button" href="{{ route('admin.document_imports.download', $import->id) }}">@lang('admin::app.document-imports.actions.download')</a><button class="secondary-button" type="submit">@lang('admin::app.document-imports.actions.save')</button><button class="primary-button" type="submit" formaction="{{ route('admin.document_imports.import', $import->id) }}" formmethod="POST">@lang('admin::app.document-imports.actions.import')</button></div>
            </div>

            @if ($import->error_message)<div class="rounded border border-red-200 bg-red-50 p-3 text-red-700">{{ $import->error_message }}</div>@endif
            @if(data_get($import->extracted_data, '_document_metadata.warning'))<div class="rounded border border-amber-200 bg-amber-50 p-3 text-amber-800">{{ data_get($import->extracted_data, '_document_metadata.warning') }}</div>@endif
            @foreach ($rows as $index => $row)
                <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    @if($isBulk)<h2 class="mb-4 text-lg font-semibold">@lang('admin::app.document-imports.row') {{ $index + 1 }}</h2>@endif
                    @foreach (['company' => ['name','name_ar','email','phone','website','cr_number','vat_number','address'], 'contact' => ['name','job_title','email','phone'], 'event' => ['title','event_type','event_date','guest_count','venue','budget','requirements']] as $section => $fields)
                        <h3 class="mb-3 mt-4 font-semibold text-gray-800 dark:text-white">@lang('admin::app.document-imports.sections.'.$section)</h3>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @foreach($fields as $field)
                                <label class="text-sm text-gray-700 dark:text-gray-300"><span class="mb-1 block">@lang('admin::app.document-imports.fields.'.$field)</span>
                                    @if($field === 'requirements')<textarea class="w-full rounded border border-gray-200 p-2 dark:border-gray-800 dark:bg-gray-900" name="rows[{{ $index }}][{{ $section }}][{{ $field }}]">{{ old("rows.$index.$section.$field", data_get($row, "$section.$field")) }}</textarea>
                                    @else<input class="w-full rounded border border-gray-200 p-2 dark:border-gray-800 dark:bg-gray-900" name="rows[{{ $index }}][{{ $section }}][{{ $field }}]" value="{{ old("rows.$index.$section.$field", data_get($row, "$section.$field")) }}">@endif
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                    @if(!empty($row['confidence']))
                        <div class="mt-5">
                            <h3 class="font-semibold">@lang('admin::app.document-imports.confidence')</h3>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($row['confidence'] as $field => $score)
                                    @if(is_numeric($score))
                                        @php($level = $score >= .8 ? 'high' : ($score >= .5 ? 'medium' : 'low'))
                                        <span class="rounded border px-2 py-1 text-sm {{ $level === 'low' ? 'border-red-300 bg-red-50' : ($level === 'medium' ? 'border-amber-300 bg-amber-50' : 'border-green-300 bg-green-50') }}">
                                            {{ str($field)->replace('_', ' ')->title() }}: @lang('admin::app.document-imports.confidence-levels.'.$level) ({{ round($score * 100) }}%)
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @php($dupes = $duplicateRows[$index] ?? [])
                    @if(!empty($dupes['organizations']) || !empty($dupes['persons']))
                        <div class="mt-5 rounded border border-amber-200 bg-amber-50 p-4"><h3 class="font-semibold">@lang('admin::app.document-imports.possible-duplicate')</h3>
                            @if(!empty($dupes['organizations']))<label class="mt-2 block">@lang('admin::app.document-imports.use-existing-organization')<select class="mt-1 w-full rounded border p-2" name="rows[{{ $index }}][organization_id]"><option value="">@lang('admin::app.document-imports.create-new')</option>@foreach($dupes['organizations'] as $item)<option value="{{ $item['id'] }}">{{ $item['name'] }}</option>@endforeach</select></label>@endif
                            @if(!empty($dupes['persons']))<label class="mt-2 block">@lang('admin::app.document-imports.use-existing-contact')<select class="mt-1 w-full rounded border p-2" name="rows[{{ $index }}][person_id]"><option value="">@lang('admin::app.document-imports.create-new')</option>@foreach($dupes['persons'] as $item)<option value="{{ $item['id'] }}">{{ $item['name'] }}</option>@endforeach</select></label>@endif
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </form>
</x-admin::layouts>
