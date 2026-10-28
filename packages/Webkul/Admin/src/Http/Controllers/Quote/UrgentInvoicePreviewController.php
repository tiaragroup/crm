<?php

namespace Webkul\Admin\Http\Controllers\Quote;

use DomainException;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Quote\Models\UrgentTaxInvoice;
use Webkul\Quote\Services\UrgentInvoiceAccess;
use Webkul\Quote\Services\UrgentInvoiceArchive;
use Webkul\Quote\Services\UrgentInvoiceDetails;
use Webkul\Quote\Services\UrgentInvoiceIssuer;
use Webkul\Quote\Services\UrgentInvoiceSource;
use Webkul\Quote\Services\UrgentInvoiceTotalsValidator;

class UrgentInvoicePreviewController extends Controller
{
    public function __construct(private UrgentInvoiceAccess $access, private UrgentInvoiceSource $source, private UrgentInvoiceTotalsValidator $validator, private UrgentInvoiceDetails $details) {}

    public function show(int $id) { return $this->page($id); }

    public function review(Request $request, int $id)
    {
        abort_unless(config('urgent_invoice.preview_enabled'), 404);
        $this->access->quote($id);
        $preview = $this->source->preview($id, true);
        try {
            $this->validator->validate($preview);
            $this->details->seller();
            $buyer = $this->details->buyer($request->all(), $preview);
        } catch (DomainException $e) {
            return back()->withErrors(['pricing' => $e->getMessage()])->withInput();
        }
        $request->session()->put('urgent_invoice_review.'.$id, ['buyer' => $buyer, 'fingerprint' => $this->details->fingerprint($preview), 'expires_at' => now()->addMinutes(30)->timestamp]);
        return $this->page($id, $buyer);
    }

    private function page(int $id, ?array $reviewedBuyer = null)
    {
        abort_unless(config('urgent_invoice.preview_enabled'), 404);
        $quote = $this->access->quote($id);
        $preview = $this->source->preview($id, true);
        $totals = null;
        $pricingError = null;
        try { $totals = $this->validator->validate($preview); }
        catch (DomainException $e) { $pricingError = $e->getMessage(); }
        $existingInvoice = UrgentTaxInvoice::where('source_quote_id', $id)->first();
        $sellerErrors = [];
        try { $this->details->seller(); }
        catch (\Illuminate\Validation\ValidationException $e) { $sellerErrors = $e->validator->errors()->all(); }
        return view('admin::quotes.urgent-invoice-review', compact('quote', 'preview', 'totals', 'pricingError', 'reviewedBuyer', 'sellerErrors', 'existingInvoice'));
    }

    public function issue(Request $request, int $id, UrgentInvoiceIssuer $issuer)
    {
        $this->access->permission('quotes.issue_invoice');
        $request->validate(['confirm_issue' => ['required', 'accepted']]);
        $review = $request->session()->get('urgent_invoice_review.'.$id);
        $existing = UrgentTaxInvoice::where('source_quote_id', $id)->first();
        abort_unless($existing || ($review && $review['expires_at'] >= now()->timestamp), 422, 'Review expired. Review the invoice again.');
        try { $invoice = $issuer->issue($id, $review['buyer'] ?? [], $review['fingerprint'] ?? ''); }
        catch (DomainException $e) { return back()->withErrors(['invoice' => $e->getMessage()]); }
        $request->session()->forget('urgent_invoice_review.'.$id);
        return redirect()->route('admin.quotes.urgent_invoice.download', $invoice->uuid);
    }

    public function download(string $uuid, UrgentInvoiceArchive $archive)
    {
        $this->access->permission('quotes.print');
        $invoice = UrgentTaxInvoice::where('uuid', $uuid)->firstOrFail();
        $this->access->invoice($invoice);
        try { $bytes = $archive->bytes($invoice); }
        catch (DomainException $e) { abort(409, $e->getMessage()); }
        return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$invoice->invoice_number.'.pdf"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
