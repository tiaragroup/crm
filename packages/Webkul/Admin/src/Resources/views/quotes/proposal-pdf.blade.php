@php
    $locale = $locale ?? 'en';
    $isArabic = $locale === 'ar';
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
    $localValue = fn (array $data, string $key, $fallback = null) => $isArabic
        ? ($data[$key.'_ar'] ?? $data[$key] ?? $fallback)
        : ($data[$key] ?? $fallback);
    $serviceValue = $isArabic
        ? ($proposal['service_inclusions_ar'] ?? $proposal['service_inclusions'] ?? [])
        : ($proposal['service_inclusions'] ?? []);
    $serviceInclusions = collect(is_array($serviceValue)
        ? $serviceValue
        : preg_split('/\r\n|\r|\n/', (string) $serviceValue))->filter();
    $gold = $company['primary_color'] ?? '#D9A154';
    $green = $company['secondary_color'] ?? '#346E25';
    $dateFormat = $isArabic ? 'd F Y' : 'd M Y';
    $issued = ! empty($proposal['issued_at']) ? \Carbon\Carbon::parse($proposal['issued_at'])->locale($locale)->translatedFormat($dateFormat) : '-';
    $valid = ! empty($proposal['valid_until']) ? \Carbon\Carbon::parse($proposal['valid_until'])->locale($locale)->translatedFormat($dateFormat) : '-';
    $event = ! empty($proposal['event_at']) ? \Carbon\Carbon::parse($proposal['event_at'])->locale($locale)->translatedFormat('d F Y') : ($isArabic ? 'يحدد لاحقاً' : 'To be confirmed');
    $money = fn ($value) => number_format((float) $value, 0);
    $logoRelative = ltrim((string) ($company['logo_path'] ?: 'images/tiara-logo.png'), '/');
    $logoPath = public_path($logoRelative);

    if (! is_file($logoPath)) {
        $logoPath = public_path('images/tiara-logo.png');
    }

    $logoData = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
    $preVatTotal = (float) ($proposal['grand_total'] ?? 0) - (float) ($proposal['tax_amount'] ?? 0);
    $labels = $isArabic ? [
        'subtitle' => 'خدمات الضيافة الراقية والمطاعم الفاخرة', 'reference' => 'رقم العرض', 'draft' => 'مسودة',
        'issued' => 'تاريخ الإصدار', 'valid' => 'صالح حتى', 'prepared_for' => 'أعد هذا العرض إلى',
        'client_company' => 'العميل / الشركة', 'attention' => 'عناية', 'mobile' => 'الجوال', 'email' => 'البريد الإلكتروني',
        'greetings' => 'تحياتنا من تيارا للضيافة', 'event_details' => 'معلومات وتفاصيل الفعالية',
        'event_type' => 'نوع الفعالية', 'date_time' => 'التاريخ والوقت', 'venue' => 'الموقع / القاعة',
        'setup' => 'وصف التجهيز', 'guests' => 'عدد الضيوف', 'service' => 'الخدمات المشمولة',
        'catering_function' => 'فعالية ضيافة', 'tbc' => 'يحدد لاحقاً', 'menu' => 'القائمة المقترحة',
        'selections' => 'اختيارات', 'menu_selections' => 'اختيارات القائمة', 'menu_pending' => 'سيتم تأكيد القائمة مع العميل.',
        'service_inclusions' => 'الخدمات المشمولة', 'investment' => 'التكلفة المتوقعة لخدمات الضيافة',
        'description' => 'الوصف', 'price_person' => 'السعر / للفرد', 'guests_short' => 'الضيوف', 'amount_sar' => 'المبلغ (ر.س)',
        'included' => 'مشمول', 'before_vat' => 'الإجمالي قبل الضريبة', 'vat' => 'ضريبة القيمة المضافة',
        'total_vat' => 'الإجمالي شامل الضريبة', 'terms' => 'الشروط والأحكام', 'pricing_vat' => 'الأسعار وضريبة القيمة المضافة',
        'invoicing' => 'الفوترة والدفع', 'changes' => 'التعديلات', 'cancellation' => 'الإلغاء',
        'payment_details' => 'تفاصيل الدفع', 'account_name' => 'اسم الحساب', 'bank' => 'البنك', 'iban' => 'رقم الآيبان',
        'acceptance' => 'القبول والموافقة', 'for_tiara' => 'عن تيارا للضيافة', 'client_approval' => 'موافقة العميل',
        'signature_date' => 'التوقيع والتاريخ', 'authorized' => 'المفوض بالتوقيع', 'page' => 'صفحة', 'of' => 'من',
    ] : [
        'subtitle' => 'Signature Catering & Fine Dining Services', 'reference' => 'Proposal Ref.', 'draft' => 'Draft',
        'issued' => 'Date Issued', 'valid' => 'Valid Until', 'prepared_for' => 'Prepared For',
        'client_company' => 'Client / Company', 'attention' => 'Attention', 'mobile' => 'Mobile', 'email' => 'Email',
        'greetings' => 'Greetings from Tiara Catering', 'event_details' => 'Event Information & Details',
        'event_type' => 'Event Type', 'date_time' => 'Date & Time', 'venue' => 'Location / Venue',
        'setup' => 'Set-up Description', 'guests' => 'Guest Count', 'service' => 'Service Includes',
        'catering_function' => 'Catering Function', 'tbc' => 'To be confirmed', 'menu' => 'Proposed Menu',
        'selections' => 'Selections', 'menu_selections' => 'Menu selections', 'menu_pending' => 'Menu to be confirmed with the client.',
        'service_inclusions' => 'Service Inclusions', 'investment' => 'Anticipated Catering Investment',
        'description' => 'Description', 'price_person' => 'Price / Person', 'guests_short' => 'Guests', 'amount_sar' => 'Amount (SAR)',
        'included' => 'Included', 'before_vat' => 'Total before VAT', 'vat' => 'VAT', 'total_vat' => 'TOTAL INCLUDING VAT',
        'terms' => 'Terms & Conditions', 'pricing_vat' => 'Pricing & VAT', 'invoicing' => 'Invoicing & Payment',
        'changes' => 'Changes', 'cancellation' => 'Cancellation', 'payment_details' => 'Payment Details',
        'account_name' => 'Account Name', 'bank' => 'Bank', 'iban' => 'IBAN', 'acceptance' => 'Acceptance & Approval',
        'for_tiara' => 'For Tiara Catering', 'client_approval' => 'Client Approval', 'signature_date' => 'Signature & Date',
        'authorized' => 'Authorized Signatory', 'page' => 'Page', 'of' => 'of',
    ];
    $companyName = $localValue($company, 'company_name', 'Tiara Catering');
    $tagline = $localValue($company, 'tagline', 'Premier Catering Services - Saudi Arabia');
    $proposalTitle = $localValue($company, 'proposal_title', 'Catering Function Proposal');
    $bankAccountName = $isArabic ? ($proposal['bank_account_name_ar'] ?? 'تيارا للضيافة') : ($proposal['bank_account_name'] ?? '');
    $bankName = $isArabic
        ? ($proposal['bank_name_ar'] ?? (($proposal['bank_name'] ?? '') === 'Al Rajhi Bank' ? 'مصرف الراجحي' : ($proposal['bank_name'] ?? '')))
        : ($proposal['bank_name'] ?? '');
    $companySignatoryTitle = $isArabic ? 'مجموعة تيارا / تيارا للضيافة' : ($proposal['company_signatory_title'] ?? '');
    $clientSignatoryTitle = $isArabic
        ? (($proposal['client_signatory_title_ar'] ?? null) ?: (($proposal['client_signatory_title'] ?? '') === 'Client' ? 'العميل' : $labels['authorized']))
        : (($proposal['client_signatory_title'] ?? null) ?: $labels['authorized']);
    $arabicGreeting = 'نشكركم على اهتمامكم بخدمات تيارا للضيافة لتنظيم فعاليتكم القادمة. يسرنا أن نقدم لكم هذا العرض بناءً على متطلبات الضيافة الخاصة بكم.';
    $arabicConfirmation = 'يرجى التكرم بمراجعة التفاصيل أدناه وإرسال موافقتكم الخطية على الأسعار المقدمة قبل تاريخ انتهاء صلاحية العرض. نتطلع للعمل معكم لإنجاح هذه الفعالية.';
    $pricingTerms = $isArabic ? 'جميع الأسعار والمبالغ بالريال السعودي، وتطبق ضريبة القيمة المضافة بنسبة 15% حسب الجدول الموضح.' : ($proposal['pricing_terms'] ?? '');
    $paymentTerms = $isArabic ? 'يلزم سداد كامل قيمة العرض مقدماً عند القبول والموافقة، وسيتم إصدار الفاتورة قبل موعد الفعالية.' : ($proposal['payment_terms'] ?? '');
    $changesTerms = $isArabic ? 'أي تغيير في تاريخ الفعالية أو موقعها يخضع لتوفر تيارا للضيافة، وقد تترتب عليه تعديلات في التكلفة.' : ($proposal['changes_terms'] ?? '');
    $cancellationTerms = $isArabic ? 'عند إلغاء الفعالية قبل ستة أيام تستحق نسبة 50% من إجمالي قيمة العرض شامل الضريبة. وعند الإلغاء قبل خمسة أيام أو أقل تستحق كامل قيمة العرض.' : ($proposal['cancellation_terms'] ?? '');
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        @if (! $isArabic)
            @page { size: letter portrait; margin: 0; }
        @endif
        * { box-sizing: border-box; }
        body { margin: 0; color: #242424; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10.5px; line-height: 1.22; }
        .page { position: relative; width: 624px; height: 956px; padding: 44px 96px 56px; page-break-after: {{ $isArabic ? 'auto' : 'always' }}; overflow: hidden; }
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
        body.rtl { direction: rtl; text-align: right; font-family: DejaVu Sans, sans-serif; }
        body.rtl .page { position: static; width: auto; height: auto; padding: 44px 96px 56px; overflow: visible; page-break-after: auto; }
        body.rtl .page-two { height: auto; padding-top: 121px; }
        body.rtl .page-three { padding-top: 44px; }
        body.rtl .page-four { height: auto; padding-top: 12px; }
        body.rtl .footer { position: static; margin-top: 24px; }
        body.rtl .micro-header .right { right: auto; left: 0; }
        body.rtl .footer .right { float: left; }
        body.rtl .menu-box th, body.rtl .pricing-table th { text-align: right; }
        body.rtl .pricing-table th.num, body.rtl .pricing-table td.num { text-align: left; }
        body.rtl .details-table td:first-child, body.rtl .bank-table td:first-child { width: 32%; }
        body.rtl .signature-table td { padding-right: 0; padding-left: 20px; }
        body.rtl .signature-table td:last-child { padding-left: 0; padding-right: 6px; }
    </style>
</head>
<body class="{{ $isArabic ? 'rtl' : 'ltr' }}">
    <section class="page page-one">
        <div class="micro-header"><strong>{{ $companyName }}</strong> &nbsp;|&nbsp; {{ $tagline }}<span class="right">{{ $proposalTitle }}</span></div>
        <div class="cover-mark">@if ($logoData)<img src="{{ $logoData }}" alt="Tiara Catering">@endif</div>
        <div class="proposal-title">{{ $proposalTitle }}</div>
        <div class="proposal-subtitle">{{ $labels['subtitle'] }}</div>

        <table class="metadata"><tr>
            <td><span class="label">{{ $labels['reference'] }}</span><span class="value">{{ $proposal['reference'] ?? $labels['draft'] }}</span></td>
            <td><span class="label">{{ $labels['issued'] }}</span><span class="value">{{ $issued }}</span></td>
            <td><span class="label">{{ $labels['valid'] }}</span><span class="value">{{ $valid }}</span></td>
        </tr></table>

        <div class="section-title">{{ $labels['prepared_for'] }}</div>
        <table class="details-table">
            <tr><td>{{ $labels['client_company'] }}</td><td>{{ $proposal['client_company'] ?: $proposal['attention_name'] ?: ($isArabic ? 'العميل' : 'Client') }}</td></tr>
            <tr><td>{{ $labels['attention'] }}</td><td>{{ $proposal['attention_name'] ?: '-' }}</td></tr>
            <tr><td>{{ $labels['mobile'] }}</td><td dir="ltr">{{ $proposal['client_mobile'] ?: '-' }}</td></tr>
            <tr><td>{{ $labels['email'] }}</td><td dir="ltr">{{ $proposal['client_email'] ?: '-' }}</td></tr>
        </table>

        <div class="section-gap"></div>
        <div class="section-title compact">{{ $labels['greetings'] }}</div>
        <p>{{ $isArabic ? $arabicGreeting : $proposal['greeting'] }}</p>
        <p>{{ $isArabic ? $arabicConfirmation : 'Kindly review the details below and send us your written confirmation of the quoted rates. This proposal is valid until the date shown above; we would appreciate your approval prior to this date to confirm the agreement. We look forward to working with you to make this event a success.' }}</p>

        <div class="section-gap small"></div>
        <div class="section-title compact">{{ $labels['event_details'] }}</div>
        <table class="details-table">
            <tr><td>{{ $labels['event_type'] }}</td><td>{{ $localValue($proposal, 'event_type', $labels['catering_function']) }}</td></tr>
            <tr><td>{{ $labels['date_time'] }}</td><td>{{ $event }}</td></tr>
            <tr><td>{{ $labels['venue'] }}</td><td>{{ $localValue($proposal, 'venue', $labels['tbc']) }}</td></tr>
            <tr class="tall"><td>{{ $labels['setup'] }}</td><td>{{ $localValue($proposal, 'setup_description', '-') }}</td></tr>
            <tr><td>{{ $labels['guests'] }}</td><td>{{ number_format($proposal['guest_count'] ?? 0) }}</td></tr>
            <tr class="tall"><td>{{ $labels['service'] }}</td><td>{{ $serviceInclusions->implode($isArabic ? '، ' : ', ') ?: '-' }}</td></tr>
        </table>

        <div class="footer">{{ $salesFooter }}<span class="right">{{ $labels['page'] }} 1 {{ $labels['of'] }} 4</span></div>
    </section>
    @if ($isArabic)<pagebreak />@endif
    <section class="page page-two">
        <div class="section-title">{{ $labels['menu'] }}</div>
        @forelse ($sections->take(4) as $section)
            <table class="menu-box">
                <thead><tr><th colspan="2">{{ $localValue($section, 'name') }} ({{ count($section['items'] ?? []) }} {{ $labels['selections'] }})</th></tr></thead>
                <tbody>
                    @foreach (($section['items'] ?? []) as $item)
                        @php($itemDescription = $localValue($item, 'description'))
                        <tr><td class="bullet">&bull;</td><td>{{ $localValue($item, 'name') }}@if ($itemDescription) {{ $isArabic ? ' - ' : ' - ' }}{{ $itemDescription }}@endif</td></tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <table class="menu-box"><thead><tr><th>{{ $labels['menu_selections'] }}</th></tr></thead><tbody><tr><td>{{ $labels['menu_pending'] }}</td></tr></tbody></table>
        @endforelse
        <div class="footer"><span class="right">{{ $labels['page'] }} 2 {{ $labels['of'] }} 4</span></div>
    </section>
    @if ($isArabic)<pagebreak />@endif
    <section class="page page-three">
        <div class="micro-header"><strong>{{ $companyName }}</strong> &nbsp;|&nbsp; {{ $tagline }}<span class="right">{{ $proposalTitle }}</span></div>

        @foreach ($sections->skip(4) as $section)
            <table class="menu-box">
                <thead><tr><th colspan="2">{{ $localValue($section, 'name') }} ({{ count($section['items'] ?? []) }} {{ $labels['selections'] }})</th></tr></thead>
                <tbody>@foreach (($section['items'] ?? []) as $item) @php($itemDescription = $localValue($item, 'description')) <tr><td class="bullet">&bull;</td><td>{{ $localValue($item, 'name') }}@if ($itemDescription) - {{ $itemDescription }}@endif</td></tr>@endforeach</tbody>
            </table>
        @endforeach

        @if ($serviceInclusions->isNotEmpty())
            <table class="menu-box"><thead><tr><th colspan="2">{{ $labels['service_inclusions'] }}</th></tr></thead><tbody>@foreach ($serviceInclusions as $line)<tr><td class="bullet">&bull;</td><td>{{ $line }}</td></tr>@endforeach</tbody></table>
        @endif

        <div class="section-title compact">{{ $labels['investment'] }}</div>
        <table class="pricing-table">
            <thead><tr><th style="width:50%">{{ $labels['description'] }}</th><th class="num" style="width:17%">{{ $labels['price_person'] }}</th><th class="num" style="width:16%">{{ $labels['guests_short'] }}</th><th class="num" style="width:17%">{{ $labels['amount_sar'] }}</th></tr></thead>
            <tbody>
                @foreach ($pricingItems as $item)
                    @php($pricingDescription = $localValue($item, 'description'))
                    <tr>
                        <td>{{ $localValue($item, 'name') }}@if ($pricingDescription)<br>{{ $pricingDescription }}@endif</td>
                        <td class="num">{{ $item['is_included'] ? $labels['included'] : $money($item['price']) }}</td>
                        <td class="num">{{ $item['is_included'] ? '-' : number_format($item['pricing_type'] === 'per_person' ? $item['guest_count'] : $item['quantity']) }}</td>
                        <td class="num">{{ $item['is_included'] ? $labels['included'] : $money($item['total']) }}</td>
                    </tr>
                @endforeach
                <tr><td class="summary-label" colspan="3">{{ $labels['before_vat'] }}</td><td class="num">{{ $money($preVatTotal) }}</td></tr>
                <tr><td class="summary-label" colspan="3">{{ $labels['vat'] }} {{ $money($proposal['vat_percent'] ?? 15) }}%</td><td class="num">{{ $money($proposal['tax_amount'] ?? 0) }}</td></tr>
                <tr><td class="grand-label" colspan="3">{{ $labels['total_vat'] }}</td><td class="num grand-total">{{ $money($proposal['grand_total'] ?? 0) }}</td></tr>
            </tbody>
        </table>
        <div class="rate-note">{{ $isArabic ? 'جميع الأسعار بالريال السعودي. تحتسب ضريبة القيمة المضافة بنسبة '.$money($proposal['vat_percent'] ?? 15).'٪.' : 'All rates are quoted in Saudi Riyals (SAR). VAT is calculated at '.$money($proposal['vat_percent'] ?? 15).'%. ' }}</div>

        <div class="section-title compact">{{ $labels['terms'] }}</div>
        <div class="term-label">{{ $labels['pricing_vat'] }}</div>
        <p>{{ $pricingTerms }}</p>
        <div class="term-label">{{ $labels['invoicing'] }}</div>

        <div class="footer">{{ $salesFooter }}<span class="right">{{ $labels['page'] }} 3 {{ $labels['of'] }} 4</span></div>
    </section>
    @if ($isArabic)<pagebreak />@endif
    <section class="page page-four">
        <p>{{ $paymentTerms }}</p>
        <div class="term-label">{{ $labels['changes'] }}</div>
        <p>{{ $changesTerms }}</p>
        <div class="term-label">{{ $labels['cancellation'] }}</div>
        <p>{{ $cancellationTerms }}</p>

        <div class="section-title">{{ $labels['payment_details'] }}</div>
        <table class="bank-table">
            <tr><td>{{ $labels['account_name'] }}</td><td>{{ $bankAccountName }}</td></tr>
            <tr><td>{{ $labels['bank'] }}</td><td>{{ $bankName }}</td></tr>
            <tr><td>{{ $labels['iban'] }}</td><td class="iban">{{ $proposal['iban'] }}</td></tr>
        </table>

        <div class="section-title">{{ $labels['acceptance'] }}</div>
        <p class="approval-copy">{{ $isArabic ? 'نشكركم مجدداً لإتاحة الفرصة لنا لخدمة ضيوفكم الكرام. نؤكد لكم كامل دعمنا وحرصنا على إنجاح فعاليتكم. لتأكيد الاتفاق، يرجى التوقيع أدناه.' : 'Once again, thank you for allowing us to be of service to your esteemed guests. We assure you of our full support and effort to make your event a success. To confirm this agreement, please sign below.' }}</p>
        <table class="signature-table">
            <tr>
                <td><div class="signature-heading">{{ $labels['for_tiara'] }}</div><div class="signature-line"></div><div>{{ $proposal['company_signatory_name'] }}</div><div class="muted">{{ $companySignatoryTitle }}</div><div class="muted">{{ $labels['signature_date'] }}</div></td>
                <td><div class="signature-heading">{{ $labels['client_approval'] }}</div><div class="signature-line"></div><div>{{ $proposal['client_signatory_name'] ?: $proposal['attention_name'] }}</div><div class="muted">{{ $clientSignatoryTitle }}</div><div class="muted">{{ $labels['signature_date'] }}</div></td>
            </tr>
        </table>

        <div class="footer"><span class="right">{{ $labels['page'] }} 4 {{ $labels['of'] }} 4</span></div>
    </section>
</body>
</html>
