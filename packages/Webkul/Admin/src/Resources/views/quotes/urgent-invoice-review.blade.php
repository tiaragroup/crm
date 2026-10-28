
<x-admin::layouts>
    <x-slot:title>
        Tax Invoice Review
    </x-slot:title>

    @php
        $inputClass = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:bg-gray-900 dark:text-white';
        $buyer = $preview['buyer'];
        $values = $reviewedBuyer ?? [];
    @endphp

    <div class="flex flex-col gap-5">
        <div class="rounded-lg bg-white p-5 dark:bg-gray-900">
            <h1 class="text-xl font-bold">
                مراجعة الفاتورة الضريبية / Tax Invoice Review
            </h1>

            <p class="mt-2 text-sm text-gray-500">
                Proposal: {{ $preview['source']['reference'] }}
                — Status: {{ $quote->status }}
            </p>

            <p class="mt-2 text-sm font-semibold text-amber-700">
                INTERNAL REVIEW ONLY — NOT AN ISSUED TAX INVOICE
            </p>
            @if (! config('urgent_invoice.enabled') || ! config('urgent_invoice.phase_one_reviewed'))
                <p class="mt-2 text-amber-700">Issuance disabled pending company review / الإصدار معطل حتى اعتماد الشركة</p>
            @endif
            @if ($existingInvoice)
                <p class="mt-3">Invoice / الفاتورة: {{ $existingInvoice->invoice_number }}</p>
                @if ($existingInvoice->archive_status === 'ready')
                    <a class="primary-button" href="{{ route('admin.quotes.urgent_invoice.download', $existingInvoice->uuid) }}">Download original / تنزيل الأصل</a>
                @elseif (bouncer()->hasPermission('quotes.issue_invoice'))
                    <form method="POST" action="{{ route('admin.quotes.urgent_invoice.issue', $quote->id) }}">@csrf
                        <input type="hidden" name="confirm_issue" value="1"><button class="primary-button">Retry same invoice / إعادة حفظ نفس الفاتورة</button>
                    </form>
                @endif
            @endif

            @if ($quote->status !== 'accepted')
                <div class="mt-3 rounded bg-amber-50 p-3 text-sm text-amber-800">
                    This proposal is not accepted. Invoice issuance
                    will remain blocked until it is accepted.
                </div>
            @endif

            @if ($pricingError)
                <div class="mt-3 rounded bg-red-50 p-3 text-sm text-red-700">
                    Pricing validation failed: {{ $pricingError }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-3 rounded bg-red-50 p-3 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            @if ($sellerErrors)
                <div class="mt-3 rounded bg-amber-50 p-3 text-sm text-amber-800">
                    <strong>Tiara seller configuration is incomplete in .env. These are not buyer-field errors. / بيانات البائع تيارا غير مكتملة في إعدادات النظام وليست أخطاء في بيانات المشتري.</strong>
                    @foreach ($sellerErrors as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif
        </div>

        <div class="rounded-lg bg-white p-5 dark:bg-gray-900">
            <h2 class="mb-4 text-lg font-bold">
                Proposal Items / بنود العرض
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2 text-left">Description</th>
                            <th class="p-2 text-right">Qty</th>
                            <th class="p-2 text-right">Unit Price</th>
                            <th class="p-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['items'] as $item)
                            <tr class="border-b">
                                <td class="p-2">{{ $item['name'] }}</td>
                                <td class="p-2 text-right">{{ $item['quantity'] }}</td>
                                <td class="p-2 text-right">{{ $item['unit_price'] }}</td>
                                <td class="p-2 text-right">{{ $item['line_total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @php
                $displayTotals = $totals ?? $preview['proposal_totals'];
            @endphp

            <div class="mt-4 text-right text-sm">
                <p>Subtotal:
                    {{ $displayTotals['subtotal'] }} SAR
                </p>
                <p>VAT:
                    {{ $displayTotals['vat_amount'] }} SAR
                </p>
                <p class="mt-2 text-lg font-bold">
                    Total:
                    {{ $displayTotals['grand_total'] }} SAR
                </p>
            </div>
        </div>

        <div class="rounded-lg bg-white p-5 dark:bg-gray-900">
            <h2 class="mb-4 text-lg font-bold">
                Buyer Details / بيانات المشتري
            </h2>

            <form
                method="POST"
                action="{{ route('admin.quotes.urgent_invoice.review', $quote->id) }}"
                class="flex flex-col gap-4"
            >
                @csrf


                <div>
                    <label>Invoice Type / نوع الفاتورة</label>
                    <select name="invoice_type" class="{{ $inputClass }}">
                        <option value="standard" @selected(old('invoice_type', $values['invoice_type'] ?? 'standard') === 'standard')>
                            Standard Tax Invoice
                        </option>
                        <option value="simplified" @selected(old('invoice_type', $values['invoice_type'] ?? '') === 'simplified')>
                            Simplified Tax Invoice
                        </option>
                    </select>
                </div>

                <div>
                    <label>Buyer Legal Name / الاسم القانوني</label>
                    <input
                        name="buyer_name"
                        required
                        class="{{ $inputClass }}"
                        value="{{ old('buyer_name', $values['buyer_name'] ?? $buyer['company_name']) }}"
                    >
                </div>

                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="buyer_vat_registered"
                        value="1"
                        @checked(old('buyer_vat_registered', $values['buyer_vat_registered'] ?? false))
                    >
                    Buyer is VAT registered / المشتري مسجل في ضريبة القيمة المضافة
                </label>

                <div>
                    <label>Buyer VAT Number / الرقم الضريبي للمشتري</label>
                    <input
                        name="buyer_vat_number"
                        class="{{ $inputClass }}"
                        value="{{ old('buyer_vat_number', $values['buyer_vat_number'] ?? '') }}"
                        placeholder="15 digits if VAT registered"
                    >
                </div>

                <div>
                    <label>Buyer Address / عنوان المشتري</label>
                    <input
                        name="buyer_address"
                        class="{{ $inputClass }}"
                        value="{{ old('buyer_address', $values['buyer_address'] ?? '') }}"
                    >
                </div>

                <div>
                    <label>City / المدينة</label>
                    <input
                        name="buyer_city"
                        class="{{ $inputClass }}"
                        value="{{ old('buyer_city', $values['buyer_city'] ?? '') }}"
                    >
                </div>

                <div>
                    <label>Payment Method / طريقة الدفع</label>
                    <select name="payment_method" class="{{ $inputClass }}">
                        <option value="bank_transfer" @selected(old('payment_method', $values['payment_method'] ?? 'bank_transfer') === 'bank_transfer')>
                            Bank Transfer / تحويل بنكي
                        </option>
                        <option value="cash" @selected(old('payment_method', $values['payment_method'] ?? '') === 'cash')>
                            Cash / نقداً
                        </option>
                        <option value="card" @selected(old('payment_method', $values['payment_method'] ?? '') === 'card')>
                            Card / بطاقة
                        </option>
                    </select>
                </div>

                <div>
                    <label>Payment Status / حالة الدفع</label>
                    <select name="payment_status" class="{{ $inputClass }}">
                        <option value="pending" @selected(old('payment_status', $values['payment_status'] ?? 'pending') === 'pending')>
                            Pending / لم يتم الدفع
                        </option>
                        <option value="received" @selected(old('payment_status', $values['payment_status'] ?? '') === 'received')>
                            Received / تم استلام الدفع
                        </option>
                    </select>
                </div>

                <div>
                    <label>Transaction Reference / رقم العملية</label>
                    <input
                        name="transaction_reference"
                        class="{{ $inputClass }}"
                        value="{{ old('transaction_reference', $values['transaction_reference'] ?? '') }}"
                        placeholder="Enter actual bank reference if payment received"
                    >
                </div>

                @foreach (['buyer_name_ar' => 'Buyer legal name in Arabic / الاسم القانوني بالعربية', 'buyer_address_ar' => 'Buyer address in Arabic / العنوان بالعربية'] as $field => $label)
                    <div><label>{{ $label }}</label><input dir="rtl" name="{{ $field }}" class="{{ $inputClass }}" value="{{ old($field, $values[$field] ?? '') }}"></div>
                @endforeach
                <div><label>Actual supply date / تاريخ التوريد الفعلي</label><input type="date" name="supply_date" required max="{{ now('Asia/Riyadh')->toDateString() }}" class="{{ $inputClass }}" value="{{ old('supply_date', $values['supply_date'] ?? '') }}"></div>
                @foreach ($preview['items'] as $index => $item)
                    <div><label>Arabic description / الوصف العربي: {{ $item['name'] }}</label><input dir="rtl" name="item_names_ar[{{ $index }}]" required class="{{ $inputClass }}" value="{{ old('item_names_ar.'.$index, $values['item_names_ar'][$index] ?? '') }}"></div>
                @endforeach
                <div><label>Actual receipt time in Riyadh (if fully paid) / وقت الاستلام بالرياض</label><input type="datetime-local" name="payment_received_at" class="{{ $inputClass }}" value="{{ old('payment_received_at', $values['payment_received_at'] ?? '') }}"></div>
                @foreach ([
                    'payment_confirmed' => 'If paid: I verified full payment and the genuine receipt reference. / عند الدفع: تحققت من كامل المبلغ والمرجع الحقيقي.',
                    'invoice_type_confirmed' => 'I verified the invoice type with accounting. / تحققت من نوع الفاتورة مع المحاسبة.',
                    'supply_confirmed' => 'Supply completed; this is not an advance. / التوريد مكتمل وليس دفعة مقدمة.',
                    'no_prior_invoice' => 'No previous invoice or advance invoice exists for this supply. / لا توجد فاتورة سابقة أو فاتورة دفعة مقدمة لهذا التوريد.',
                ] as $field => $label)
                    <label><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $values[$field] ?? false))> {{ $label }}</label>
                @endforeach
                <button type="submit" class="primary-button">
                    Review Buyer Details / مراجعة بيانات المشتري
                </button>
            </form>

            @if ($reviewedBuyer)
                <div class="mt-4 rounded bg-green-50 p-4 text-green-800">
                    Buyer details passed initial validation.
                    No invoice has been issued or saved.
                </div>
                @if (! $existingInvoice && $quote->status === 'accepted' && ! $pricingError && config('urgent_invoice.enabled') && config('urgent_invoice.phase_one_reviewed') && bouncer()->hasPermission('quotes.issue_invoice'))
                    <form method="POST" action="{{ route('admin.quotes.urgent_invoice.issue', $quote->id) }}" class="mt-4">@csrf
                        <label><input type="checkbox" name="confirm_issue" value="1" required> Confirm reviewed details; issuance is permanent. / أؤكد البيانات والإصدار النهائي.</label>
                        <button class="primary-button">Issue and download / إصدار وتنزيل</button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</x-admin::layouts>
