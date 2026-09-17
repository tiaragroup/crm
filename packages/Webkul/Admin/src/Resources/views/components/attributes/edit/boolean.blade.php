@php
    $selectedOption = old($attribute->code, $value);
@endphp

<input
    type="hidden"
    name="{{ $attribute->code }}"
    value="0"
>

<label class="relative inline-flex cursor-pointer items-center">
    <v-field
        type="checkbox"
        name="{{ $attribute->code }}"
        value="1"
        rules="{{ $validations }}"
        label="{{ $attribute->name }}"
        v-slot="{ field, errors }"
    >
        <input
            type="checkbox"
            name="{{ $attribute->code }}"
            value="1"
            id="{{ $attribute->code }}"
            class="peer sr-only"
            v-bind="field"
        >

        <v-checked-handler
            :field="field"
            checked="{{ $selectedOption ? 1 : '' }}"
        >
        </v-checked-handler>

        <div
            class="peer h-5 w-9 cursor-pointer rounded-full bg-gray-200 after:absolute after:top-0.5 after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brandColor peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:bg-gray-800 dark:after:border-white dark:after:bg-white dark:peer-checked:bg-gray-950 after:ltr:left-0.5 peer-checked:after:ltr:translate-x-full after:rtl:right-0.5 peer-checked:after:rtl:-translate-x-full"
            :class="errors.length ? 'ring-2 ring-red-600' : ''"
        ></div>
    </v-field>
</label>
