<?php

namespace Webkul\Tax\Validation;

class FieldLabels
{
    public static function forRules(array $rules): array
    {
        $labels = [];
        foreach (array_keys($rules) as $field) {
            $key = last(explode('.', $field));
            if (trans()->has('admin::tax.'.$key)) {
                $labels[$field] = trans('admin::tax.'.$key);
            }
        }

        return $labels;
    }
}
