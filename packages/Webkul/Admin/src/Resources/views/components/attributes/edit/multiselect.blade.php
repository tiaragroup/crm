@php
    $options = $attribute->lookup_type
        ? app('Webkul\Attribute\Repositories\AttributeRepository')->getLookUpOptions($attribute->lookup_type)
        : $attribute->options()->orderBy('sort_order')->get();

    $selectedOptions = old($attribute->code, $value);

    if (! is_array($selectedOptions)) {
        $selectedOptions = array_filter(explode(',', (string) $selectedOptions));
    }
@endphp

@if ($attribute->code === 'interested_services')
    {{-- Submit an empty value when every option is cleared. --}}
    <input
        type="hidden"
        name="{{ $attribute->code }}"
        value=""
    >

    <div
        id="{{ $attribute->code }}"
        class="grid grid-cols-2 gap-2 rounded-md border border-gray-200 bg-white p-2.5 transition-all hover:border-gray-400 max-md:grid-cols-1 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-400"
        role="group"
        aria-label="{{ $attribute->name }}"
    >
        @foreach ($options as $option)
            @php
                $optionLabelKey = 'admin::app.attribute-options.'.$attribute->code.'.'.\Illuminate\Support\Str::slug($option->name);
                $translatedOptionLabel = trans($optionLabelKey);
                $optionLabel = $translatedOptionLabel !== $optionLabelKey ? $translatedOptionLabel : $option->name;
                $optionId = $attribute->code.'-'.$option->id;
            @endphp

            <label
                for="{{ $optionId }}"
                class="attribute-multiselect-option flex cursor-pointer items-center gap-2.5 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 transition-colors hover:border-brandColor dark:border-gray-700 dark:text-gray-300 dark:hover:border-brandColor"
            >
                <input
                    type="checkbox"
                    id="{{ $optionId }}"
                    name="{{ $attribute->code }}[]"
                    value="{{ $option->id }}"
                    class="h-4 w-4 shrink-0 cursor-pointer rounded border-gray-300 focus:ring-brandColor dark:border-gray-600"
                    style="accent-color: var(--brand-color)"
                    @checked(in_array((string) $option->id, array_map('strval', $selectedOptions), true))
                >

                <span>{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>
@else
    <v-field
        type="select"
        id="{{ $attribute->code }}"
        name="{{ $attribute->code }}[]"
        rules="{{ $validations }}"
        label="{{ $attribute->name }}"
        placeholder="{{ $attribute->name }}"
        multiple
    >
        <select
            name="{{ $attribute->code }}[]"
            class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
            multiple
        >
            @foreach ($options as $option)
                @php
                    $optionLabelKey = 'admin::app.attribute-options.'.$attribute->code.'.'.\Illuminate\Support\Str::slug($option->name);
                    $translatedOptionLabel = trans($optionLabelKey);
                    $optionLabel = $translatedOptionLabel !== $optionLabelKey ? $translatedOptionLabel : $option->name;
                @endphp

                <option
                    value="{{ $option->id }}"
                    @selected(in_array((string) $option->id, array_map('strval', $selectedOptions), true))
                >
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
    </v-field>
@endif

@pushOnce('styles')
    <style>
        .attribute-multiselect-option {
            min-height: 2.5rem;
        }

        .attribute-multiselect-option:has(input:checked) {
            border-color: var(--brand-color);
            background: color-mix(in srgb, var(--brand-color) 10%, transparent);
            color: rgb(17 24 39);
            font-weight: 500;
        }

        .attribute-multiselect-option:focus-within {
            outline: 2px solid color-mix(in srgb, var(--brand-color) 45%, transparent);
            outline-offset: 1px;
        }

        .dark .attribute-multiselect-option:has(input:checked) {
            color: rgb(255 255 255);
        }
    </style>
@endPushOnce
