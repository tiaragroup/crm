<?php

namespace Webkul\Quote\Services;

use DomainException;

class UrgentInvoiceTotalsValidator
{
    /**
     * Validate the pricing of a proposal before using
     * it as the source of a tax invoice.
     *
     * This method does not create or issue invoices.
     */
    public function validate(array $preview): array
    {
        $items = $preview['items'] ?? [];
        $totals = $preview['proposal_totals'] ?? [];

        if (empty($items) || count($items) > 200) {
            throw new DomainException('No chargeable items found.');
        }

        $calculatedSubtotal = 0;
        $calculatedDiscount = 0;

        foreach ($items as $index => $item) {
            $label = 'Item '.($index + 1);

            $type = $item['pricing_type'] ?? null;

            if (! in_array($type, ['fixed', 'per_person'], true)) {
                throw new DomainException(
                    $label.': unsupported pricing type.'
                );
            }

            $quantity = (string) ($item['quantity'] ?? '');

            if (
                ! preg_match('/^[1-9][0-9]*$/D', $quantity)
                || (int) $quantity > 100000
            ) {
                throw new DomainException(
                    $label.': invalid quantity.'
                );
            }

            $quantity = (int) $quantity;

            if ($type === 'per_person' && (int) ($item['guest_count'] ?? 0) !== $quantity) {
                throw new DomainException($label.': guest count does not match quantity.');
            }

            $unitPrice = $this->toCents(
                $item['unit_price'] ?? null,
                $label.' unit price'
            );

            $lineTotal = $this->toCents(
                $item['line_total'] ?? null,
                $label.' line total'
            );

            $discount = $this->toCents(
                $item['discount_amount'] ?: '0',
                $label.' discount'
            );

            if ($unitPrice * $quantity !== $lineTotal) {
                throw new DomainException(
                    $label.': unit price and quantity do not match line total.'
                );
            }

            if ($discount > $lineTotal) {
                throw new DomainException(
                    $label.': discount exceeds line total.'
                );
            }

            $calculatedSubtotal += $lineTotal;
            $calculatedDiscount += $discount;
            if ($calculatedSubtotal > 99999999999) {
                throw new DomainException('Invoice exceeds the supported monetary limit.');
            }
        }

        $subtotal = $this->toCents(
            $totals['subtotal'] ?? null,
            'Proposal subtotal'
        );

        $discount = $this->toCents(
            $totals['discount'] ?? '0',
            'Proposal discount'
        );

        $adjustment = $this->toCents(
            $totals['adjustment'] ?? '0',
            'Proposal adjustment',
            true
        );

        $vatRate = $this->toCents(
            $totals['vat_rate'] ?? null,
            'VAT rate'
        );

        if ($adjustment !== 0) {
            throw new DomainException('Adjustments require the full invoicing workflow; this temporary workflow supports zero adjustment only.');
        }

        $vatAmount = $this->toCents(
            $totals['vat_amount'] ?? null,
            'VAT amount'
        );

        $grandTotal = $this->toCents(
            $totals['grand_total'] ?? null,
            'Grand total'
        );

        if ($calculatedSubtotal !== $subtotal) {
            throw new DomainException(
                'Invoice item totals do not match proposal subtotal.'
            );
        }

        if ($calculatedDiscount !== $discount) {
            throw new DomainException(
                'Invoice item discounts do not match proposal discounts.'
            );
        }

        // Temporary client workflow supports only
        // validated 15% VAT proposals.
        if ($vatRate !== 1500) {
            throw new DomainException(
                'Only 15% VAT is currently supported.'
            );
        }

        $taxable = $subtotal - $discount + $adjustment;

        if ($taxable <= 0) {
            throw new DomainException(
                'Taxable amount cannot be negative.'
            );
        }

        // Integer-cents calculation:
        // 15% VAT rounded to the nearest halalah.
        $expectedVat = intdiv($taxable * 15 + 50, 100);

        if ($expectedVat !== $vatAmount) {
            throw new DomainException(
                'Calculated VAT does not match proposal VAT.'
            );
        }

        if ($taxable + $vatAmount !== $grandTotal) {
            throw new DomainException(
                'Grand total does not match taxable amount plus VAT.'
            );
        }

        return [
            'currency'       => 'SAR',
            'subtotal'       => $this->formatCents($subtotal),
            'discount'       => $this->formatCents($discount),
            'adjustment'     => $this->formatCents($adjustment),
            'taxable_amount' => $this->formatCents($taxable),
            'vat_rate'       => '15.00',
            'vat_amount'     => $this->formatCents($vatAmount),
            'grand_total'    => $this->formatCents($grandTotal),
        ];
    }

    /**
     * Convert an exact decimal string into integer halalah.
     * No floating-point arithmetic.
     */
    private function toCents(
        mixed $value,
        string $label,
        bool $allowNegative = false
    ): int {
        $value = trim((string) $value);

        if (
            ! preg_match(
                '/^(-?)([0-9]{1,9})(?:\.([0-9]{1,4}))?$/D',
                $value,
                $matches
            )
        ) {
            throw new DomainException(
                $label.': invalid monetary value.'
            );
        }

        $negative = $matches[1] === '-';

        if ($negative && ! $allowNegative) {
            throw new DomainException(
                $label.': negative amount is not allowed.'
            );
        }

        $fraction = str_pad($matches[3] ?? '', 4, '0');

        if (substr($fraction, 2) !== '00') {
            throw new DomainException(
                $label.': more than two significant decimal places.'
            );
        }

        $cents = ((int) $matches[2] * 100)
            + (int) substr($fraction, 0, 2);

        return $negative ? -$cents : $cents;
    }

    private function formatCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $sign
            .intdiv($absolute, 100)
            .'.'
            .str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
