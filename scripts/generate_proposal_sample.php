<?php

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Console\Kernel;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpWord\IOFactory;
use Webkul\Product\Models\CateringMenuCategory;
use Webkul\Quote\Models\ProposalSetting;
use Webkul\Quote\Services\ProposalWordExporter;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$format = $argv[1] ?? null;
$locale = $format === 'pdf-ar' ? 'ar' : 'en';
$settings = ProposalSetting::query()->firstOrFail()->toArray();
$settings['company_name_ar'] = 'تيارا للضيافة';
$settings['tagline_ar'] = 'خدمات ضيافة راقية - المملكة العربية السعودية';
$settings['proposal_title_ar'] = 'عرض خدمات الضيافة';
$sections = CateringMenuCategory::query()
    ->with(['products' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
    ->orderBy('sort_order')
    ->get()
    ->map(fn ($category) => [
        'name'       => $category->name,
        'name_ar'    => $category->name_ar ?: $category->name,
        'sort_order' => $category->sort_order,
        'items'      => $category->products->map(fn ($product) => [
            'name'           => $product->name,
            'name_ar'        => $product->name_ar ?: $product->name,
            'description'    => $product->description,
            'description_ar' => $product->description_ar ?: $product->description,
            'sort_order'     => $product->sort_order,
        ])->all(),
    ])->all();

$snapshot = [
    'company'  => $settings,
    'proposal' => [
        'reference'               => 'TC-2612-0001',
        'status'                  => 'draft',
        'revision'                => 1,
        'subject'                 => 'Catering Function Proposal',
        'subject_ar'              => 'عرض خدمات الضيافة',
        'issued_at'               => '2026-11-15',
        'valid_until'             => '2026-12-14',
        'client_company'          => 'Razan Kharraz',
        'attention_name'          => 'Razan Kharraz',
        'client_mobile'           => '+966 50 000 0000',
        'client_email'            => 'razan@example.com',
        'greeting'                => $settings['greeting_template'],
        'event_type'              => 'Finger Food Reception',
        'event_type_ar'           => 'حفل استقبال بالمأكولات الخفيفة',
        'event_at'                => '2026-12-15 19:00:00',
        'venue'                   => 'Riyadh, Saudi Arabia',
        'venue_ar'                => 'الرياض، المملكة العربية السعودية',
        'setup_description'       => 'Finger food reception - no buffet tables required. Items presented on serving platters, ready to serve, with on-site service team, delivery and setup.',
        'setup_description_ar'    => 'حفل استقبال بالمأكولات الخفيفة من دون طاولات بوفيه، وتقدم الأصناف على أطباق تقديم جاهزة مع فريق خدمة في الموقع والتوصيل والتجهيز.',
        'guest_count'             => 150,
        'service_inclusions'      => preg_split('/\r\n|\r|\n/', "Finger food presented on serving platters - no buffet tables required\nDisposable plates, napkins and cups\nProfessional service team, delivery, setup and clearing"),
        'service_inclusions_ar'   => [
            'تقديم المأكولات الخفيفة على أطباق تقديم من دون طاولات بوفيه',
            'أطباق ومناديل وأكواب للاستخدام الواحد',
            'فريق خدمة محترف مع التوصيل والتجهيز والتنظيف',
        ],
        'vat_percent'             => 15,
        'pricing_terms'           => $settings['pricing_terms'],
        'payment_terms'           => $settings['payment_terms'],
        'changes_terms'           => $settings['changes_terms'],
        'cancellation_terms'      => $settings['cancellation_terms'],
        'bank_account_name'       => $settings['bank_account_name'],
        'bank_name'               => $settings['bank_name'],
        'iban'                    => $settings['iban'],
        'company_signatory_name'  => $settings['signatory_name'],
        'company_signatory_title' => $settings['signatory_title'],
        'client_signatory_name'   => 'Razan Kharraz',
        'client_signatory_title'  => 'Client',
        'sub_total'               => 37500,
        'discount_amount'         => 0,
        'tax_amount'              => 5625,
        'adjustment_amount'       => 0,
        'grand_total'             => 43125,
    ],
    'menu_sections' => $sections,
    'pricing_items' => [[
        'name'            => 'Finger Food Reception',
        'name_ar'         => 'حفل استقبال بالمأكولات الخفيفة',
        'description'     => 'Complete catering package as detailed in the proposed menu.',
        'description_ar'  => 'باقة ضيافة متكاملة حسب تفاصيل القائمة المقترحة.',
        'pricing_type'    => 'per_person',
        'price'           => 250,
        'quantity'        => 150,
        'guest_count'     => 150,
        'is_included'     => false,
        'discount_amount' => 0,
        'total'           => 37500,
        'sort_order'      => 1,
    ]],
];

if (in_array($format, ['pdf', 'pdf-ar'], true)) {
    $directory = dirname(__DIR__).'/output/pdf';
    is_dir($directory) || mkdir($directory, 0755, true);
    app()->setLocale($locale);
    $html = view('admin::quotes.proposal-pdf', compact('snapshot', 'locale'))->render();
    $filename = $locale === 'ar'
        ? 'tiara-catering-proposal-sample-ar.pdf'
        : 'tiara-catering-proposal-sample.pdf';
    $path = $directory.'/'.$filename;

    if ($locale === 'ar') {
        $pdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'LETTER',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'margin_left'      => 0,
            'margin_right'     => 0,
            'margin_top'       => 0,
            'margin_bottom'    => 0,
        ]);
        $pdf->SetDirectionality('rtl');
        $pdf->SetDisplayMode('fullpage');
        $pdf->WriteHTML($html);
        $pdf->Output($path, Destination::FILE);
    } else {
        Pdf::loadHTML($html)->setPaper('letter', 'portrait')->save($path);
    }

    echo $path;
    exit(0);
}

if ($format === 'docx') {
    $directory = dirname(__DIR__).'/output/docx';
    is_dir($directory) || mkdir($directory, 0755, true);
    $word = app(ProposalWordExporter::class)->build($snapshot);
    IOFactory::createWriter($word, 'Word2007')->save($directory.'/tiara-catering-proposal-sample.docx');
    echo $directory.'/tiara-catering-proposal-sample.docx';
    exit(0);
}

fwrite(STDERR, "Usage: php scripts/generate_proposal_sample.php [pdf|pdf-ar|docx]\n");
exit(1);
