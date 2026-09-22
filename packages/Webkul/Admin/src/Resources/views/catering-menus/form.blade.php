@php
    $isEdit = $menu->exists;
    $selected = collect($selectedProductIds)->map(fn ($id) => (int) $id)->all();
    $inputClass = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 outline-none transition focus:border-brandColor dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300';
@endphp

<x-admin::layouts>
    <x-slot:title>{{ $isEdit ? trans('admin::app.catering-menus.edit-title') : trans('admin::app.catering-menus.create-title') }}</x-slot>

    <form method="POST" action="{{ $isEdit ? route('admin.catering.menus.update', $menu) : route('admin.catering.menus.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                <div><p class="text-sm text-gray-500"><a href="{{ route('admin.catering.menus.index') }}" class="hover:text-brandColor">@lang('admin::app.catering-menus.title')</a> / {{ $isEdit ? trans('admin::app.catering-menus.edit') : trans('admin::app.catering-menus.create') }}</p><h1 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">{{ $isEdit ? trans('admin::app.catering-menus.edit-title') : trans('admin::app.catering-menus.create-title') }}</h1></div>
                <div class="flex gap-2"><a href="{{ route('admin.catering.menus.index') }}" class="secondary-button">@lang('admin::app.catering-menus.cancel')</a><button type="submit" class="primary-button">{{ $isEdit ? trans('admin::app.catering-menus.save-changes') : trans('admin::app.catering-menus.create') }}</button></div>
            </div>

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><p class="font-semibold">@lang('admin::app.catering-menus.validation-title')</p><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <div class="grid grid-cols-[minmax(0,1fr)_340px] gap-4 max-xl:grid-cols-1">
                <div class="flex flex-col gap-4">
                    <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                        <header class="mb-4 border-b border-gray-200 pb-3 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white">@lang('admin::app.catering-menus.details')</h2><p class="text-xs text-gray-500">@lang('admin::app.catering-menus.details-description')</p></header>
                        <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                            <div><label class="mb-1.5 block text-sm font-medium">@lang('admin::app.catering-menus.name-en') *</label><input name="name" required value="{{ old('name', $menu->name) }}" placeholder="@lang('admin::app.catering-menus.name-placeholder')" class="{{ $inputClass }}"></div>
                            <div><label class="mb-1.5 block text-sm font-medium">@lang('admin::app.catering-menus.name-ar')</label><input dir="rtl" lang="ar" name="name_ar" value="{{ old('name_ar', $menu->name_ar) }}" placeholder="@lang('admin::app.catering-menus.name-placeholder')" class="{{ $inputClass }}"></div>
                            <div><label class="mb-1.5 block text-sm font-medium">@lang('admin::app.catering-menus.client-description-en')</label><textarea name="description" rows="3" placeholder="@lang('admin::app.catering-menus.description-placeholder')" class="{{ $inputClass }}">{{ old('description', $menu->description) }}</textarea></div>
                            <div><label class="mb-1.5 block text-sm font-medium">@lang('admin::app.catering-menus.client-description-ar')</label><textarea dir="rtl" lang="ar" name="description_ar" rows="3" placeholder="@lang('admin::app.catering-menus.description-placeholder')" class="{{ $inputClass }}">{{ old('description_ar', $menu->description_ar) }}</textarea></div>
                            <div><label class="mb-1.5 block text-sm font-medium">@lang('admin::app.catering-menus.price-per-person-sar') *</label><input type="number" name="price_per_person" required min="0" step="0.01" value="{{ old('price_per_person', $menu->price_per_person ?? 0) }}" class="{{ $inputClass }}"></div>
                            <div><label class="mb-1.5 block text-sm font-medium">@lang('admin::app.catering-menus.minimum-guests')</label><input type="number" name="minimum_guests" min="1" value="{{ old('minimum_guests', $menu->minimum_guests) }}" placeholder="@lang('admin::app.catering-menus.optional')" class="{{ $inputClass }}"></div>
                            <div><label class="mb-1.5 block text-sm font-medium">@lang('admin::app.catering-menus.setup-description-en')</label><textarea name="setup_description" rows="3" placeholder="@lang('admin::app.catering-menus.setup-placeholder')" class="{{ $inputClass }}">{{ old('setup_description', $menu->setup_description) }}</textarea></div>
                            <div><label class="mb-1.5 block text-sm font-medium">@lang('admin::app.catering-menus.setup-description-ar')</label><textarea dir="rtl" lang="ar" name="setup_description_ar" rows="3" placeholder="@lang('admin::app.catering-menus.setup-placeholder')" class="{{ $inputClass }}">{{ old('setup_description_ar', $menu->setup_description_ar) }}</textarea></div>
                            <div><label class="mb-1.5 block text-sm font-medium">@lang('admin::app.catering-menus.service-inclusions-en') <span class="font-normal text-gray-500">(@lang('admin::app.catering-menus.one-per-line'))</span></label><textarea name="service_inclusions" rows="4" placeholder="{{ trans('admin::app.catering-menus.service-placeholder') }}" class="{{ $inputClass }}">{{ old('service_inclusions', $menu->service_inclusions) }}</textarea></div>
                            <div><label class="mb-1.5 block text-sm font-medium">@lang('admin::app.catering-menus.service-inclusions-ar')</label><textarea dir="rtl" lang="ar" name="service_inclusions_ar" rows="4" placeholder="{{ trans('admin::app.catering-menus.service-placeholder') }}" class="{{ $inputClass }}">{{ old('service_inclusions_ar', $menu->service_inclusions_ar) }}</textarea></div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                        <header class="mb-4 border-b border-gray-200 pb-3 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white">@lang('admin::app.catering-menus.choose-dishes')</h2><p class="text-xs text-gray-500">@lang('admin::app.catering-menus.choose-dishes-description')</p></header>
                        <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">
                            @foreach ($categories as $category)
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                                    <div class="mb-3 flex items-center justify-between border-b border-gray-100 pb-2 dark:border-gray-800"><h3 class="font-semibold text-gray-800 dark:text-white">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} · {{ app()->getLocale() === 'ar' && $category->name_ar ? $category->name_ar : $category->name }} @if (app()->getLocale() !== 'ar' && $category->name_ar)<span class="ml-2 font-normal text-gray-500" dir="rtl" lang="ar">{{ $category->name_ar }}</span>@endif</h3><span class="text-xs text-gray-500">@lang('admin::app.catering-menus.available-count', ['count' => $category->products->count()])</span></div>
                                    <div class="flex flex-col gap-2">
                                        @forelse ($category->products as $product)
                                            <label class="flex cursor-pointer items-start gap-3 rounded-md p-2 hover:bg-gray-50 dark:hover:bg-gray-950"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array((int) $product->id, $selected, true)) class="mt-1 h-4 w-4 accent-brandColor"><span><span class="block text-sm font-medium text-gray-800 dark:text-white">{{ app()->isLocale('ar') && $product->name_ar ? $product->name_ar : $product->name }}</span>@if (! app()->isLocale('ar') && $product->name_ar)<span class="mt-0.5 block text-sm text-gray-600" dir="rtl" lang="ar">{{ $product->name_ar }}</span>@endif @if ((app()->isLocale('ar') ? $product->description_ar : $product->description) ?: $product->description)<span class="mt-0.5 block text-xs text-gray-500">{{ (app()->isLocale('ar') ? $product->description_ar : $product->description) ?: $product->description }}</span>@endif</span></label>
                                        @empty
                                            <p class="py-3 text-sm text-gray-500">@lang('admin::app.catering-menus.no-active-dishes')</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 text-xs text-gray-500">@lang('admin::app.catering-menus.need-dish')</p>
                    </section>
                </div>

                <aside class="h-fit rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 xl:sticky xl:top-4">
                    <h2 class="font-semibold text-gray-800 dark:text-white">@lang('admin::app.catering-menus.availability')</h2>
                    <p class="mt-1 text-xs text-gray-500">@lang('admin::app.catering-menus.availability-description')</p>
                    <input type="hidden" name="is_active" value="0">
                    <label class="mt-4 flex cursor-pointer items-center gap-3 rounded-md border border-gray-200 p-4 dark:border-gray-800"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $menu->exists ? $menu->is_active : true)) class="h-4 w-4 accent-brandColor"><span><span class="block text-sm font-semibold">@lang('admin::app.catering-menus.active-menu')</span><span class="text-xs text-gray-500">@lang('admin::app.catering-menus.available-to-sales')</span></span></label>
                    <div class="mt-5 rounded-md bg-amber-50 p-4 text-xs leading-5 text-amber-800">@lang('admin::app.catering-menus.future-proposals-note')</div>
                </aside>
            </div>
        </div>
    </form>
</x-admin::layouts>
