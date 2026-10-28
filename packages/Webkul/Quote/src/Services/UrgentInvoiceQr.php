<?php

namespace Webkul\Quote\Services;

class UrgentInvoiceQr
{
    public function encode(string $seller, string $vat, string $timestamp, string $gross, string $tax): string
    {
        $tlv = '';
        foreach ([$seller, $vat, $timestamp, $gross, $tax] as $index => $value) {
            if ($value === '' || strlen($value) > 255 || ! mb_check_encoding($value, 'UTF-8')) {
                throw new \DomainException('Invalid Phase 1 QR field.');
            }
            $tlv .= chr($index + 1).chr(strlen($value)).$value;
        }

        return base64_encode($tlv);
    }
}
