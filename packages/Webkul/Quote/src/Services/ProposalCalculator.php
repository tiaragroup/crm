<?php

namespace Webkul\Quote\Services;

class ProposalCalculator
{
    public function calculate(array $data): array
    {
        $guestCount = max(0, (int) ($data['guest_count'] ?? 0));
        $items = $data['items'] ?? [];
        $subTotal = 0.0;
        $discountTotal = 0.0;

        foreach ($items as $key => $item) {
            $pricingType = $item['pricing_type'] ?? 'per_person';
            $isIncluded = $pricingType === 'included' || ! empty($item['is_included']);
            $price = max(0, (float) ($item['price'] ?? 0));
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $itemGuests = max(0, (int) ($item['guest_count'] ?? $guestCount));
            $discount = max(0, (float) ($item['discount_amount'] ?? 0));

            if ($isIncluded) {
                $lineTotal = 0.0;
                $price = 0.0;
                $discount = 0.0;
            } elseif ($pricingType === 'fixed') {
                $lineTotal = $price * $quantity;
            } else {
                $pricingType = 'per_person';
                $lineTotal = $price * $itemGuests;
                $quantity = $itemGuests;
            }

            $lineTotal = round($lineTotal, 2);
            $discount = min(round($discount, 2), $lineTotal);

            $items[$key] = array_merge($item, [
                'pricing_type'    => $pricingType,
                'is_included'     => $isIncluded,
                'price'           => $price,
                'quantity'        => $quantity,
                'guest_count'     => $pricingType === 'per_person' ? $itemGuests : null,
                'discount_amount' => $discount,
                'tax_amount'      => 0,
                'total'           => $lineTotal,
                'sort_order'      => (int) ($item['sort_order'] ?? 0),
            ]);

            $subTotal += $lineTotal;
            $discountTotal += $discount;
        }

        $subTotal = round($subTotal, 2);
        $discountTotal = round($discountTotal, 2);
        $adjustment = round((float) ($data['adjustment_amount'] ?? 0), 2);
        $vatPercent = max(0, (float) ($data['vat_percent'] ?? 15));
        $taxableAmount = max(0, $subTotal - $discountTotal + $adjustment);
        $taxAmount = round($taxableAmount * ($vatPercent / 100), 2);

        return array_merge($data, [
            'items'            => $items,
            'sub_total'        => $subTotal,
            'discount_amount'  => $discountTotal,
            'tax_amount'       => $taxAmount,
            'adjustment_amount'=> $adjustment,
            'grand_total'      => round($taxableAmount + $taxAmount, 2),
        ]);
    }
}
