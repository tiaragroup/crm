<!DOCTYPE html>
<html lang="ar"><head><meta charset="UTF-8"><style>
body {font-family: dejavusans; font-size: 9pt; color: #26323e;} h1 {font-size:18pt; color:#9c713c;} h2 {font-size:11pt;color:#9c713c;margin:4mm 0 2mm;}
table {width:100%;border-collapse:collapse;} td, th {padding:2mm;vertical-align:top;} .lines th {background:#9c713c;color:white;font-size:8pt;} .lines td {border-bottom:1px solid #ddd;} .amount {text-align:right;white-space:nowrap;} .ar {direction:rtl;text-align:right;} .muted {color:#65717a;} .box {background:#f6f3ee;} .total {background:#9c713c;color:white;font-size:13pt;} .small {font-size:8pt;}
.total td {color:#ffffff;}
</style></head><body>
@php
    $s = $invoice->seller_snapshot; $b = $invoice->buyer_snapshot; $t = $invoice->totals_snapshot;
    $issued = \Carbon\CarbonImmutable::parse($invoice->getAttributes()['issued_at'], 'UTC')->setTimezone('Asia/Riyadh');
    $money = fn ($cents) => intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    $cents = fn ($value) => (int) explode('.', $value)[0] * 100 + (int) str_pad(substr(explode('.', $value)[1] ?? '', 0, 2), 2, '0');
@endphp
<table><tr><td width="24%">@if($logo)<img src="var:tiaraLogo" style="width:34mm">@endif</td><td style="text-align:right">
<h1>{{ $invoice->invoice_type === 'simplified' ? 'فاتورة ضريبية مبسطة' : 'فاتورة ضريبية' }}</h1>
<strong>{{ $invoice->invoice_type === 'simplified' ? 'Simplified Tax Invoice' : 'Tax Invoice' }}</strong><br>{{ $invoice->invoice_number }}
</td></tr></table>
<table class="box"><tr><td>Issue date / تاريخ الإصدار<br><strong>{{ $issued->format('Y-m-d H:i:s') }} +03:00</strong></td><td>Supply date / تاريخ التوريد<br><strong>{{ $b['supply_date'] }}</strong></td><td>Proposal / العرض<br><strong>{{ $invoice->source_quote_reference }} / {{ $invoice->source_quote_revision }}</strong></td></tr></table>
<table><tr><td width="50%"><h2>Seller / البائع</h2><div class="ar">{{ $s['legal_name_ar'] }}</div><strong>{{ $s['legal_name_en'] }}</strong><br>
<div class="ar">{{ $s['address_ar'] }}</div>{{ $s['address_en'] }}<br>
{{ $s['building'] }}, {{ $s['street'] }}, {{ $s['district'] }}, {{ $s['city'] }}, {{ $s['postal_code'] }}, SA<br>
VAT / الرقم الضريبي: {{ $s['vat_number'] }}<br>CR / السجل التجاري: {{ $s['cr_number'] }}
</td><td><h2>Buyer / المشتري</h2><div class="ar">{{ $b['buyer_name_ar'] }}</div><strong>{{ $b['buyer_name'] }}</strong><br>
<div class="ar">{{ $b['buyer_address_ar'] ?? '' }}</div>{{ $b['buyer_address'] ?? '' }}<br>{{ $b['buyer_city'] ?? '' }}<br>
@if($b['buyer_vat_registered']) VAT / الرقم الضريبي: {{ $b['buyer_vat_number'] }} @endif
</td></tr></table>
<h2>Invoice items / بنود الفاتورة</h2>
<table class="lines"><thead><tr><th>Description<br>الوصف</th><th>Qty<br>الكمية</th><th>Unit price<br>سعر الوحدة</th><th>Discount<br>الخصم</th><th>Taxable<br>المبلغ الخاضع</th><th>VAT rate<br>نسبة الضريبة</th></tr></thead><tbody>
@foreach($invoice->items_snapshot as $item)
<tr><td><div class="ar">{{ $item['name_ar'] }}</div>{{ $item['name'] }}</td><td class="amount">{{ $item['quantity'] }}</td><td class="amount">{{ $money($cents($item['unit_price'])) }}</td><td class="amount">{{ $money($cents($item['discount_amount'] ?: '0')) }}</td><td class="amount">{{ $money($cents($item['line_total']) - $cents($item['discount_amount'] ?: '0')) }}</td><td class="amount">15%</td></tr>
@endforeach
</tbody></table>
<table style="margin-top:5mm"><tr><td width="40%"><img src="var:invoiceQr" style="width:45mm;height:45mm"><br><span class="small">SAR / جميع المبالغ بالريال السعودي</span></td><td>
<table><tr><td>Subtotal / الإجمالي قبل الخصم</td><td class="amount">{{ $t['subtotal'] }}</td></tr><tr><td>Discount / الخصم</td><td class="amount">{{ $t['discount'] }}</td></tr><tr><td>Taxable amount / الخاضع للضريبة</td><td class="amount">{{ $t['taxable_amount'] }}</td></tr><tr><td>VAT 15% / ضريبة القيمة المضافة</td><td class="amount">{{ $t['vat_amount'] }}</td></tr><tr class="total"><td>Total / الإجمالي</td><td class="amount">{{ $t['grand_total'] }}</td></tr></table>
</td></tr></table>
<div class="box" style="padding:3mm"><strong>Payment at issuance / الدفع عند الإصدار</strong><br>
{{ ['bank_transfer' => 'Bank transfer / تحويل بنكي', 'cash' => 'Cash / نقداً', 'card' => 'Card / بطاقة'][$b['payment_method']] }} — {{ $b['payment_status'] === 'received' ? 'Confirmed received in full / تم تأكيد الاستلام بالكامل' : 'Payment pending / بانتظار الدفع' }}
@if($b['payment_status'] === 'received')<br>Reference / المرجع: {{ $b['transaction_reference'] }}<br>Received at / تاريخ الاستلام: {{ $b['payment_received_at'] }} (Asia/Riyadh)@endif
</div>
</body></html>
