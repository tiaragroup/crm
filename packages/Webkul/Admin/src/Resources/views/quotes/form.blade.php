@php
    $settings = $proposalSettings;
    $issuedAt = old('issued_at', optional($quote->issued_at)->format('Y-m-d') ?: now()->format('Y-m-d'));
    $validUntil = old('expired_at', optional($quote->expired_at)->format('Y-m-d') ?: now()->addDays($settings?->validity_days ?? 30)->format('Y-m-d'));
    $eventAt = old('event_at', optional($quote->event_at)->format('Y-m-d\TH:i'));
    $defaultItems = $quote->exists
        ? $quote->items->map(fn ($item) => ['key' => (string) $item->id, 'product_id' => (int) $item->product_id > 0 ? (int) $item->product_id : null, 'name' => $item->name, 'description' => $item->description, 'pricing_type' => $item->pricing_type, 'price' => (float) $item->price, 'quantity' => (int) $item->quantity, 'guest_count' => (int) ($item->guest_count ?: $quote->guest_count), 'discount_amount' => (float) $item->discount_amount])->values()
        : collect([['key' => 'item_1', 'product_id' => null, 'name' => 'Catering Package', 'description' => '', 'pricing_type' => 'per_person', 'price' => 0, 'quantity' => 1, 'guest_count' => 150, 'discount_amount' => 0]]);
    $defaultSections = $quote->exists
        ? $quote->menuSections->map(fn ($section) => ['name' => $section->name, 'catering_menu_category_id' => (int) $section->catering_menu_category_id > 0 ? (int) $section->catering_menu_category_id : null, 'items' => $section->items->map(fn ($item) => ['product_id' => (int) $item->product_id > 0 ? (int) $item->product_id : null, 'name' => $item->name, 'description' => $item->description])->values()])->values()
        : collect();
    $initialItems = array_values((array) old('items', $defaultItems->all()));
    $initialSections = collect((array) old('menu_sections', $defaultSections->all()))
        ->values()
        ->map(function ($section) {
            $section = (array) $section;
            $section['items'] = array_values((array) ($section['items'] ?? []));

            return $section;
        })
        ->all();
    $initial = [
        'contactId' => (string) old('person_id', $quote->person_id),
        'guestCount' => (int) old('guest_count', $quote->guest_count ?: 150),
        'vatPercent' => (float) old('vat_percent', $quote->vat_percent ?? $settings?->vat_percent ?? 15),
        'adjustment' => (float) old('adjustment_amount', $quote->adjustment_amount ?: 0),
        'items' => $initialItems,
        'menuSections' => $initialSections,
        'setupDescription' => old('setup_description', $quote->setup_description),
        'serviceInclusions' => old('service_inclusions', $quote->service_inclusions),
    ];
    $inputClass = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 outline-none transition focus:border-brandColor dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300';
    $labelClass = 'mb-1.5 block text-sm font-medium text-gray-800 dark:text-white';
@endphp

<div class="flex flex-col gap-4">
    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
        <div>
            <x-admin::breadcrumbs :name="$isEdit ? 'quotes.edit' : 'quotes.create'" :entity="$isEdit ? $quote : null" />
            <h1 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">{{ $isEdit ? 'Edit Catering Proposal' : 'Create Catering Proposal' }}</h1>
            @if ($quote->proposal_reference)<p class="text-sm text-gray-500">{{ $quote->proposal_reference }} · Revision {{ $quote->revision }}</p>@endif
        </div>
        <div class="flex gap-2">
            @if ($isEdit)
                <a href="{{ route('admin.quotes.word', $quote->id) }}" class="secondary-button">Download Word</a>
                <a href="{{ route('admin.quotes.print', $quote->id) }}" class="secondary-button">Download PDF</a>
            @endif
            <button type="submit" class="primary-button">Save Proposal</button>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><p class="font-semibold">Please correct the proposal information.</p><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <v-catering-proposal-builder :initial='@json($initial)' :packages='@json($cateringPackages)' :categories='@json($menuCategories)' :contacts='@json($people)'></v-catering-proposal-builder>
</div>

@pushOnce('scripts')
<script type="text/x-template" id="v-catering-proposal-builder-template">
    <div class="grid grid-cols-[minmax(0,1fr)_340px] gap-4 max-xl:grid-cols-1">
        <div class="flex flex-col gap-4">
            <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                    <div><h2 class="font-semibold text-gray-800 dark:text-white">Proposal & Client</h2><p class="text-xs text-gray-500">Cover-page information shown to the client.</p></div>
                    <select name="status" class="{{ $inputClass }} !w-36">@foreach (['draft', 'sent', 'accepted', 'rejected', 'expired'] as $status)<option value="{{ $status }}" @selected(old('status', $quote->status ?: 'draft') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
                </div>
                <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">Proposal subject *</label><input name="subject" required value="{{ old('subject', $quote->subject ?: 'Catering Function Proposal') }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Proposal reference</label><input name="proposal_reference" value="{{ old('proposal_reference', $quote->proposal_reference) }}" placeholder="Generated after save" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Sales owner *</label><select name="user_id" required class="{{ $inputClass }}"><option value="">Select sales owner</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((int) old('user_id', $quote->user_id) === $user->id)>{{ $user->name }}</option>@endforeach</select></div>
                    <div><label class="{{ $labelClass }}">Issue date *</label><input type="date" name="issued_at" required value="{{ $issuedAt }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Valid until *</label><input type="date" name="expired_at" required value="{{ $validUntil }}" class="{{ $inputClass }}"></div>
                    <div class="col-span-2 max-md:col-span-1">
                        <label class="{{ $labelClass }}">CRM contact *</label>
                        <div class="flex gap-2 max-sm:flex-col">
                            <select name="person_id" v-model="selectedContactId" required class="{{ $inputClass }}">
                                <option value="">Select contact</option>
                                <option v-for="contact in contactOptions" :key="contact.id" :value="String(contact.id)">@{{ contactLabel(contact) }}</option>
                            </select>
                            @if (bouncer()->hasPermission('contacts.persons.create'))
                                <button type="button" class="secondary-button whitespace-nowrap" @click="$refs.contactModal.open()">+ Add new contact</button>
                            @endif
                        </div>
                        <div v-if="selectedContact" class="mt-3 grid grid-cols-2 gap-x-6 gap-y-2 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300 max-sm:grid-cols-1">
                            <div><span class="font-medium text-gray-800 dark:text-white">Contact:</span> @{{ selectedContact.name }}</div>
                            <div><span class="font-medium text-gray-800 dark:text-white">Company:</span> @{{ selectedContact.company || 'Not specified' }}</div>
                            <div><span class="font-medium text-gray-800 dark:text-white">Mobile:</span> @{{ selectedContact.mobile || 'Not specified' }}</div>
                            <div><span class="font-medium text-gray-800 dark:text-white">Email:</span> @{{ selectedContact.email || 'Not specified' }}</div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Contact details are taken directly from the CRM contact record and used in the proposal.</p>
                        <input type="hidden" name="client_company" :value="selectedContact?.company || ''">
                        <input type="hidden" name="attention_name" :value="selectedContact?.name || ''">
                        <input type="hidden" name="client_mobile" :value="selectedContact?.mobile || ''">
                        <input type="hidden" name="client_email" :value="selectedContact?.email || ''">
                    </div>
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">Introduction</label><textarea name="greeting" rows="4" class="{{ $inputClass }}">{{ old('greeting', $quote->greeting ?: $settings?->greeting_template) }}</textarea></div>
                    <input type="hidden" name="description" value="{{ old('description', $quote->description ?: 'Catering function proposal') }}">
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <header class="mb-4 border-b border-gray-200 pb-3 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white">Event Details</h2><p class="text-xs text-gray-500">Operational information printed in the event summary.</p></header>
                <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                    <div><label class="{{ $labelClass }}">Event type</label><input name="event_type" value="{{ old('event_type', $quote->event_type) }}" placeholder="Reception, wedding, corporate event…" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Event date and time</label><input type="datetime-local" name="event_at" value="{{ $eventAt }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Venue</label><input name="venue" value="{{ old('venue', $quote->venue) }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Number of guests *</label><input type="number" min="1" name="guest_count" v-model.number="guestCount" required class="{{ $inputClass }}"></div>
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">Setup description</label><textarea name="setup_description" v-model="setupDescription" rows="3" class="{{ $inputClass }}"></textarea></div>
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">Service inclusions <span class="font-normal text-gray-500">(one per line)</span></label><textarea name="service_inclusions" v-model="serviceInclusions" rows="4" class="{{ $inputClass }}"></textarea></div>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <header class="mb-4 flex items-end justify-between gap-3 border-b border-gray-200 pb-3 dark:border-gray-800 max-md:flex-col max-md:items-stretch">
                    <div><h2 class="font-semibold text-gray-800 dark:text-white">Menu Builder</h2><p class="text-xs text-gray-500">Apply a package, then customize this proposal without changing the catalog.</p></div>
                    <div class="flex flex-wrap justify-end gap-2"><select v-model="selectedPackage" class="{{ $inputClass }} !w-auto min-w-64"><option value="">Choose catering menu</option><option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">@{{ pkg.name }} — SAR @{{ money(pkg.price_per_person) }}/person</option></select><button type="button" class="secondary-button whitespace-nowrap" @click="applyPackage">Apply Menu</button>@if (bouncer()->hasPermission('catering_menus.create'))<a href="{{ route('admin.catering.menus.create') }}" class="secondary-button whitespace-nowrap">+ Create Menu</a>@endif</div>
                </header>
                <div v-if="!menuSections.length" class="rounded-md bg-gray-50 p-6 text-center text-sm text-gray-500 dark:bg-gray-950">Choose a package or add the first menu section.</div>
                <div v-for="(section, sectionIndex) in menuSections" :key="section._key || sectionIndex" class="mb-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <input v-model="section.name" :name="`menu_sections[${sectionIndex}][name]`" required placeholder="Menu section" class="{{ $inputClass }} font-semibold">
                        <input type="hidden" :name="`menu_sections[${sectionIndex}][catering_menu_category_id]`" :value="section.catering_menu_category_id">
                        <button type="button" class="secondary-button whitespace-nowrap" @click="section._expanded = !section._expanded">@{{ section._expanded ? 'Collapse' : `Edit ${section.items.length} dishes` }}</button>
                        <button type="button" class="px-2 text-sm font-medium text-red-600" @click="menuSections.splice(sectionIndex, 1)">Remove</button>
                    </div>
                    <div v-show="section._expanded" class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-800">
                        <div v-for="(item, itemIndex) in section.items" :key="item._key || itemIndex" class="mb-2 flex items-center gap-2 max-md:flex-col max-md:items-stretch">
                            <input type="hidden" :name="`menu_sections[${sectionIndex}][items][${itemIndex}][product_id]`" :value="item.product_id">
                            <input v-model="item.name" :name="`menu_sections[${sectionIndex}][items][${itemIndex}][name]`" required placeholder="Dish name" class="{{ $inputClass }} !w-2/5 max-md:!w-full">
                            <input v-model="item.description" :name="`menu_sections[${sectionIndex}][items][${itemIndex}][description]`" placeholder="Optional description" class="{{ $inputClass }} flex-1">
                            <button type="button" class="px-3 text-xl text-red-600" title="Remove dish" @click="section.items.splice(itemIndex, 1)">×</button>
                        </div>
                        <div class="mt-3 flex gap-2 max-md:flex-col"><select v-model="section.selectedProduct" class="{{ $inputClass }}"><option value="">Add item from catalog</option><option v-for="product in productsFor(section)" :key="product.id" :value="product.id">@{{ product.name }}</option></select><button type="button" class="secondary-button whitespace-nowrap" @click="addCatalogProduct(section)">Add Item</button><button type="button" class="secondary-button whitespace-nowrap" @click="section.items.push({product_id:null,name:'',description:''})">Custom Item</button></div>
                    </div>
                </div>
                <button type="button" class="secondary-button" @click="addSection">+ Add Menu Section</button>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <header class="mb-4 flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800"><div><h2 class="font-semibold text-gray-800 dark:text-white">Pricing</h2><p class="text-xs text-gray-500">Per-person, fixed, and included rows are supported.</p></div><button type="button" class="secondary-button" @click="addPriceItem">+ Add Row</button></header>
                <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-sm"><thead><tr class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-950"><th class="p-2">Description</th><th class="p-2">Type</th><th class="p-2">Price</th><th class="p-2">Qty / Guests</th><th class="p-2">Discount</th><th class="p-2 text-right">Total</th><th></th></tr></thead><tbody>
                    <tr v-for="(item, index) in items" :key="item.key" class="border-b border-gray-100 dark:border-gray-800">
                        <td class="p-2"><input type="hidden" :name="`items[${item.key}][product_id]`" :value="item.product_id"><input v-model="item.name" :name="`items[${item.key}][name]`" required class="{{ $inputClass }}"><input v-model="item.description" :name="`items[${item.key}][description]`" placeholder="Optional detail" class="mt-1 w-full border-0 bg-transparent px-1 text-xs text-gray-500 outline-none"></td>
                        <td class="p-2"><select v-model="item.pricing_type" :name="`items[${item.key}][pricing_type]`" class="{{ $inputClass }}"><option value="per_person">Per person</option><option value="fixed">Fixed</option><option value="included">Included</option></select></td>
                        <td class="p-2"><input type="number" step="0.01" min="0" v-model.number="item.price" :disabled="item.pricing_type === 'included'" :name="`items[${item.key}][price]`" class="{{ $inputClass }}"></td>
                        <td class="p-2"><input type="number" min="1" v-model.number="item.guest_count" :name="`items[${item.key}][guest_count]`" class="{{ $inputClass }}"><input type="hidden" :name="`items[${item.key}][quantity]`" :value="item.pricing_type === 'per_person' ? item.guest_count : 1"></td>
                        <td class="p-2"><input type="number" step="0.01" min="0" v-model.number="item.discount_amount" :name="`items[${item.key}][discount_amount]`" class="{{ $inputClass }}"></td><td class="p-2 text-right font-semibold">SAR @{{ money(lineTotal(item)) }}</td><td class="p-2"><button type="button" class="text-red-600" @click="removePriceItem(index)">×</button></td><input type="hidden" :name="`items[${item.key}][sort_order]`" :value="index + 1">
                    </tr>
                </tbody></table></div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <header class="mb-4 border-b border-gray-200 pb-3 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white">Terms, Bank & Approval</h2><p class="text-xs text-gray-500">Content for the final proposal page.</p></header>
                <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">Pricing & VAT terms</label><textarea name="pricing_terms" rows="3" class="{{ $inputClass }}">{{ old('pricing_terms', $quote->pricing_terms ?: $settings?->pricing_terms) }}</textarea></div>
                    <div><label class="{{ $labelClass }}">Payment terms</label><textarea name="payment_terms" rows="4" class="{{ $inputClass }}">{{ old('payment_terms', $quote->payment_terms ?: $settings?->payment_terms) }}</textarea></div><div><label class="{{ $labelClass }}">Changes terms</label><textarea name="changes_terms" rows="4" class="{{ $inputClass }}">{{ old('changes_terms', $quote->changes_terms ?: $settings?->changes_terms) }}</textarea></div>
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">Cancellation terms</label><textarea name="cancellation_terms" rows="4" class="{{ $inputClass }}">{{ old('cancellation_terms', $quote->cancellation_terms ?: $settings?->cancellation_terms) }}</textarea></div>
                    <div><label class="{{ $labelClass }}">Account name</label><input name="bank_account_name" value="{{ old('bank_account_name', $quote->bank_account_name ?: $settings?->bank_account_name) }}" class="{{ $inputClass }}"></div><div><label class="{{ $labelClass }}">Bank</label><input name="bank_name" value="{{ old('bank_name', $quote->bank_name ?: $settings?->bank_name) }}" class="{{ $inputClass }}"></div>
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">IBAN</label><input name="iban" value="{{ old('iban', $quote->iban ?: $settings?->iban) }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Tiara signatory</label><input name="company_signatory_name" value="{{ old('company_signatory_name', $quote->company_signatory_name ?: $settings?->signatory_name) }}" class="{{ $inputClass }}"></div><div><label class="{{ $labelClass }}">Tiara signatory title</label><input name="company_signatory_title" value="{{ old('company_signatory_title', $quote->company_signatory_title ?: $settings?->signatory_title) }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Client signatory</label><input name="client_signatory_name" value="{{ old('client_signatory_name', $quote->client_signatory_name) }}" class="{{ $inputClass }}"></div><div><label class="{{ $labelClass }}">Client signatory title</label><input name="client_signatory_title" value="{{ old('client_signatory_title', $quote->client_signatory_title) }}" class="{{ $inputClass }}"></div>
                </div>
            </section>
        </div>

        <aside class="h-fit rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 xl:sticky xl:top-4">
            <h2 class="border-b border-gray-200 pb-3 font-semibold text-gray-800 dark:border-gray-800 dark:text-white">Proposal Summary</h2>
            <dl class="mt-4 flex flex-col gap-3 text-sm"><div class="flex justify-between"><dt>Guests</dt><dd class="font-semibold">@{{ guestCount }}</dd></div><div class="flex justify-between"><dt>Subtotal</dt><dd>SAR @{{ money(subtotal) }}</dd></div><div class="flex justify-between"><dt>Discount</dt><dd>- SAR @{{ money(discountTotal) }}</dd></div><div class="flex items-center justify-between gap-4"><dt>Adjustment</dt><dd><input type="number" step="0.01" name="adjustment_amount" v-model.number="adjustment" class="{{ $inputClass }} !w-28 text-right"></dd></div><div class="flex items-center justify-between gap-4"><dt>VAT</dt><dd class="flex items-center gap-1"><input type="number" step="0.01" min="0" max="100" name="vat_percent" v-model.number="vatPercent" class="{{ $inputClass }} !w-20 text-right"><span>%</span></dd></div><div class="mt-2 flex justify-between border-t border-gray-200 pt-4 text-base font-bold dark:border-gray-800"><dt>Grand total</dt><dd class="text-brandColor">SAR @{{ money(grandTotal) }}</dd></div></dl>
            <p class="mt-4 rounded-md bg-green-50 p-3 text-xs text-green-700">Totals are recalculated securely when saved and stored in the export snapshot.</p>
        </aside>

        @if (bouncer()->hasPermission('contacts.persons.create'))
            <x-admin::modal ref="contactModal" size="medium">
                <x-slot:header><h3 class="text-lg font-semibold text-gray-800 dark:text-white">Add new CRM contact</h3></x-slot>
                <x-slot:content>
                    <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                        <div class="col-span-2 max-sm:col-span-1"><label class="{{ $labelClass }}">Contact name *</label><input v-model.trim="newContact.name" class="{{ $inputClass }}"></div>
                        <div><label class="{{ $labelClass }}">Company</label><input v-model.trim="newContact.company" class="{{ $inputClass }}"></div>
                        <div><label class="{{ $labelClass }}">Email *</label><input type="email" v-model.trim="newContact.email" class="{{ $inputClass }}"></div>
                        <div><label class="{{ $labelClass }}">Mobile</label><input v-model.trim="newContact.mobile" class="{{ $inputClass }}"></div>
                    </div>
                    <div v-if="contactError" class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700">@{{ contactError }}</div>
                </x-slot>
                <x-slot:footer>
                    <div class="flex justify-end gap-2"><button type="button" class="secondary-button" @click="$refs.contactModal.close()">Cancel</button><button type="button" class="primary-button" :disabled="isCreatingContact" @click="createContact">@{{ isCreatingContact ? 'Saving…' : 'Save contact' }}</button></div>
                </x-slot>
            </x-admin::modal>
        @endif
    </div>
</script>

<script type="module">
    app.component('v-catering-proposal-builder', {
        template: '#v-catering-proposal-builder-template',
        props: ['initial', 'packages', 'categories', 'contacts'],
        data() { return {selectedContactId: this.initial.contactId || '', contactOptions: [...(this.contacts || [])], newContact: {name:'',company:'',email:'',mobile:''}, contactError: '', isCreatingContact: false, guestCount: this.initial.guestCount || 1, vatPercent: this.initial.vatPercent ?? 15, adjustment: this.initial.adjustment || 0, items: (this.initial.items || []).map((item, index) => ({...item, key: item.key || `item_${Date.now()}_${index}`})), menuSections: (this.initial.menuSections || []).map((section, index) => ({...section, _key: `section_${index}`, _expanded: false, selectedProduct: '', items: section.items || []})), setupDescription: this.initial.setupDescription || '', serviceInclusions: this.initial.serviceInclusions || '', selectedPackage: ''}; },
        computed: {selectedContact() { return this.contactOptions.find(contact => Number(contact.id) === Number(this.selectedContactId)) || null; }, subtotal() { return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0); }, discountTotal() { return this.items.reduce((sum, item) => sum + Number(item.discount_amount || 0), 0); }, taxable() { return Math.max(0, this.subtotal - this.discountTotal + Number(this.adjustment || 0)); }, grandTotal() { return this.taxable + this.taxable * Number(this.vatPercent || 0) / 100; }},
        watch: {guestCount(value) { this.items.filter(item => item.pricing_type === 'per_person').forEach(item => item.guest_count = value); }},
        methods: {
            contactLabel(contact) { return [contact.name, contact.company, contact.mobile].filter(Boolean).join(' — '); },
            createContact() {
                this.contactError = '';
                if (!this.newContact.name || !this.newContact.email) { this.contactError = 'Contact name and email are required.'; return; }
                this.isCreatingContact = true;
                const payload = {entity_type:'persons',name:this.newContact.name,organization_name:this.newContact.company,emails:[{label:'work',value:this.newContact.email}]};
                if (this.newContact.mobile) payload.contact_numbers = [{label:'work',value:this.newContact.mobile}];
                this.$axios.post('{{ route('admin.contacts.persons.store') }}', payload)
                    .then(response => { const person = response.data.data; const contact = {id:person.id,name:person.name,company:person.organization?.name || this.newContact.company,email:person.emails?.[0]?.value || this.newContact.email,mobile:person.contact_numbers?.[0]?.value || this.newContact.mobile}; this.contactOptions.push(contact); this.selectedContactId = String(contact.id); this.newContact = {name:'',company:'',email:'',mobile:''}; this.$refs.contactModal.close(); this.$emitter.emit('add-flash', {type:'success',message:'Contact created and selected.'}); })
                    .catch(error => { const errors = error.response?.data?.errors || {}; this.contactError = Object.values(errors).flat()[0] || error.response?.data?.message || 'Unable to create the contact.'; })
                    .finally(() => this.isCreatingContact = false);
            },
            money(value) { return Number(value || 0).toLocaleString('en-SA', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
            lineTotal(item) { if (item.pricing_type === 'included') return 0; return Number(item.price || 0) * (item.pricing_type === 'per_person' ? Number(item.guest_count || this.guestCount || 0) : Number(item.quantity || 1)); },
            applyPackage() { const pkg = this.packages.find(item => Number(item.id) === Number(this.selectedPackage)); if (!pkg) return; const grouped = {}; (pkg.items || []).forEach(link => { const product = link.product; if (!product) return; const category = product.catering_menu_category || {id:null,name:'Menu'}; grouped[category.name] ||= {name:category.name,catering_menu_category_id:category.id,items:[]}; grouped[category.name].items.push({product_id:product.id,name:product.name || 'Unnamed dish',description:product.description || ''}); }); this.menuSections = Object.values(grouped).map((section, index) => ({...section,_key:`section_${Date.now()}_${index}`,_expanded:false,selectedProduct:''})); this.setupDescription = pkg.setup_description || ''; this.serviceInclusions = pkg.service_inclusions || ''; this.items = [{key:`item_${Date.now()}`,product_id:null,name:pkg.name,description:pkg.description || '',pricing_type:'per_person',price:Number(pkg.price_per_person),quantity:1,guest_count:this.guestCount,discount_amount:0}]; },
            addSection() { this.menuSections.push({_key:`section_${Date.now()}`,name:'New Menu Section',catering_menu_category_id:null,_expanded:true,selectedProduct:'',items:[]}); },
            productsFor(section) { const category = this.categories.find(item => Number(item.id) === Number(section.catering_menu_category_id)); return category?.products || this.categories.flatMap(item => item.products || []); },
            addCatalogProduct(section) { const product = this.categories.flatMap(item => item.products || []).find(item => Number(item.id) === Number(section.selectedProduct)); if (!product) return; section.items.push({product_id:product.id,name:product.name,description:product.description || ''}); section.selectedProduct = ''; },
            addPriceItem() { this.items.push({key:`item_${Date.now()}`,product_id:null,name:'',description:'',pricing_type:'fixed',price:0,quantity:1,guest_count:this.guestCount,discount_amount:0}); },
            removePriceItem(index) { if (this.items.length > 1) this.items.splice(index, 1); },
        },
    });
</script>
@endPushOnce
