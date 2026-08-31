@php
    $options = $attribute->lookup_type
        ? app('Webkul\Attribute\Repositories\AttributeRepository')->getLookUpOptions($attribute->lookup_type)
        : $attribute->options()->orderBy('sort_order')->get();

    $options = collect($options)->map(function ($option) use ($attribute) {
        $option = clone $option;
        $optionLabelKey = 'admin::app.attribute-options.'.$attribute->code.'.'.\Illuminate\Support\Str::slug($option->name);
        $translatedOptionLabel = trans($optionLabelKey);

        if ($translatedOptionLabel !== $optionLabelKey) {
            $option->name = $translatedOptionLabel;
        }

        return $option;
    });
@endphp

<x-admin::form.control-group.controls.inline.select
    ::name="'{{ $attribute->code }}'"
    :value="$value"
    :options="$options"
    rules="required"
    position="left"
    :label="$attribute->name"
    ::errors="errors"
    :placeholder="$attribute->name"
    :url="$url"
    :allow-edit="$allowEdit"
/>
