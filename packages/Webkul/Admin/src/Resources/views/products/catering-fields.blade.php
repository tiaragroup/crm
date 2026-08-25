@php
    $fieldClass = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 outline-none focus:border-brandColor dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300';
@endphp

<div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-800">
    <div class="mb-4">
        <p class="text-base font-semibold text-gray-800 dark:text-white">Catering Catalog</p>
        <p class="text-xs text-gray-500">Controls how this product appears in proposal menu packages.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">Menu section</label>
            <select name="catering_menu_category_id" class="{{ $fieldClass }}">
                <option value="">Not a catering menu item</option>
                @foreach ($cateringMenuCategories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('catering_menu_category_id', $product?->catering_menu_category_id) === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">Unit type *</label>
            <select name="unit_type" required class="{{ $fieldClass }}">
                @foreach (['menu_item' => 'Menu item', 'per_person' => 'Per person', 'fixed' => 'Fixed price', 'included' => 'Included'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('unit_type', $product?->unit_type ?: 'menu_item') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">Allergens / dietary notes</label>
            <input name="allergens" value="{{ old('allergens', $product?->allergens) }}" placeholder="Gluten, dairy, nuts…" class="{{ $fieldClass }}">
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">Menu display order</label>
            <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $product?->sort_order ?: 0) }}" class="{{ $fieldClass }}">
        </div>

        <div class="col-span-2 flex items-center gap-2 max-md:col-span-1">
            <input type="hidden" name="is_active" value="0">
            <input id="catering-product-active" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $product?->is_active ?? true)) class="h-4 w-4 rounded border-gray-300 text-brandColor">
            <label for="catering-product-active" class="text-sm font-medium text-gray-800 dark:text-white">Available in catering proposals</label>
        </div>
    </div>
</div>
