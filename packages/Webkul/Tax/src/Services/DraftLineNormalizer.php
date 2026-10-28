<?php

namespace Webkul\Tax\Services;

use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

/** Exact input normalization only. Statutory calculation/rounding belongs to the next sprint. */
class DraftLineNormalizer
{
    public function normalize(array $items): array
    {
        return array_map(function (array $item, int $index) {
            $category = ($item['tax_category'] ?? null) ?: null;
            $rate = isset($item['tax_rate']) && $item['tax_rate'] !== '' ? BigDecimal::of($item['tax_rate']) : null;
            if (($category === 'S' && (! $rate || $rate->isLessThanOrEqualTo(0)))
                || (in_array($category, ['Z', 'E', 'O'], true) && (! $rate || ! $rate->isZero()))
                || (! $category && $rate !== null)) {
                throw ValidationException::withMessages(["items.$index.tax_rate" => trans('admin::tax.invalid-tax')]);
            }
            if (in_array($category, ['Z', 'E', 'O'], true)
                && (empty($item['exemption_code']) || empty($item['exemption_reason']))) {
                throw ValidationException::withMessages(["items.$index.exemption_reason" => trans('admin::tax.exemption-required')]);
            }

            return array_merge($item, [
                'line_number'     => $index + 1,
                'quantity'        => (string) BigDecimal::of($item['quantity'])->toScale(6),
                'unit_price'      => (string) BigDecimal::of($item['unit_price'])->toScale(6),
                'discount_amount' => (string) BigDecimal::of($item['discount_amount'] ?? '0')->toScale(2),
                'tax_category'    => $category,
                'tax_rate'        => $rate === null ? null : (string) $rate->toScale(6),
            ]);
        }, array_values($items), array_keys(array_values($items)));
    }
}
