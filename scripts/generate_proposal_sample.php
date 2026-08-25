<?php

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Console\Kernel;
use PhpOffice\PhpWord\IOFactory;
use Webkul\Product\Models\CateringMenuCategory;
use Webkul\Quote\Models\ProposalSetting;
use Webkul\Quote\Services\ProposalWordExporter;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$format = $argv[1] ?? null;
$settings = ProposalSetting::query()->firstOrFail()->toArray();
$sections = CateringMenuCategory::query()
    ->with(['products' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
    ->orderBy('sort_order')
    ->get()
    ->map(fn ($category) => [
        'name'       => $category->name,
        'sort_order' => $category->sort_order,
        'items'      => $category->products->map(fn ($product) => [
            'name'        => $product->name,
            'description' => $product->description,
            'sort_order'  => $product->sort_order,
        ])->all(),
    ])->all();

$snapshot = [
    'company'  => $settings,
    'proposal' => [
        'reference'               => 'TC-2612-0001',
        'status'                  => 'draft',
        'revision'                => 1,
        'subject'                 => 'Catering Function Proposal',
        'issued_at'               => '2026-11-15',
        'valid_until'             => '2026-12-14',
        'client_company'          => 'Razan Kharraz',
        'attention_name'          => 'Razan Kharraz',
        'client_mobile'           => '+966 50 000 0000',
        'client_email'            => 'razan@example.com',
        'greeting'                => $settings['greeting_template'],
        'event_type'              => 'Finger Food Reception',
        'event_at'                => '2026-12-15 19:00:00',
        'venue'                   => 'Riyadh, Saudi Arabia',
        'setup_description'       => 'Finger food reception - no buffet tables required. Items presented on serving platters, ready to serve, with on-site service team, delivery and setup.',
        'guest_count'             => 150,
        'service_inclusions'      => preg_split('/\r\n|\r|\n/', "Finger food presented on serving platters - no buffet tables required\nDisposable plates, napkins and cups\nProfessional service team, delivery, setup and clearing"),
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
        'description'     => 'Complete catering package as detailed in the proposed menu.',
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

if ($format === 'pdf') {
    $directory = dirname(__DIR__).'/output/pdf';
    is_dir($directory) || mkdir($directory, 0755, true);
    Pdf::loadHTML(view('admin::quotes.proposal-pdf', compact('snapshot'))->render())
        ->setPaper('letter', 'portrait')
        ->save($directory.'/tiara-catering-proposal-sample.pdf');
    echo $directory.'/tiara-catering-proposal-sample.pdf';
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

fwrite(STDERR, "Usage: php scripts/generate_proposal_sample.php [pdf|docx]\n");
exit(1);
