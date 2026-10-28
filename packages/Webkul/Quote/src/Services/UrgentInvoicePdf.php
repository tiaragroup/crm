<?php

namespace Webkul\Quote\Services;

use Mpdf\Mpdf;
use Mpdf\QrCode\QrCode;
use Mpdf\QrCode\Output\Png;
use Webkul\Quote\Models\UrgentTaxInvoice;

class UrgentInvoicePdf
{
    public function render(UrgentTaxInvoice $invoice, bool $sample = false): string
    {
        if ($invoice->snapshot_version !== 1) {
            throw new \DomainException('Unsupported invoice snapshot version.');
        }
        $temp = storage_path('app/private/urgent-invoice-render');
        if (! is_dir($temp) && ! mkdir($temp, 0700, true) && ! is_dir($temp)) {
            throw new \RuntimeException('Cannot create private invoice render directory.');
        }
        $pdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $temp,
            'default_font' => 'dejavusans', 'margin_left' => 12, 'margin_right' => 12,
            'margin_top' => 12, 'margin_bottom' => 18, 'autoScriptToLang' => true, 'autoLangToFont' => true]);
        $qr = (new Png)->output(new QrCode($invoice->qr_payload), 600, [255, 255, 255], [0, 0, 0]);
        $logoPath = public_path('images/tiara-logo.png');
        $logo = is_file($logoPath);
        if ($logo) {
            $pdf->imageVars['tiaraLogo'] = file_get_contents($logoPath);
        }
        $pdf->imageVars['invoiceQr'] = $qr;
        $pdf->SetTitle($invoice->invoice_number.' | Tax Invoice');
        $pdf->SetAuthor($invoice->seller_snapshot['legal_name_en']);
        if ($sample) {
            $pdf->SetWatermarkText('SAMPLE - NOT ISSUED', 0.12);
            $pdf->showWatermarkText = true;
        }
        $pdf->SetHTMLFooter('<div style="text-align:center;font-size:8pt;color:#777">'.$invoice->invoice_number.' • {PAGENO} / {nbpg}</div>');
        $pdf->WriteHTML(view('admin::quotes.urgent-invoice-pdf', compact('invoice', 'logo'))->render());

        return $pdf->Output('', 'S');
    }
}
