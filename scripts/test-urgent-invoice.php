<?php

// Boots the local app read-only, then switches ALL test writes to isolated in-memory SQLite.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'invoice_test', 'database.connections.invoice_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Webkul\Quote\Models\Quote;
use Webkul\Quote\Models\UrgentTaxInvoice;
use Webkul\Quote\Services\UrgentInvoiceAccess;
use Webkul\Quote\Services\UrgentInvoiceArchive;
use Webkul\Quote\Services\UrgentInvoiceDetails;
use Webkul\Quote\Services\UrgentInvoiceIssuer;
use Webkul\Quote\Services\UrgentInvoiceSource;
use Webkul\Quote\Services\UrgentInvoiceTotalsValidator;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

function check($condition, $label)
{
    if (! $condition) {
        throw new RuntimeException('FAILED: '.$label);
    }
    echo 'PASS: '.$label.PHP_EOL;
}
check(DB::connection()->getConfig('database') === ':memory:', 'isolated database');
Schema::create('users', fn (Blueprint $t) => $t->increments('id'));
Schema::create('attributes', function (Blueprint $t) {
    $t->increments('id');
    $t->string('code');
});
Schema::create('attribute_values', function (Blueprint $t) {
    $t->increments('id');
    $t->string('entity_type');
    $t->integer('entity_id');
});
Schema::create('quotes', function (Blueprint $t) {
    $t->increments('id');
    foreach (['subject', 'status', 'proposal_reference', 'client_company', 'attention_name', 'client_email', 'client_mobile', 'event_type', 'event_at', 'venue'] as $c) {
        $t->string($c)->nullable();
    }
    foreach (['user_id', 'person_id', 'revision', 'guest_count'] as $c) {
        $t->integer($c)->nullable();
    }
    foreach (['sub_total', 'discount_amount', 'adjustment_amount', 'vat_percent', 'tax_amount', 'grand_total'] as $c) {
        $t->decimal($c, 12, 4)->default(0);
    }
    $t->timestamps();
});
Schema::create('quote_items', function (Blueprint $t) {
    $t->increments('id');
    foreach (['name', 'description', 'pricing_type'] as $c) {
        $t->string($c)->nullable();
    }
    foreach (['quote_id', 'quantity', 'guest_count', 'sort_order', 'is_included'] as $c) {
        $t->integer($c)->default(0);
    }
    foreach (['price', 'discount_amount', 'total'] as $c) {
        $t->decimal($c, 12, 4)->default(0);
    }
    $t->timestamps();
});
foreach (['2026_10_28_130000_create_urgent_tax_invoice_tables.php', '2026_10_28_150000_protect_urgent_tax_invoice_archives.php'] as $file) {
    (require base_path('database/migrations/'.$file))->up();
}
DB::table('users')->insert(['id' => 1]);
$user = new User;
$user->forceFill(['id' => 1, 'name' => 'Test Administrator', 'status' => 1, 'view_permission' => 'global']);
$role = new Role;
$role->forceFill(['permission_type' => 'all', 'permissions' => null]);
$user->setRelation('role', $role);
auth()->guard('user')->setUser($user);
app(UrgentInvoiceAccess::class)->permission('quotes.print');
check(true, 'administrator with null permissions can preview (reported crash)');
config(['urgent_invoice.enabled'             => true, 'urgent_invoice.phase_one_reviewed' => true, 'urgent_invoice.preview_enabled' => true,
    'urgent_invoice.seller'                  => ['legal_name_ar' => 'شركة تجريبية', 'legal_name_en' => 'Test Company', 'vat_number' => '300000000000003', 'cr_number' => 'TEST', 'street' => 'Test Street', 'building' => '1234', 'district' => 'Test District', 'city' => 'Riyadh', 'postal_code' => '12345', 'country_code' => 'SA', 'address_ar' => 'عنوان تجريبي الرياض', 'address_en' => 'Test address Riyadh'],
    'filesystems.disks.urgent_invoices.root' => storage_path('framework/testing/invoice-'.bin2hex(random_bytes(6))),
]);
$quote = Quote::create(['subject' => 'Synthetic test', 'status' => 'accepted', 'proposal_reference' => 'TEST-ONLY', 'revision' => 1, 'user_id' => 1, 'guest_count' => 150, 'sub_total' => '22500.00', 'discount_amount' => '0', 'adjustment_amount' => '0', 'vat_percent' => '15', 'tax_amount' => '3375', 'grand_total' => '25875']);
DB::table('quote_items')->insert(['quote_id' => $quote->id, 'name' => 'Test catering', 'pricing_type' => 'per_person', 'quantity' => 150, 'guest_count' => 150, 'price' => '150', 'total' => '22500']);
$source = app(UrgentInvoiceSource::class);
$details = app(UrgentInvoiceDetails::class);
$preview = $source->preview($quote->id);
$buyer = ['invoice_type' => 'standard', 'invoice_type_confirmed' => 1, 'buyer_name' => 'Test buyer', 'buyer_name_ar' => 'عميل تجريبي', 'buyer_address' => 'Test address', 'buyer_address_ar' => 'عنوان تجريبي', 'buyer_city' => 'Riyadh', 'payment_method' => 'bank_transfer', 'payment_status' => 'pending', 'supply_date' => now('Asia/Riyadh')->toDateString(), 'supply_confirmed' => 1, 'no_prior_invoice' => 1, 'item_names_ar' => ['خدمة ضيافة تجريبية']];
$details->buyer($buyer, $preview);
$enteredSeller = config('urgent_invoice.seller');
config(['urgent_invoice.seller' => array_fill_keys(array_keys($enteredSeller), null)]);
try {
    $details->seller();
    throw new RuntimeException('Empty seller accepted');
} catch (ValidationException $e) {
    check(str_contains($e->errors()['seller.legal_name_ar'][0], 'TIARA_INVOICE_LEGAL_NAME_AR'), 'seller errors identify exact environment setting');
}
config(['urgent_invoice.seller' => $enteredSeller]);
check($details->seller()['vat_number'] === '300000000000003', 'seller details read only from configuration');
$receivedBuyer = array_merge($buyer, ['payment_status' => 'received', 'transaction_reference' => 'TEST-RECEIPT', 'payment_received_at' => now('Asia/Riyadh')->subMinute()->format('Y-m-d\TH:i')]);
try {
    $details->buyer($receivedBuyer, $preview);
    throw new RuntimeException('Unconfirmed receipt accepted');
} catch (ValidationException $e) {
    check(isset($e->errors()['payment_confirmed']), 'received payment requires confirmation');
}
$receivedBuyer['payment_confirmed'] = 1;
check($details->buyer($receivedBuyer, $preview)['payment_status'] === 'received', 'confirmed receipt accepted');
check(UrgentTaxInvoice::count() === 0 && DB::table('urgent_tax_invoice_sequences')->value('next_number') === 1, 'review allocates no invoice number');
$totals = app(UrgentInvoiceTotalsValidator::class);
check($totals->validate($preview)['grand_total'] === '25875.00', '150 guests × SAR 150 + 15% VAT');
$bad = $preview;
$bad['proposal_totals']['vat_amount'] = '1';
try {
    $totals->validate($bad);
    throw new RuntimeException('Bad VAT accepted');
} catch (DomainException $e) {
    check(true, 'incorrect VAT rejected');
}
$quote->update(['status' => 'draft']);
check($source->preview($quote->id, true)['source']['status'] === 'draft', 'draft preview allowed');
try {
    $source->preview($quote->id);
    throw new RuntimeException('Draft accepted');
} catch (DomainException $e) {
    check(true, 'draft issuance rejected');
}
$quote->update(['status' => 'accepted']);
$issuer = app(UrgentInvoiceIssuer::class);
try {
    $issuer->issue($quote->id, $buyer, 'stale');
    throw new RuntimeException('Stale review accepted');
} catch (DomainException $e) {
    check(true, 'stale review rejected');
}
$invoice = $issuer->issue($quote->id, $buyer, $details->fingerprint($source->preview($quote->id)));
check($invoice->archive_status === 'ready', 'PDF generated and privately archived');
$bytes = app(UrgentInvoiceArchive::class)->bytes($invoice);
check(str_starts_with($bytes, '%PDF-'), 'archived PDF readable and hash verified');
$again = $issuer->issue($quote->id, [], '');
check($again->id === $invoice->id && UrgentTaxInvoice::count() === 1 && DB::table('urgent_tax_invoice_sequences')->value('next_number') === 2, 'duplicate submission returns same invoice and number');
$decoded = base64_decode($invoice->qr_payload, true);
$fields = [];
$offset = 0;
while ($offset < strlen($decoded)) {
    $tag = ord($decoded[$offset++]);
    $length = ord($decoded[$offset++]);
    $fields[$tag] = substr($decoded, $offset, $length);
    $offset += $length;
}
check($fields[1] === 'شركة تجريبية' && $fields[2] === '300000000000003' && $fields[4] === '25875.00' && $fields[5] === '3375.00' && count($fields) === 5, 'QR decoded: UTF-8 byte lengths and all five fields');
try {
    DB::table('urgent_tax_invoices')->where('id', $invoice->id)->update(['invoice_number' => 'CHANGED']);
    throw new RuntimeException('Mutation allowed');
} catch (QueryException $e) {
    check(true, 'database rejects issued content changes');
}
$quote->delete();
$invoice->refresh();
check($invoice->quote_id === null && app(UrgentInvoiceArchive::class)->bytes($invoice) === $bytes, 'original PDF retained after proposal deletion');
Storage::disk('urgent_invoices')->put($invoice->pdf_path, 'tampered');
try {
    app(UrgentInvoiceArchive::class)->bytes($invoice);
    throw new RuntimeException('Corruption accepted');
} catch (DomainException $e) {
    check(true, 'archive corruption detected');
}
echo 'ALL INVOICE SMOKE CHECKS PASSED'.PHP_EOL;
