@php
    $options = $attribute->lookup_type
        ? app('Webkul\Attribute\Repositories\AttributeRepository')->getLookUpOptions($attribute->lookup_type)
        : $attribute->options()->orderBy('sort_order')->get();
@endphp

<x-admin::form.control-group.control
    type="select"
    id="{{ $attribute->code }}"
    name="{{ $attribute->code }}"
    rules="{{ $validations }}"
    :label="$attribute->name"
    :placeholder="$attribute->name"
    :value="old($attribute->code) ?? $value"
>
    @foreach ($options as $option)
        @php
            $optionLabelKey = 'admin::app.attribute-options.'.$attribute->code.'.'.\Illuminate\Support\Str::slug($option->name);
            $translatedOptionLabel = trans($optionLabelKey);
            $optionLabel = $translatedOptionLabel !== $optionLabelKey ? $translatedOptionLabel : $option->name;
        @endphp

        <option value="{{ $option->id }}">
            {{ $optionLabel }}
        </option>
    @endforeach
</x-admin::form.control-group.control>
