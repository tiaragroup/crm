<?php

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Webkul\Quote\Models\UrgentTaxInvoice;
use Webkul\Quote\Services\UrgentInvoicePdf;
use Webkul\Quote\Services\UrgentInvoiceQr;

// Read-only application bootstrap; no invoice record or sequence is written.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$invoice = new UrgentTaxInvoice;
$invoice->forceFill([
    'invoice_number'         => 'SAMPLE-NOT-ISSUED', 'snapshot_version' => 1,
    'invoice_type'           => 'standard', 'issued_at' => now('UTC'),
    'source_quote_reference' => 'SAMPLE-PROPOSAL', 'source_quote_revision' => 1,
    'seller_snapshot'        => [
        'legal_name_ar' => 'تيارا للضيافة — بيانات تجريبية', 'legal_name_en' => 'Tiara Catering - TEST DATA',
        'vat_number'    => '300000000000003', 'cr_number' => 'TEST ONLY',
        'address_ar'    => 'عنوان تجريبي، الرياض، المملكة العربية السعودية',
        'address_en'    => 'Sample address, Riyadh, Saudi Arabia',
        'building'      => '1234', 'street' => 'Sample Street', 'district' => 'Sample District',
        'city'          => 'Riyadh', 'postal_code' => '12345', 'country_code' => 'SA',
    ],
    'buyer_snapshot' => [
        'buyer_name'     => 'Sample Customer - NOT A REAL CUSTOMER', 'buyer_name_ar' => 'عميل تجريبي — ليس عميلاً حقيقياً',
        'buyer_address'  => 'Sample buyer address, Riyadh', 'buyer_address_ar' => 'عنوان مشتري تجريبي، الرياض',
        'buyer_city'     => 'Riyadh', 'buyer_vat_registered' => false,
        'supply_date'    => now('Asia/Riyadh')->toDateString(),
        'payment_method' => 'bank_transfer', 'payment_status' => 'pending',
    ],
    'items_snapshot' => [[
        'name'     => 'Catering service for 150 guests', 'name_ar' => 'خدمة ضيافة لعدد ١٥٠ ضيفاً',
        'quantity' => 150, 'unit_price' => '150.00', 'discount_amount' => '0.00', 'line_total' => '22500.00',
    ]],
    'totals_snapshot' => ['subtotal' => '22500.00', 'discount' => '0.00', 'taxable_amount' => '22500.00', 'vat_amount' => '3375.00', 'grand_total' => '25875.00'],
]);
$invoice->qr_payload = app(UrgentInvoiceQr::class)->encode(
    $invoice->seller_snapshot['legal_name_ar'], $invoice->seller_snapshot['vat_number'],
    CarbonImmutable::parse($invoice->getAttributes()['issued_at'], 'UTC')->format('Y-m-d\TH:i:s\Z'), '25875.00', '3375.00'
);
$directory = base_path('output/pdf');
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}
$path = $directory.'/tiara-invoice-SAMPLE.pdf';
file_put_contents($path, app(UrgentInvoicePdf::class)->render($invoice, true));
echo $path.PHP_EOL;
