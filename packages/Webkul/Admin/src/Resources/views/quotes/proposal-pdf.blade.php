@php
    $company = $snapshot['company'] ?? [];
    $proposal = $snapshot['proposal'] ?? [];
    $sections = collect($snapshot['menu_sections'] ?? []);
    $pricingItems = collect($snapshot['pricing_items'] ?? []);
    $salesContact = $snapshot['sales_contact'] ?? [];
    $salesFooter = collect([
        $salesContact['name'] ?? null,
        $salesContact['email'] ?? null,
        $salesContact['phone'] ?? null,
    ])->filter(fn ($value) => filled($value))->implode('  |  ');
    $serviceInclusions = collect(is_array($proposal['service_inclusions'] ?? null)
        ? $proposal['service_inclusions']
        : preg_split('/\r\n|\r|\n/', (string) ($proposal['service_inclusions'] ?? '')))->filter();
    $gold = $company['primary_color'] ?? '#D9A154';
    $green = $company['secondary_color'] ?? '#346E25';
    $issued = ! empty($proposal['issued_at']) ? \Carbon\Carbon::parse($proposal['issued_at'])->format('d M Y') : '-';
    $valid = ! empty($proposal['valid_until']) ? \Carbon\Carbon::parse($proposal['valid_until'])->format('d M Y') : '-';
    $event = ! empty($proposal['event_at']) ? \Carbon\Carbon::parse($proposal['event_at'])->format('d F Y') : 'To be confirmed';
    $money = fn ($value) => number_format((float) $value, 0);
    $logoRelative = ltrim((string) ($company['logo_path'] ?: 'images/tiara-logo.png'), '/');
    $logoPath = public_path($logoRelative);

    if (! is_file($logoPath)) {
        $logoPath = public_path('images/tiara-logo.png');
    }

    $logoData = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
    $preVatTotal = (float) ($proposal['grand_total'] ?? 0) - (float) ($proposal['tax_amount'] ?? 0);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter portrait; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #242424; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10.5px; line-height: 1.22; }
        .page { position: relative; width: 624px; height: 956px; padding: 44px 96px 56px; page-break-after: always; overflow: hidden; }
        .page:last-child { page-break-after: auto; }
        .micro-header { position: relative; height: 22px; margin-bottom: 20px; border-bottom: 1px solid {{ $gold }}; color: #666; font-size: 7.5px; }
        .micro-header strong { color: {{ $gold }}; }
        .micro-header .right { position: absolute; top: 0; right: 0; }
        .footer { position: absolute; bottom: 22px; left: 96px; right: 96px; padding-top: 6px; border-top: 1px solid #d4d4d4; color: #6d6d6d; font-size: 7px; }
        .footer .right { float: right; }
        .cover-mark { height: 68px; text-align: center; }
        .cover-mark img { width: 82px; height: auto; }
        .proposal-title { margin: 7px 0 1px; color: #222; font-size: 20px; font-weight: bold; text-align: center; text-transform: uppercase; }
        .proposal-subtitle { margin: 0 0 14px; color: #777; font-size: 10px; font-style: italic; text-align: center; }
        .metadata, .details-table, .pricing-table, .bank-table, .signature-table { width: 100%; border-collapse: collapse; }
        .metadata td { width: 33.333%; padding: 6px 8px 5px; border: 1px solid #d4d4d4; background: #f2f3ef; vertical-align: top; }
        .metadata .label { display: block; color: #666; font-size: 7.5px; font-weight: bold; text-transform: uppercase; }
        .metadata .value { display: block; margin-top: 1px; font-size: 10.5px; }
        .section-title { margin: 24px 0 7px; padding-bottom: 4px; border-bottom: 1.2px solid #e9ab00; color: {{ $gold }}; font-size: 14px; font-weight: bold; }
        .section-title.compact { margin-top: 13px; }
        p { margin: 0 0 7px; }
        .details-table td, .bank-table td { padding: 6px 8px; border: 1px solid #d4d4d4; vertical-align: middle; }
        .details-table td:first-child, .bank-table td:first-child { width: 28%; background: #f2f3ef; font-weight: bold; }
        .details-table .tall td { padding-top: 6px; padding-bottom: 6px; }
        .page-two { height: 879px; padding-top: 121px; }
        .page-two .section-title:first-child { margin-top: 0; }
        .menu-box { width: 100%; margin: 0 0 16px; border-collapse: collapse; page-break-inside: avoid; }
        .menu-box th { padding: 5px 8px; border: 1px solid #d4d4d4; background: #f2f3ef; color: {{ $gold }}; font-size: 10px; text-align: left; }
        .menu-box td { padding: 7px 8px; border: 1px solid #d4d4d4; }
        .menu-box .bullet { width: 16px; padding-left: 8px; padding-right: 4px; color: #efaf00; font-weight: bold; text-align: center; }
        .page-three { padding-top: 44px; }
        .page-three .micro-header { margin-bottom: 20px; }
        .pricing-table th { padding: 7px 8px; background: {{ $green }}; color: #fff; font-size: 9px; text-align: left; vertical-align: middle; }
        .pricing-table th.num, .pricing-table td.num { text-align: right; }
        .pricing-table td { padding: 7px 8px; border: 1px solid #d4d4d4; vertical-align: middle; }
        .pricing-table .summary-label { background: #f2f3ef; font-weight: bold; }
        .pricing-table .grand-label { background: {{ $green }}; color: #fff; font-size: 11px; font-weight: bold; }
        .pricing-table .grand-total { background: {{ $green }}; color: {{ $gold }}; font-size: 11px; font-style: italic; font-weight: bold; }
        .rate-note { margin-top: 5px; color: #777; font-size: 8.5px; font-style: italic; }
        .term-label { margin: 0 0 1px; font-weight: bold; }
        .page-four { height: 988px; padding-top: 12px; }
        .page-four .section-title { margin-top: 15px; }
        .bank-table td { padding-top: 6px; padding-bottom: 6px; }
        .bank-table .iban { font-style: italic; }
        .approval-copy { margin-bottom: 10px; }
        .signature-table td { width: 50%; padding-right: 20px; vertical-align: top; }
        .signature-table td:last-child { padding-right: 0; padding-left: 6px; }
        .signature-heading { color: {{ $gold }}; font-weight: bold; }
        .signature-line { height: 18px; margin-bottom: 7px; border-bottom: 1px solid #333; }
        .muted { color: #777; font-size: 8.5px; }
        .section-gap { height: 34px; }
        .section-gap.small { height: 20px; }
    </style>
</head>
<body>
    <section class="page page-one">
        <div class="micro-header"><strong>{{ $company['company_name'] ?? 'Tiara Catering' }}</strong> &nbsp;|&nbsp; {{ $company['tagline'] ?? 'Premier Catering Services - Saudi Arabia' }}<span class="right">{{ $company['proposal_title'] ?? 'Catering Function Proposal' }}</span></div>
        <div class="cover-mark">@if ($logoData)<img src="{{ $logoData }}" alt="Tiara Catering">@endif</div>
        <div class="proposal-title">{{ $company['proposal_title'] ?? 'Catering Function Proposal' }}</div>
        <div class="proposal-subtitle">Signature Catering &amp; Fine Dining Services</div>

        <table class="metadata"><tr>
            <td><span class="label">Proposal Ref.</span><span class="value">{{ $proposal['reference'] ?? 'Draft' }}</span></td>
            <td><span class="label">Date Issued</span><span class="value">{{ $issued }}</span></td>
            <td><span class="label">Valid Until</span><span class="value">{{ $valid }}</span></td>
        </tr></table>

        <div class="section-title">Prepared For</div>
        <table class="details-table">
            <tr><td>Client / Company</td><td>{{ $proposal['client_company'] ?: $proposal['attention_name'] ?: 'Client' }}</td></tr>
            <tr><td>Attention</td><td>{{ $proposal['attention_name'] ?: '-' }}</td></tr>
            <tr><td>Mobile</td><td>{{ $proposal['client_mobile'] ?: '-' }}</td></tr>
            <tr><td>Email</td><td>{{ $proposal['client_email'] ?: '-' }}</td></tr>
        </table>

        <div class="section-gap"></div>
        <div class="section-title compact">Greetings from Tiara Catering</div>
        <p>{{ $proposal['greeting'] }}</p>
        <p>Kindly review the details below and send us your written confirmation of the quoted rates. This proposal is valid until the date shown above; we would appreciate your approval prior to this date to confirm the agreement. We look forward to working with you to make this event a success.</p>

        <div class="section-gap small"></div>
        <div class="section-title compact">Event Information &amp; Details</div>
        <table class="details-table">
            <tr><td>Event Type</td><td>{{ $proposal['event_type'] ?: 'Catering Function' }}</td></tr>
            <tr><td>Date &amp; Time</td><td>{{ $event }}</td></tr>
            <tr><td>Location / Venue</td><td>{{ $proposal['venue'] ?: 'To be confirmed' }}</td></tr>
            <tr class="tall"><td>Set-up Description</td><td>{{ $proposal['setup_description'] ?: '-' }}</td></tr>
            <tr><td>Guest Count</td><td>{{ number_format($proposal['guest_count'] ?? 0) }}</td></tr>
            <tr class="tall"><td>Service Includes</td><td>{{ $serviceInclusions->implode(', ') ?: '-' }}</td></tr>
        </table>

        <div class="footer">{{ $salesFooter }}<span class="right">Page 1 of 4</span></div>
    </section>

    <section class="page page-two">
        <div class="section-title">Proposed Menu</div>
        @forelse ($sections->take(4) as $section)
            <table class="menu-box">
                <thead><tr><th colspan="2">{{ $section['name'] }} ({{ count($section['items'] ?? []) }} Selections)</th></tr></thead>
                <tbody>
                    @foreach (($section['items'] ?? []) as $item)
                        <tr><td class="bullet">&bull;</td><td>{{ $item['name'] }}@if (! empty($item['description'])) - {{ $item['description'] }}@endif</td></tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <table class="menu-box"><thead><tr><th>Menu selections</th></tr></thead><tbody><tr><td>Menu to be confirmed with the client.</td></tr></tbody></table>
        @endforelse
        <div class="footer"><span class="right">Page 2 of 4</span></div>
    </section>

    <section class="page page-three">
        <div class="micro-header"><strong>{{ $company['company_name'] ?? 'Tiara Catering' }}</strong> &nbsp;|&nbsp; {{ $company['tagline'] ?? 'Premier Catering Services - Saudi Arabia' }}<span class="right">{{ $company['proposal_title'] ?? 'Catering Function Proposal' }}</span></div>

        @foreach ($sections->skip(4) as $section)
            <table class="menu-box">
                <thead><tr><th colspan="2">{{ $section['name'] }} ({{ count($section['items'] ?? []) }} Selections)</th></tr></thead>
                <tbody>@foreach (($section['items'] ?? []) as $item)<tr><td class="bullet">&bull;</td><td>{{ $item['name'] }}@if (! empty($item['description'])) - {{ $item['description'] }}@endif</td></tr>@endforeach</tbody>
            </table>
        @endforeach

        @if ($serviceInclusions->isNotEmpty())
            <table class="menu-box"><thead><tr><th colspan="2">Service Inclusions</th></tr></thead><tbody>@foreach ($serviceInclusions as $line)<tr><td class="bullet">&bull;</td><td>{{ $line }}</td></tr>@endforeach</tbody></table>
        @endif

        <div class="section-title compact">Anticipated Catering Investment</div>
        <table class="pricing-table">
            <thead><tr><th style="width:50%">Description</th><th class="num" style="width:17%">Price /<br>Person</th><th class="num" style="width:16%">Guests</th><th class="num" style="width:17%">Amount<br>(SAR)</th></tr></thead>
            <tbody>
                @foreach ($pricingItems as $item)
                    <tr>
                        <td>{{ $item['name'] }}@if (! empty($item['description']))<br>{{ $item['description'] }}@endif</td>
                        <td class="num">{{ $item['is_included'] ? 'Included' : $money($item['price']) }}</td>
                        <td class="num">{{ $item['is_included'] ? '-' : number_format($item['pricing_type'] === 'per_person' ? $item['guest_count'] : $item['quantity']) }}</td>
                        <td class="num">{{ $item['is_included'] ? 'Included' : $money($item['total']) }}</td>
                    </tr>
                @endforeach
                <tr><td class="summary-label" colspan="3">Total before VAT</td><td class="num">{{ $money($preVatTotal) }}</td></tr>
                <tr><td class="summary-label" colspan="3">VAT {{ $money($proposal['vat_percent'] ?? 15) }}%</td><td class="num">{{ $money($proposal['tax_amount'] ?? 0) }}</td></tr>
                <tr><td class="grand-label" colspan="3">TOTAL INCLUDING VAT</td><td class="num grand-total">{{ $money($proposal['grand_total'] ?? 0) }}</td></tr>
            </tbody>
        </table>
        <div class="rate-note">All rates are quoted in Saudi Riyals (SAR). VAT is calculated at {{ $money($proposal['vat_percent'] ?? 15) }}%.</div>

        <div class="section-title compact">Terms &amp; Conditions</div>
        <div class="term-label">Pricing &amp; VAT</div>
        <p>{{ $proposal['pricing_terms'] }}</p>
        <div class="term-label">Invoicing &amp; Payment</div>

        <div class="footer">{{ $salesFooter }}<span class="right">Page 3 of 4</span></div>
    </section>

    <section class="page page-four">
        <p>{{ $proposal['payment_terms'] }}</p>
        <div class="term-label">Changes</div>
        <p>{{ $proposal['changes_terms'] }}</p>
        <div class="term-label">Cancellation</div>
        <p>{{ $proposal['cancellation_terms'] }}</p>

        <div class="section-title">Payment Details</div>
        <table class="bank-table">
            <tr><td>Account Name</td><td>{{ $proposal['bank_account_name'] }}</td></tr>
            <tr><td>Bank</td><td>{{ $proposal['bank_name'] }}</td></tr>
            <tr><td>IBAN</td><td class="iban">{{ $proposal['iban'] }}</td></tr>
        </table>

        <div class="section-title">Acceptance &amp; Approval</div>
        <p class="approval-copy">Once again, thank you for allowing us to be of service to your esteemed guests. We assure you of our full support and effort to make your event a success. To confirm this agreement, please sign below.</p>
        <table class="signature-table">
            <tr>
                <td><div class="signature-heading">For Tiara Catering</div><div class="signature-line"></div><div>{{ $proposal['company_signatory_name'] }}</div><div class="muted">{{ $proposal['company_signatory_title'] }}</div><div class="muted">Signature &amp; Date</div></td>
                <td><div class="signature-heading">Client Approval</div><div class="signature-line"></div><div>{{ $proposal['client_signatory_name'] ?: $proposal['attention_name'] }}</div><div class="muted">{{ $proposal['client_signatory_title'] ?: 'Authorized Signatory' }}</div><div class="muted">Signature &amp; Date</div></td>
            </tr>
        </table>

        <div class="footer"><span class="right">Page 4 of 4</span></div>
    </section>
</body>
</html>
