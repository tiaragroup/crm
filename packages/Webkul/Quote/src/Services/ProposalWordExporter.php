<?php

namespace Webkul\Quote\Services;

use PhpOffice\PhpWord\Element\Header;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\Quote\Models\Quote;

class ProposalWordExporter
{
    public function __construct(protected ProposalSnapshot $snapshotBuilder) {}

    public function download(Quote $quote): BinaryFileResponse
    {
        $snapshot = $quote->document_snapshot ?: $this->snapshotBuilder->build($quote);
        $phpWord = $this->build($snapshot);
        $directory = storage_path('app/proposal-exports');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $reference = preg_replace('/[^A-Za-z0-9_-]/', '_', $quote->proposal_reference ?: 'draft_'.$quote->id);
        $path = $directory.DIRECTORY_SEPARATOR.'Proposal_'.$reference.'_'.uniqid().'.docx';

        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return response()
            ->download($path, 'Proposal_'.$reference.'.docx')
            ->deleteFileAfterSend(true);
    }

    public function build(array $snapshot): PhpWord
    {
        Settings::setOutputEscapingEnabled(true);

        $company = $snapshot['company'] ?? [];
        $proposal = $snapshot['proposal'] ?? [];
        $sections = collect($snapshot['menu_sections'] ?? []);
        $pricingItems = collect($snapshot['pricing_items'] ?? []);
        $gold = ltrim($company['primary_color'] ?? '#D7A052', '#');
        $green = ltrim($company['secondary_color'] ?? '#356D24', '#');
        $word = new PhpWord;
        $word->setDefaultFontName('Aptos');
        $word->setDefaultFontSize(9.5);
        $word->addTitleStyle(1, ['size' => 24, 'color' => $gold, 'bold' => false], ['spaceAfter' => 120]);
        $word->addTitleStyle(2, ['size' => 12, 'color' => $green, 'bold' => true], ['spaceBefore' => 160, 'spaceAfter' => 70]);
        $word->addTableStyle('Details', ['borderColor' => 'DDDDDD', 'borderSize' => 4, 'cellMargin' => 90]);
        $word->addTableStyle('Pricing', ['borderColor' => 'DDDDDD', 'borderSize' => 4, 'cellMargin' => 70]);

        $section = $word->addSection([
            'pageSizeW'   => Converter::inchToTwip(8.5),
            'pageSizeH'   => Converter::inchToTwip(11),
            'marginTop'   => Converter::inchToTwip(.75),
            'marginRight' => Converter::inchToTwip(.7),
            'marginBottom'=> Converter::inchToTwip(.65),
            'marginLeft'  => Converter::inchToTwip(.7),
        ]);
        $this->addHeaderFooter($section, $company, $proposal, $gold, $green);

        $section->addTitle($company['proposal_title'] ?? 'Catering Function Proposal', 1);
        $section->addText('Proposal reference: '.($proposal['reference'] ?? 'Draft').'  |  Revision '.($proposal['revision'] ?? 1), ['color' => '777777']);
        $section->addTextBreak();
        $cover = $section->addTable('Details');
        $cover->addRow();
        $left = $cover->addCell(4700, ['bgColor' => 'F4F2ED']);
        $left->addText('PREPARED FOR', ['size' => 7, 'bold' => true, 'color' => $gold]);
        $left->addText($proposal['client_company'] ?: $proposal['attention_name'] ?: 'Client', ['bold' => true, 'size' => 11]);
        $left->addText((string) ($proposal['attention_name'] ?? ''));
        $left->addText((string) ($proposal['client_mobile'] ?? ''));
        $left->addText((string) ($proposal['client_email'] ?? ''));
        $right = $cover->addCell(4700, ['bgColor' => 'F4F2ED']);
        $right->addText('PROPOSAL DATES', ['size' => 7, 'bold' => true, 'color' => $gold]);
        $right->addText('Issued: '.$this->date($proposal['issued_at'] ?? null));
        $right->addText('Valid until: '.$this->date($proposal['valid_until'] ?? null));
        $right->addText('Status: '.ucfirst($proposal['status'] ?? 'draft'), ['color' => '777777']);
        $section->addTitle('Dear '.($proposal['attention_name'] ?: 'Valued Client').',', 2);
        $section->addText((string) ($proposal['greeting'] ?? ''), [], ['alignment' => Jc::BOTH]);
        $section->addTitle('Event Details', 2);
        $eventTable = $section->addTable('Details');
        $this->addDetailRow($eventTable, 'Event', $proposal['event_type'] ?: 'Catering Function', $green);
        $this->addDetailRow($eventTable, 'Date & Time', $this->dateTime($proposal['event_at'] ?? null), $green);
        $this->addDetailRow($eventTable, 'Venue', $proposal['venue'] ?: 'To be confirmed', $green);
        $this->addDetailRow($eventTable, 'Number of Guests', number_format($proposal['guest_count'] ?? 0).' persons', $green);
        $this->addDetailRow($eventTable, 'Setup', $proposal['setup_description'] ?? '', $green);

        $section->addPageBreak();
        $section->addTitle('Proposed Menu', 1);
        $section->addText('A curated menu prepared for '.($proposal['event_type'] ?: 'your event'), ['color' => '777777']);
        foreach ($sections->take(3) as $menuSection) {
            $this->addMenuSection($section, $menuSection);
        }
        if (! empty($proposal['service_inclusions'])) {
            $section->addTitle('Service Inclusions', 2);
            foreach ($proposal['service_inclusions'] as $line) {
                $section->addListItem($line, 0);
            }
        }

        $section->addPageBreak();
        $section->addTitle('Menu & Investment', 1);
        foreach ($sections->skip(3) as $menuSection) {
            $this->addMenuSection($section, $menuSection);
        }
        $section->addTitle('Pricing', 2);
        $pricing = $section->addTable('Pricing');
        $pricing->addRow(340, ['tblHeader' => true]);
        foreach (['Description', 'Pricing', 'Unit Price', 'Qty / Guests', 'Amount'] as $heading) {
            $pricing->addCell(null, ['bgColor' => $green])->addText($heading, ['bold' => true, 'color' => 'FFFFFF', 'size' => 8]);
        }
        foreach ($pricingItems as $item) {
            $pricing->addRow();
            $pricing->addCell(3200)->addText($item['name'].(! empty($item['description']) ? "\n".$item['description'] : ''));
            $pricing->addCell(1300)->addText(ucwords(str_replace('_', ' ', $item['pricing_type'])));
            $pricing->addCell(1500)->addText($item['is_included'] ? 'Included' : 'SAR '.$this->money($item['price']));
            $pricing->addCell(1300)->addText(number_format($item['pricing_type'] === 'per_person' ? $item['guest_count'] : $item['quantity']));
            $pricing->addCell(1500)->addText('SAR '.$this->money($item['total']));
        }
        $totals = $section->addTable('Details');
        $this->addTotalRow($totals, 'Subtotal', $proposal['sub_total'] ?? 0);
        if (($proposal['discount_amount'] ?? 0) > 0) {
            $this->addTotalRow($totals, 'Discount', -$proposal['discount_amount']);
        }
        $this->addTotalRow($totals, 'VAT ('.($proposal['vat_percent'] ?? 15).'%)', $proposal['tax_amount'] ?? 0);
        $this->addTotalRow($totals, 'Grand Total', $proposal['grand_total'] ?? 0, $gold);

        $section->addPageBreak();
        $section->addTitle('Terms & Acceptance', 1);
        foreach ([
            'Payment Terms'            => $proposal['payment_terms'] ?? '',
            'Changes to Date or Venue' => $proposal['changes_terms'] ?? '',
            'Cancellation'             => $proposal['cancellation_terms'] ?? '',
        ] as $label => $text) {
            $run = $section->addTextRun(['spaceAfter' => 120]);
            $run->addText($label.'. ', ['bold' => true, 'color' => $green]);
            $run->addText($text);
        }
        $section->addTitle('Bank Details', 2);
        $bank = $section->addTable('Details');
        $this->addDetailRow($bank, 'Account Name', $proposal['bank_account_name'] ?? '', $green);
        $this->addDetailRow($bank, 'Bank', $proposal['bank_name'] ?? '', $green);
        $this->addDetailRow($bank, 'IBAN', $proposal['iban'] ?? '', $green);
        $section->addTitle('Approval', 2);
        $section->addText('By signing below, the client confirms acceptance of this proposal, its pricing, and its terms.');
        $signatures = $section->addTable();
        $signatures->addRow();
        foreach ([
            ['For Tiara Catering', $proposal['company_signatory_name'] ?? '', $proposal['company_signatory_title'] ?? ''],
            ['Accepted for the Client', $proposal['client_signatory_name'] ?: $proposal['attention_name'], $proposal['client_signatory_title'] ?: $proposal['client_company']],
        ] as [$label, $name, $title]) {
            $cell = $signatures->addCell(4700);
            $cell->addText($label, ['bold' => true]);
            $cell->addText('________________________________');
            $cell->addText($name);
            $cell->addText($title, ['color' => '777777']);
            $cell->addTextBreak();
            $cell->addText('Date: ____________________');
        }

        return $word;
    }

    private function addHeaderFooter($section, array $company, array $proposal, string $gold, string $green): void
    {
        foreach ([Header::AUTO, Header::EVEN] as $type) {
            $header = $section->addHeader($type);
            $table = $header->addTable();
            $table->addRow();
            $brand = $table->addCell(4700);
            $brand->addText('TIARA CATERING', ['bold' => true, 'size' => 17, 'color' => $gold]);
            $contact = $table->addCell(4700);
            $contact->addText(trim(($company['phone'] ?? '')."\n".($company['email'] ?? '').' · '.($company['website'] ?? '')), ['size' => 7, 'color' => '777777'], ['alignment' => Jc::END]);
            $footer = $section->addFooter($type);
            $footer->addPreserveText(($company['company_name'] ?? 'Tiara Catering').' · '.($proposal['reference'] ?? '').'                                        Page {PAGE} of {NUMPAGES}', ['size' => 7, 'color' => '777777'], ['alignment' => Jc::CENTER]);
        }
    }

    private function addMenuSection($section, array $menuSection): void
    {
        $section->addTitle($menuSection['name'], 2);
        foreach ($menuSection['items'] as $item) {
            $section->addListItem($item['name'].(! empty($item['description']) ? ' — '.$item['description'] : ''), 0);
        }
    }

    private function addDetailRow($table, string $label, string $value, string $green): void
    {
        $table->addRow();
        $table->addCell(2600, ['bgColor' => 'F4F2ED'])->addText($label, ['bold' => true, 'color' => $green]);
        $table->addCell(6800)->addText($value);
    }

    private function addTotalRow($table, string $label, float|int $value, ?string $background = null): void
    {
        $table->addRow();
        $style = $background ? ['bgColor' => $background] : [];
        $font = $background ? ['bold' => true, 'color' => 'FFFFFF'] : [];
        $table->addCell(7000, $style)->addText($label, $font, ['alignment' => Jc::END]);
        $table->addCell(2400, $style)->addText('SAR '.$this->money($value), $font, ['alignment' => Jc::END]);
    }

    private function date(?string $date): string
    {
        return $date ? date('d M Y', strtotime($date)) : '—';
    }

    private function dateTime(?string $date): string
    {
        return $date ? date('d M Y · h:i A', strtotime($date)) : 'To be confirmed';
    }

    private function money(float|int|string|null $value): string
    {
        return number_format((float) $value, 2);
    }
}
