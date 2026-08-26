<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Webkul\Admin\DataGrids\Quote\QuoteDataGrid;
use Webkul\Admin\Http\Controllers\Quote\QuoteController;
use Webkul\Contact\Models\Person;
use Webkul\Quote\Repositories\QuoteRepository;
use Webkul\User\Models\User;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$person = Person::query()->firstOrFail();
$user = User::query()->where('status', true)->firstOrFail();
auth()->guard('user')->login($user);
$route = app('router')->getRoutes()->getByName('admin.quotes.create');
$route->bind(request());
request()->setRouteResolver(fn () => $route);
app('view')->share('errors', new ViewErrorBag);
$formHtml = app(QuoteController::class)->create()->render();

assert(str_contains($formHtml, 'Menu Builder'));
assert(str_contains($formHtml, 'Download PDF') === false);
assert(str_contains($formHtml, 'v-model="selectedContactId"'));
assert(str_contains($formHtml, '+ Add new contact'));
assert(str_contains($formHtml, 'Contact details are taken directly from the CRM contact record'));
assert(! str_contains($formHtml, '>Client / company</label>'));
assert(preg_match('/<v-catering-proposal-builder\s+:initial=\'\{.*?"items":\[/s', $formHtml) === 1);
$gridResponse = app(QuoteDataGrid::class)->process();
assert($gridResponse->getStatusCode() === 200);

DB::beginTransaction();

try {
    $quote = app(QuoteRepository::class)->create([
        'entity_type'       => 'quotes',
        'subject'           => 'Catering Proposal Verification',
        'description'       => 'Automated transaction verification',
        'status'            => 'draft',
        'issued_at'         => '2026-11-15',
        'expired_at'        => '2026-12-14',
        'person_id'         => $person->id,
        'user_id'           => $user->id,
        'guest_count'       => 150,
        'vat_percent'       => 15,
        'adjustment_amount' => 0,
        'items'             => [[
            'name'            => 'Finger Food Reception',
            'pricing_type'    => 'per_person',
            'price'           => 250,
            'quantity'        => 150,
            'guest_count'     => 150,
            'discount_amount' => 0,
            'sort_order'      => 1,
        ]],
        'menu_sections' => [[
            'name'       => 'Sandwiches & Canapes',
            'sort_order' => 1,
            'items'      => [[
                'name'       => 'Muhammara Canape with Walnut & Pomegranate',
                'sort_order' => 1,
            ]],
        ]],
    ]);

    $quote->load(['items', 'menuSections.items']);

    assert((float) $quote->sub_total === 37500.0);
    assert((float) $quote->tax_amount === 5625.0);
    assert((float) $quote->grand_total === 43125.0);
    assert($quote->items->count() === 1);
    assert($quote->menuSections->count() === 1);
    assert(! empty($quote->document_snapshot));
    assert(data_get($quote->document_snapshot, 'sales_contact.name') === $user->name);
    assert(data_get($quote->document_snapshot, 'sales_contact.email') === $user->email);
    assert(str_starts_with($quote->proposal_reference, 'TC-2611-'));

    $editRoute = app('router')->getRoutes()->getByName('admin.quotes.edit');
    $editRoute->bind(request());
    request()->setRouteResolver(fn () => $editRoute);
    $editHtml = app(QuoteController::class)->edit($quote->id)->render();
    assert(str_contains($editHtml, 'ref="pdfLanguageModal"'));
    assert(str_contains($editHtml, 'Export quotation PDF'));
    assert(str_contains($editHtml, 'locale=en'));
    assert(str_contains($editHtml, 'locale=ar'));
    assert(str_contains($editHtml, 'العربية'));

    $arabicSnapshot = $quote->document_snapshot;
    data_set($arabicSnapshot, 'company.company_name_ar', 'تيارا للضيافة');
    data_set($arabicSnapshot, 'company.tagline_ar', 'خدمات ضيافة راقية - المملكة العربية السعودية');
    data_set($arabicSnapshot, 'company.proposal_title_ar', 'عرض خدمات الضيافة');
    data_set($arabicSnapshot, 'proposal.event_at', '2026-12-15 19:00:00');
    data_set($arabicSnapshot, 'proposal.event_type_ar', 'حفل استقبال بالمأكولات الخفيفة');
    $arabicHtml = view('admin::quotes.proposal-pdf', ['snapshot' => $arabicSnapshot, 'locale' => 'ar'])->render();
    assert(str_contains($arabicHtml, '<html lang="ar" dir="rtl">'));
    assert(str_contains($arabicHtml, 'القائمة المقترحة'));
    assert(str_contains($arabicHtml, 'الإجمالي شامل الضريبة'));
    assert(str_contains($arabicHtml, '15 ديسمبر 2026'));

    echo json_encode([
        'reference'     => $quote->proposal_reference,
        'subtotal'      => (float) $quote->sub_total,
        'vat'           => (float) $quote->tax_amount,
        'total'         => (float) $quote->grand_total,
        'menu_sections' => $quote->menuSections->count(),
        'snapshot'      => ! empty($quote->document_snapshot),
        'form_rendered' => true,
        'grid_prepared' => true,
        'pdf_languages' => ['en', 'ar'],
        'arabic_rtl'    => true,
    ], JSON_PRETTY_PRINT).PHP_EOL;
} finally {
    DB::rollBack();
}
