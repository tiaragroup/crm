@php
    $fieldClass = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 outline-none focus:border-brandColor dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300';
@endphp

<div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-800">
    <div class="mb-4">
        <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.catering-products.title')</p>
        <p class="text-xs text-gray-500">@lang('admin::app.catering-products.description')</p>
    </div>

    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">@lang('admin::app.catering-products.arabic-name')</label>
            <input dir="rtl" lang="ar" name="name_ar" value="{{ old('name_ar', $product?->name_ar) }}" placeholder="اسم المنتج بالعربية" class="{{ $fieldClass }}">
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">@lang('admin::app.catering-products.arabic-description')</label>
            <textarea dir="rtl" lang="ar" name="description_ar" rows="3" placeholder="وصف المنتج بالعربية" class="{{ $fieldClass }}">{{ old('description_ar', $product?->description_ar) }}</textarea>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">@lang('admin::app.catering-products.menu-section') *</label>
            <select name="catering_menu_category_id" required class="{{ $fieldClass }}">
                <option value="" disabled @selected(! old('catering_menu_category_id', $product?->catering_menu_category_id))>@lang('admin::app.catering-products.select-category')</option>
                @foreach ($cateringMenuCategories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('catering_menu_category_id', $product?->catering_menu_category_id) === $category->id)>{{ app()->isLocale('ar') && $category->name_ar ? $category->name_ar : $category->name }}</option>
                @endforeach
            </select>

            @error('catering_menu_category_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">@lang('admin::app.catering-products.unit-type') *</label>
            <select name="unit_type" required class="{{ $fieldClass }}">
                @foreach (['menu_item' => trans('admin::app.catering-products.unit-types.menu-item'), 'per_person' => trans('admin::app.catering-products.unit-types.per-person'), 'fixed' => trans('admin::app.catering-products.unit-types.fixed'), 'included' => trans('admin::app.catering-products.unit-types.included')] as $value => $label)
                    <option value="{{ $value }}" @selected(old('unit_type', $product?->unit_type ?: 'menu_item') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">@lang('admin::app.catering-products.allergens')</label>
            <input name="allergens" value="{{ old('allergens', $product?->allergens) }}" placeholder="@lang('admin::app.catering-products.allergens-placeholder')" class="{{ $fieldClass }}">
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">@lang('admin::app.catering-products.arabic-allergens')</label>
            <input dir="rtl" lang="ar" name="allergens_ar" value="{{ old('allergens_ar', $product?->allergens_ar) }}" placeholder="الجلوتين، الألبان، المكسرات…" class="{{ $fieldClass }}">
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">@lang('admin::app.catering-products.display-order')</label>
            <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $product?->sort_order ?: 0) }}" class="{{ $fieldClass }}">
        </div>

        <div class="col-span-2 flex items-center gap-2 max-md:col-span-1">
            <input type="hidden" name="is_active" value="0">
            <input id="catering-product-active" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $product?->is_active ?? true)) class="h-4 w-4 rounded border-gray-300 text-brandColor">
            <label for="catering-product-active" class="text-sm font-medium text-gray-800 dark:text-white">@lang('admin::app.catering-products.available')</label>
        </div>
    </div>
</div>
