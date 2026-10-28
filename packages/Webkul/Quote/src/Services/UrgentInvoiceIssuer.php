<?php

namespace Webkul\Quote\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Quote\Models\UrgentTaxInvoice;
use Webkul\Quote\Models\UrgentTaxInvoiceSequence;

class UrgentInvoiceIssuer
{
    public function __construct(
        private UrgentInvoiceAccess $access,
        private UrgentInvoiceSource $source,
        private UrgentInvoiceTotalsValidator $validator,
        private UrgentInvoiceDetails $details,
        private UrgentInvoiceQr $qr,
        private UrgentInvoiceArchive $archive,
    ) {}

    public function issue(int $id, array $input, string $fingerprint): UrgentTaxInvoice
    {
        $this->access->permission('quotes.issue_invoice');
        $invoice = DB::transaction(function () use ($id, $input, $fingerprint) {
            // A retry returns the original document, even after its proposal changes/deletion.
            $existing = UrgentTaxInvoice::where('source_quote_id', $id)->first();
            if ($existing) {
                $this->access->invoice($existing, 'quotes.issue_invoice');

                return $existing;
            }
            abort_unless(config('urgent_invoice.enabled') && config('urgent_invoice.phase_one_reviewed'), 403, 'Issuance is disabled pending company approval.');
            $quote = $this->access->quote($id, 'quotes.issue_invoice', true);
            // Locking read sees a concurrent request committed while we waited for the quote.
            $existing = UrgentTaxInvoice::where('source_quote_id', $id)->lockForUpdate()->first();
            if ($existing) {
                $this->access->invoice($existing, 'quotes.issue_invoice');

                return $existing;
            }
            $preview = $this->source->preview($id, false, true);
            if (! hash_equals($this->details->fingerprint($preview), $fingerprint)) {
                throw new DomainException('Proposal or seller details changed. Review the invoice again.');
            }
            $totals = $this->validator->validate($preview);
            $buyer = $this->details->buyer($input, $preview);
            $seller = $this->details->seller();
            $items = $preview['items'];
            foreach ($items as $index => &$item) {
                $item['name_ar'] = $buyer['item_names_ar'][$index];
            }
            unset($item);
            $sequence = UrgentTaxInvoiceSequence::where('series', 'TI')->lockForUpdate()->firstOrFail();
            $number = (int) $sequence->next_number;
            if ($number < 1 || $number > 9999999999) {
                throw new DomainException('Invoice sequence is exhausted or invalid.');
            }
            $issuedAt = now('UTC')->startOfSecond();
            $invoice = new UrgentTaxInvoice;
            $invoice->forceFill([
                'uuid' => (string) Str::uuid(), 'series' => 'TI', 'sequence_number' => $number,
                'invoice_number' => 'TI-'.str_pad((string) $number, 10, '0', STR_PAD_LEFT),
                'source_quote_id' => $id, 'quote_id' => $id,
                'source_quote_reference' => $preview['source']['reference'],
                'source_quote_revision' => $preview['source']['revision'],
                'source_owner_user_id' => $quote->user_id,
                'invoice_type' => $buyer['invoice_type'], 'issued_at' => $issuedAt,
                'currency_code' => 'SAR', 'snapshot_version' => 1,
                'seller_snapshot' => $seller, 'buyer_snapshot' => $buyer,
                'items_snapshot' => $items, 'totals_snapshot' => $totals,
                'qr_payload' => $this->qr->encode($seller['legal_name_ar'], $seller['vat_number'], $issuedAt->format('Y-m-d\TH:i:s\Z'), $totals['grand_total'], $totals['vat_amount']),
                'archive_status' => 'pending',
                'issued_by_user_id' => auth()->guard('user')->id(),
                'issued_by_name' => auth()->guard('user')->user()->name,
            ]);
            $invoice->content_sha256 = $invoice->contentHash();
            $invoice->save();
            $sequence->next_number = $number + 1;
            $sequence->save();

            return $invoice;
        }, 3);

        // PDF failure retains the allocated number and snapshots; retry archives the same invoice.
        return $this->archive->store($invoice);
    }
}
