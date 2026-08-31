@foreach ($customAttributes as $attribute)
    @php
        $attribute = clone $attribute;
        $attributeLabelKey = 'admin::app.attribute-labels.'.$attribute->entity_type.'.'.$attribute->code;
        $translatedAttributeLabel = trans($attributeLabelKey);

        if ($translatedAttributeLabel !== $attributeLabelKey) {
            $attribute->name = $translatedAttributeLabel;
        }

        $validations = [];

        if ($attribute->is_required) {
            $validations[] = 'required';
        }

        if ($attribute->type == 'price') {
            $validations[] = 'decimal';
        }

        $validations[] = $attribute->validation;

        $validations = implode('|', array_filter($validations));
    @endphp

    <x-admin::form.control-group class="mb-2.5 w-full">
        <x-admin::form.control-group.label
            for="{{ $attribute->code }}"
            :class="$attribute->is_required ? 'required' : ''"
        >
            {{ $attribute->name }}

            @if ($attribute->type == 'price')
                <span class="currency-code">({{ core()->currencySymbol(config('app.currency')) }})</span>
            @endif
        </x-admin::form.control-group.label>

        @if (isset($attribute))
            <x-admin::attributes.edit.index
                :attribute="$attribute"
                :validations="$validations"
                :value="isset($entity) ? $entity[$attribute->code] : null"
            />
        @endif

        <x-admin::form.control-group.error :control-name="$attribute->code" />
    </x-admin::form.control-group>
@endforeach
