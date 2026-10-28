<?php

namespace Webkul\Quote\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Quote\Models\UrgentTaxInvoice;

class UrgentInvoiceArchive
{
    public function __construct(private UrgentInvoicePdf $pdf) {}

    public function store(UrgentTaxInvoice $invoice): UrgentTaxInvoice
    {
        try {
            return DB::transaction(function () use ($invoice) {
                $locked = UrgentTaxInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                $this->verifyContent($locked);
                if ($locked->archive_status === 'ready') {
                    $this->bytes($locked);

                    return $locked;
                }
                $bytes = $this->pdf->render($locked);
                $path = $locked->uuid.'/invoice.pdf';
                $disk = Storage::disk('urgent_invoices');
                $disk->put($path, $bytes);
                $hash = hash('sha256', $bytes);
                if (! hash_equals($hash, hash('sha256', $disk->get($path)))) {
                    throw new DomainException('Invoice archive verification failed.');
                }
                $locked->forceFill(['pdf_path' => $path, 'pdf_sha256' => $hash, 'archive_status' => 'ready', 'archived_at' => now('UTC')])->save();

                return $locked;
            }, 3);
        } catch (\Throwable $exception) {
            UrgentTaxInvoice::whereKey($invoice->id)->where('archive_status', '!=', 'ready')->update(['archive_status' => 'failed']);
            report($exception);
            throw new DomainException('Invoice '.$invoice->invoice_number.' is retained, but its archive is unavailable. Retry the same invoice; do not create another proposal to replace it.', 0, $exception);
        }
    }

    public function verifyContent(UrgentTaxInvoice $invoice): void
    {
        if (! $invoice->content_sha256 || ! hash_equals($invoice->content_sha256, $invoice->contentHash())) {
            throw new DomainException('Invoice snapshot integrity check failed.');
        }
    }

    public function bytes(UrgentTaxInvoice $invoice): string
    {
        $this->verifyContent($invoice);
        if ($invoice->archive_status !== 'ready' || $invoice->pdf_path !== $invoice->uuid.'/invoice.pdf') {
            throw new DomainException('Invoice archive is not ready.');
        }
        $disk = Storage::disk('urgent_invoices');
        if (! $disk->exists($invoice->pdf_path)) {
            throw new DomainException('Invoice archive is missing; restore the original from backup.');
        }
        $bytes = $disk->get($invoice->pdf_path);
        if (! hash_equals($invoice->pdf_sha256 ?? '', hash('sha256', $bytes))) {
            throw new DomainException('Invoice archive integrity check failed; restore the original from backup.');
        }

        return $bytes;
    }
}
