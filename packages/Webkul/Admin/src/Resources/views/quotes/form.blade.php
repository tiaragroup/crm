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
            <h1 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">{{ $isEdit ? trans('admin::app.quotes.form.edit-title') : trans('admin::app.quotes.form.create-title') }}</h1>
            @if ($quote->proposal_reference)<p class="text-sm text-gray-500">{{ $quote->proposal_reference }} · @lang('admin::app.quotes.form.revision', ['number' => $quote->revision])</p>@endif
        </div>
        <div class="flex gap-2">
            @if ($isEdit)
                <a href="{{ route('admin.quotes.word', $quote->id) }}" class="secondary-button">@lang('admin::app.quotes.form.download-word')</a>
                <x-admin::modal ref="pdfLanguageModal" size="normal">
                    <x-slot:toggle>
                        <button type="button" class="secondary-button">@lang('admin::app.quotes.form.download-pdf')</button>
                    </x-slot>

                    <x-slot:header>
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50 dark:bg-gray-800">
                                <img src="{{ asset('images/tiara-logo.png') }}" width="32" height="32" alt="Tiara Catering" style="object-fit: contain;">
                            </div>
                            <div>
                                <p class="text-lg font-bold text-gray-800 dark:text-white">@lang('admin::app.quotes.form.export-title')</p>
                                <p class="mt-1 text-xs text-gray-500">@lang('admin::app.quotes.form.export-info')</p>
                            </div>
                        </div>
                    </x-slot>

                    <x-slot:content class="!border-b-0 !p-0">
                        <div class="p-5">
                            <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                                <a
                                    href="{{ route('admin.quotes.print', ['id' => $quote->id, 'locale' => 'en']) }}"
                                    target="_blank"
                                    class="flex flex-col justify-between gap-8 rounded-lg border-2 border-gray-200 bg-white p-5 no-underline transition hover:border-brandColor hover:bg-amber-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-brandColor dark:hover:bg-gray-800"
                                    @click="$refs.pdfLanguageModal.close()"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="rounded-md bg-amber-50 px-3 py-2 text-xs font-bold text-brandColor dark:bg-gray-800">EN</span>
                                        <span class="text-xl text-brandColor">→</span>
                                    </div>
                                    <div>
                                        <p class="text-base font-bold text-gray-800 dark:text-white">@lang('admin::app.quotes.form.english')</p>
                                        <p class="mt-1 text-xs leading-5 text-gray-500">@lang('admin::app.quotes.form.english-info')</p>
                                    </div>
                                </a>

                                <a
                                    href="{{ route('admin.quotes.print', ['id' => $quote->id, 'locale' => 'ar']) }}"
                                    target="_blank"
                                    dir="rtl"
                                    lang="ar"
                                    class="flex flex-col justify-between gap-8 rounded-lg border-2 border-gray-200 bg-white p-5 text-right no-underline transition hover:border-brandColor hover:bg-amber-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-brandColor dark:hover:bg-gray-800"
                                    @click="$refs.pdfLanguageModal.close()"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="rounded-md bg-amber-50 px-3 py-2 text-sm font-bold text-brandColor dark:bg-gray-800">ع</span>
                                        <span class="text-xl text-brandColor">←</span>
                                    </div>
                                    <div>
                                        <p class="text-base font-bold text-gray-800 dark:text-white">@lang('admin::app.quotes.form.arabic')</p>
                                        <p class="mt-1 text-xs leading-5 text-gray-500">@lang('admin::app.quotes.form.arabic-info')</p>
                                    </div>
                                </a>
                            </div>

                            <div class="mt-4 flex items-center gap-2 rounded-md bg-gray-50 px-4 py-3 text-xs text-gray-500 dark:bg-gray-950">
                                <span class="h-2 w-2 rounded-full bg-brandColor"></span>
                                <span>@lang('admin::app.quotes.form.export-note')</span>
                            </div>
                        </div>
                    </x-slot>
                </x-admin::modal>
            @endif
            <button type="submit" class="primary-button">@lang('admin::app.quotes.form.save')</button>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><p class="font-semibold">@lang('admin::app.quotes.form.validation-title')</p><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <v-catering-proposal-builder :initial='@json($initial)' :packages='@json($cateringPackages)' :categories='@json($menuCategories)' :contacts='@json($people)'></v-catering-proposal-builder>
</div>

@pushOnce('scripts')
<script type="text/x-template" id="v-catering-proposal-builder-template">
    <div class="grid grid-cols-[minmax(0,1fr)_340px] gap-4 max-xl:grid-cols-1">
        <div class="flex flex-col gap-4">
            <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                    <div><h2 class="font-semibold text-gray-800 dark:text-white">@lang('admin::app.quotes.form.proposal-client')</h2><p class="text-xs text-gray-500">@lang('admin::app.quotes.form.proposal-client-info')</p></div>
                    <select name="status" class="{{ $inputClass }} !w-36">@foreach (['draft', 'sent', 'accepted', 'rejected', 'expired'] as $status)<option value="{{ $status }}" @selected(old('status', $quote->status ?: 'draft') === $status)>@lang('admin::app.quotes.form.statuses.'.$status)</option>@endforeach</select>
                </div>
                <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.subject') *</label><input name="subject" required value="{{ old('subject', $quote->subject ?: 'Catering Function Proposal') }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.reference')</label><input name="proposal_reference" value="{{ old('proposal_reference', $quote->proposal_reference) }}" placeholder="@lang('admin::app.quotes.form.reference-placeholder')" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.sales-owner') *</label><select name="user_id" required class="{{ $inputClass }}"><option value="">@lang('admin::app.quotes.form.select-sales-owner')</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((int) old('user_id', $quote->user_id) === $user->id)>{{ $user->name }}</option>@endforeach</select></div>
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.issue-date') *</label><input type="date" name="issued_at" required value="{{ $issuedAt }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.valid-until') *</label><input type="date" name="expired_at" required value="{{ $validUntil }}" class="{{ $inputClass }}"></div>
                    <div class="col-span-2 max-md:col-span-1">
                        <label class="{{ $labelClass }}">@lang('admin::app.quotes.form.crm-contact') *</label>
                        <input type="hidden" name="person_id" :value="selectedContactId" required>

                        <div class="flex items-start gap-2 max-sm:flex-col">
                            <div ref="contactSearch" class="relative w-full">
                                <div class="relative">
                                    <input
                                        type="search"
                                        v-model="contactSearch"
                                        @input="queueContactSearch"
                                        @focus="openContactResults"
                                        @keydown.escape="showContactResults = false"
                                        autocomplete="off"
                                        placeholder="@lang('admin::app.quotes.form.contact-search')"
                                        class="{{ $inputClass }} pr-10"
                                    >

                                    <span
                                        v-if="isSearchingContacts"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-500"
                                    >
                                        @lang('admin::app.quotes.form.searching')
                                    </span>
                                </div>

                                <div
                                    v-if="showContactResults"
                                    class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                                >
                                    <p v-if="contactSearch.trim().length < 2" class="px-4 py-3 text-sm text-gray-500">
                                        @lang('admin::app.quotes.form.search-minimum')
                                    </p>

                                    <button
                                        v-for="contact in contactResults"
                                        :key="contact.id"
                                        type="button"
                                        class="flex w-full flex-col border-b border-gray-100 px-4 py-3 text-left last:border-b-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950"
                                        @click="selectContact(contact)"
                                    >
                                        <span class="font-medium text-gray-800 dark:text-white">@{{ contact.name }}</span>
                                        <span class="mt-0.5 text-xs text-gray-500">
                                            @{{ [contact.company, contact.email, contact.mobile].filter(Boolean).join(' · ') || @json(trans('admin::app.quotes.form.no-contact-details')) }}
                                        </span>
                                    </button>

                                    <p
                                        v-if="!isSearchingContacts && contactSearch.trim().length >= 2 && !contactResults.length"
                                        class="px-4 py-3 text-sm text-gray-500"
                                    >
                                        @lang('admin::app.quotes.form.no-contact-results')
                                    </p>
                                </div>
                            </div>

                            @if (bouncer()->hasPermission('contacts.persons.create'))
                                <button type="button" class="secondary-button whitespace-nowrap" @click="$refs.contactModal.open()">+ @lang('admin::app.quotes.form.add-contact')</button>
                            @endif
                        </div>
                        <div v-if="selectedContact" class="mt-3 grid grid-cols-2 gap-x-6 gap-y-2 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300 max-sm:grid-cols-1">
                            <div><span class="font-medium text-gray-800 dark:text-white">@lang('admin::app.quotes.form.contact'):</span> @{{ selectedContact.name }}</div>
                            <div><span class="font-medium text-gray-800 dark:text-white">@lang('admin::app.quotes.form.company'):</span> @{{ selectedContact.company || @json(trans('admin::app.quotes.form.not-specified')) }}</div>
                            <div><span class="font-medium text-gray-800 dark:text-white">@lang('admin::app.quotes.form.mobile'):</span> @{{ selectedContact.mobile || @json(trans('admin::app.quotes.form.not-specified')) }}</div>
                            <div><span class="font-medium text-gray-800 dark:text-white">@lang('admin::app.quotes.form.email'):</span> @{{ selectedContact.email || @json(trans('admin::app.quotes.form.not-specified')) }}</div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">@lang('admin::app.quotes.form.contact-info')</p>
                        <input type="hidden" name="client_company" :value="selectedContact?.company || ''">
                        <input type="hidden" name="attention_name" :value="selectedContact?.name || ''">
                        <input type="hidden" name="client_mobile" :value="selectedContact?.mobile || ''">
                        <input type="hidden" name="client_email" :value="selectedContact?.email || ''">
                    </div>
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.introduction')</label><textarea name="greeting" rows="4" class="{{ $inputClass }}">{{ old('greeting', $quote->greeting ?: $settings?->greeting_template) }}</textarea></div>
                    <input type="hidden" name="description" value="{{ old('description', $quote->description ?: 'Catering function proposal') }}">
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <header class="mb-4 border-b border-gray-200 pb-3 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white">@lang('admin::app.quotes.form.event-details')</h2><p class="text-xs text-gray-500">@lang('admin::app.quotes.form.event-details-info')</p></header>
                <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.event-type')</label><input name="event_type" value="{{ old('event_type', $quote->event_type) }}" placeholder="@lang('admin::app.quotes.form.event-type-placeholder')" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.event-date-time')</label><input type="datetime-local" name="event_at" value="{{ $eventAt }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.venue')</label><input name="venue" value="{{ old('venue', $quote->venue) }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.guest-count') *</label><input type="number" min="1" name="guest_count" v-model.number="guestCount" required class="{{ $inputClass }}"></div>
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.setup-description')</label><textarea name="setup_description" v-model="setupDescription" rows="3" class="{{ $inputClass }}"></textarea></div>
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.service-inclusions') <span class="font-normal text-gray-500">(@lang('admin::app.quotes.form.one-per-line'))</span></label><textarea name="service_inclusions" v-model="serviceInclusions" rows="4" class="{{ $inputClass }}"></textarea></div>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <header class="mb-4 flex items-end justify-between gap-3 border-b border-gray-200 pb-3 dark:border-gray-800 max-md:flex-col max-md:items-stretch">
                    <div><h2 class="font-semibold text-gray-800 dark:text-white">@lang('admin::app.quotes.form.menu-builder')</h2><p class="text-xs text-gray-500">@lang('admin::app.quotes.form.menu-builder-info')</p></div>
                    <div class="flex flex-wrap justify-end gap-2"><select v-model="selectedPackage" class="{{ $inputClass }} !w-auto min-w-64"><option value="">@lang('admin::app.quotes.form.choose-menu')</option><option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">@{{ localizedName(pkg) }} — @lang('admin::app.quotes.form.currency') @{{ money(pkg.price_per_person) }}/@lang('admin::app.quotes.form.per-person-suffix')</option></select><button type="button" class="secondary-button whitespace-nowrap" @click="applyPackage">@lang('admin::app.quotes.form.apply-menu')</button>@if (bouncer()->hasPermission('catering_menus.create'))<a href="{{ route('admin.catering.menus.create') }}" class="secondary-button whitespace-nowrap">+ @lang('admin::app.quotes.form.create-menu')</a>@endif</div>
                </header>
                <div v-if="!menuSections.length" class="rounded-md bg-gray-50 p-6 text-center text-sm text-gray-500 dark:bg-gray-950">@lang('admin::app.quotes.form.empty-menu')</div>
                <div v-for="(section, sectionIndex) in menuSections" :key="section._key || sectionIndex" class="mb-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <input v-model="section.name" :name="`menu_sections[${sectionIndex}][name]`" required placeholder="@lang('admin::app.quotes.form.menu-section-placeholder')" class="{{ $inputClass }} font-semibold">
                        <input type="hidden" :name="`menu_sections[${sectionIndex}][catering_menu_category_id]`" :value="section.catering_menu_category_id">
                        <button type="button" class="secondary-button whitespace-nowrap" @click="section._expanded = !section._expanded">@{{ section._expanded ? 'Collapse' : `Edit ${section.items.length} dishes` }}</button>
                        <button type="button" class="px-2 text-sm font-medium text-red-600" @click="menuSections.splice(sectionIndex, 1)">@lang('admin::app.quotes.form.remove')</button>
                    </div>
                    <div v-show="section._expanded" class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-800">
                        <div v-for="(item, itemIndex) in section.items" :key="item._key || itemIndex" class="mb-2 flex items-center gap-2 max-md:flex-col max-md:items-stretch">
                            <input type="hidden" :name="`menu_sections[${sectionIndex}][items][${itemIndex}][product_id]`" :value="item.product_id">
                            <input v-model="item.name" :name="`menu_sections[${sectionIndex}][items][${itemIndex}][name]`" required placeholder="@lang('admin::app.quotes.form.dish-name')" class="{{ $inputClass }} !w-2/5 max-md:!w-full">
                            <input v-model="item.description" :name="`menu_sections[${sectionIndex}][items][${itemIndex}][description]`" placeholder="@lang('admin::app.quotes.form.optional-description')" class="{{ $inputClass }} flex-1">
                            <button type="button" class="px-3 text-xl text-red-600" title="@lang('admin::app.quotes.form.remove-dish')" @click="section.items.splice(itemIndex, 1)">×</button>
                        </div>
                        <div class="mt-3 flex gap-2 max-md:flex-col"><select v-model="section.selectedProduct" class="{{ $inputClass }}"><option value="">@lang('admin::app.quotes.form.catalog-item')</option><option v-for="product in productsFor(section)" :key="product.id" :value="product.id">@{{ localizedName(product) }}</option></select><button type="button" class="secondary-button whitespace-nowrap" @click="addCatalogProduct(section)">@lang('admin::app.quotes.form.add-item')</button><button type="button" class="secondary-button whitespace-nowrap" @click="section.items.push({product_id:null,name:'',description:''})">@lang('admin::app.quotes.form.custom-item')</button></div>
                    </div>
                </div>
                <button type="button" class="secondary-button" @click="addSection">+ @lang('admin::app.quotes.form.add-menu-section')</button>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <header class="mb-4 flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800"><div><h2 class="font-semibold text-gray-800 dark:text-white">@lang('admin::app.quotes.form.pricing')</h2><p class="text-xs text-gray-500">@lang('admin::app.quotes.form.pricing-info')</p></div><button type="button" class="secondary-button" @click="addPriceItem">+ @lang('admin::app.quotes.form.add-row')</button></header>
                <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-sm"><thead><tr class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-950"><th class="p-2">@lang('admin::app.quotes.form.description')</th><th class="p-2">@lang('admin::app.quotes.form.type')</th><th class="p-2">@lang('admin::app.quotes.form.price')</th><th class="p-2">@lang('admin::app.quotes.form.quantity-guests')</th><th class="p-2">@lang('admin::app.quotes.form.discount')</th><th class="p-2 text-right">@lang('admin::app.quotes.form.total')</th><th></th></tr></thead><tbody>
                    <tr v-for="(item, index) in items" :key="item.key" class="border-b border-gray-100 dark:border-gray-800">
                        <td class="p-2"><input type="hidden" :name="`items[${item.key}][product_id]`" :value="item.product_id"><input v-model="item.name" :name="`items[${item.key}][name]`" required class="{{ $inputClass }}"><input v-model="item.description" :name="`items[${item.key}][description]`" placeholder="@lang('admin::app.quotes.form.optional-detail')" class="mt-1 w-full border-0 bg-transparent px-1 text-xs text-gray-500 outline-none"></td>
                        <td class="p-2"><select v-model="item.pricing_type" :name="`items[${item.key}][pricing_type]`" class="{{ $inputClass }}"><option value="per_person">@lang('admin::app.quotes.form.per-person')</option><option value="fixed">@lang('admin::app.quotes.form.fixed')</option><option value="included">@lang('admin::app.quotes.form.included')</option></select></td>
                        <td class="p-2"><input type="number" step="0.01" min="0" v-model.number="item.price" :disabled="item.pricing_type === 'included'" :name="`items[${item.key}][price]`" class="{{ $inputClass }}"></td>
                        <td class="p-2"><input type="number" min="1" v-model.number="item.guest_count" :name="`items[${item.key}][guest_count]`" class="{{ $inputClass }}"><input type="hidden" :name="`items[${item.key}][quantity]`" :value="item.pricing_type === 'per_person' ? item.guest_count : 1"></td>
                        <td class="p-2"><input type="number" step="0.01" min="0" v-model.number="item.discount_amount" :name="`items[${item.key}][discount_amount]`" class="{{ $inputClass }}"></td><td class="p-2 text-right font-semibold">@lang('admin::app.quotes.form.currency') @{{ money(lineTotal(item)) }}</td><td class="p-2"><button type="button" class="text-red-600" @click="removePriceItem(index)">×</button></td><input type="hidden" :name="`items[${item.key}][sort_order]`" :value="index + 1">
                    </tr>
                </tbody></table></div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <header class="mb-4 border-b border-gray-200 pb-3 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white">@lang('admin::app.quotes.form.terms-title')</h2><p class="text-xs text-gray-500">@lang('admin::app.quotes.form.terms-info')</p></header>
                <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.pricing-vat-terms')</label><textarea name="pricing_terms" rows="3" class="{{ $inputClass }}">{{ old('pricing_terms', $quote->pricing_terms ?: $settings?->pricing_terms) }}</textarea></div>
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.payment-terms')</label><textarea name="payment_terms" rows="4" class="{{ $inputClass }}">{{ old('payment_terms', $quote->payment_terms ?: $settings?->payment_terms) }}</textarea></div><div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.changes-terms')</label><textarea name="changes_terms" rows="4" class="{{ $inputClass }}">{{ old('changes_terms', $quote->changes_terms ?: $settings?->changes_terms) }}</textarea></div>
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.cancellation-terms')</label><textarea name="cancellation_terms" rows="4" class="{{ $inputClass }}">{{ old('cancellation_terms', $quote->cancellation_terms ?: $settings?->cancellation_terms) }}</textarea></div>
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.account-name')</label><input name="bank_account_name" value="{{ old('bank_account_name', $quote->bank_account_name ?: $settings?->bank_account_name) }}" class="{{ $inputClass }}"></div><div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.bank')</label><input name="bank_name" value="{{ old('bank_name', $quote->bank_name ?: $settings?->bank_name) }}" class="{{ $inputClass }}"></div>
                    <div class="col-span-2 max-md:col-span-1"><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.iban')</label><input name="iban" value="{{ old('iban', $quote->iban ?: $settings?->iban) }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.tiara-signatory')</label><input name="company_signatory_name" value="{{ old('company_signatory_name', $quote->company_signatory_name ?: $settings?->signatory_name) }}" class="{{ $inputClass }}"></div><div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.tiara-signatory-title')</label><input name="company_signatory_title" value="{{ old('company_signatory_title', $quote->company_signatory_title ?: $settings?->signatory_title) }}" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.client-signatory')</label><input name="client_signatory_name" value="{{ old('client_signatory_name', $quote->client_signatory_name) }}" class="{{ $inputClass }}"></div><div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.client-signatory-title')</label><input name="client_signatory_title" value="{{ old('client_signatory_title', $quote->client_signatory_title) }}" class="{{ $inputClass }}"></div>
                </div>
            </section>
        </div>

        <aside class="h-fit rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 xl:sticky xl:top-4">
            <h2 class="border-b border-gray-200 pb-3 font-semibold text-gray-800 dark:border-gray-800 dark:text-white">@lang('admin::app.quotes.form.summary')</h2>
            <dl class="mt-4 flex flex-col gap-3 text-sm"><div class="flex justify-between"><dt>@lang('admin::app.quotes.form.guests')</dt><dd class="font-semibold">@{{ guestCount }}</dd></div><div class="flex justify-between"><dt>@lang('admin::app.quotes.form.subtotal')</dt><dd>@lang('admin::app.quotes.form.currency') @{{ money(subtotal) }}</dd></div><div class="flex justify-between"><dt>@lang('admin::app.quotes.form.discount')</dt><dd>- @lang('admin::app.quotes.form.currency') @{{ money(discountTotal) }}</dd></div><div class="flex items-center justify-between gap-4"><dt>@lang('admin::app.quotes.form.adjustment')</dt><dd><input type="number" step="0.01" name="adjustment_amount" v-model.number="adjustment" class="{{ $inputClass }} !w-28 text-right"></dd></div><div class="flex items-center justify-between gap-4"><dt>@lang('admin::app.quotes.form.vat')</dt><dd class="flex items-center gap-1"><input type="number" step="0.01" min="0" max="100" name="vat_percent" v-model.number="vatPercent" class="{{ $inputClass }} !w-20 text-right"><span>%</span></dd></div><div class="mt-2 flex justify-between border-t border-gray-200 pt-4 text-base font-bold dark:border-gray-800"><dt>@lang('admin::app.quotes.form.grand-total')</dt><dd class="text-brandColor">@lang('admin::app.quotes.form.currency') @{{ money(grandTotal) }}</dd></div></dl>
            <p class="mt-4 rounded-md bg-green-50 p-3 text-xs text-green-700">@lang('admin::app.quotes.form.totals-note')</p>
        </aside>

        @if (bouncer()->hasPermission('contacts.persons.create'))
            <x-admin::modal ref="contactModal" size="medium">
                <x-slot:header><h3 class="text-lg font-semibold text-gray-800 dark:text-white">@lang('admin::app.quotes.form.add-contact-title')</h3></x-slot>
                <x-slot:content>
                    <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                        <div class="col-span-2 max-sm:col-span-1"><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.contact-name') *</label><input v-model.trim="newContact.name" class="{{ $inputClass }}"></div>
                        <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.company')</label><input v-model.trim="newContact.company" class="{{ $inputClass }}"></div>
                        <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.email') *</label><input type="email" v-model.trim="newContact.email" class="{{ $inputClass }}"></div>
                        <div><label class="{{ $labelClass }}">@lang('admin::app.quotes.form.mobile')</label><input v-model.trim="newContact.mobile" class="{{ $inputClass }}"></div>
                    </div>
                    <div v-if="contactError" class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700">@{{ contactError }}</div>
                </x-slot>
                <x-slot:footer>
                    <div class="flex justify-end gap-2"><button type="button" class="secondary-button" @click="$refs.contactModal.close()">@lang('admin::app.quotes.form.cancel')</button><button type="button" class="primary-button" :disabled="isCreatingContact" @click="createContact">@{{ isCreatingContact ? @json(trans('admin::app.quotes.form.saving')) : @json(trans('admin::app.quotes.form.save-contact')) }}</button></div>
                </x-slot>
            </x-admin::modal>
        @endif
    </div>
</script>

<script type="module">
    app.component('v-catering-proposal-builder', {
        template: '#v-catering-proposal-builder-template',
        props: ['initial', 'packages', 'categories', 'contacts'],
        data() {
            const contactOptions = [...(this.contacts || [])];
            const selectedContact = contactOptions.find(contact => Number(contact.id) === Number(this.initial.contactId));

            return {
                isArabic: @json(app()->isLocale('ar')),
                selectedContactId: this.initial.contactId || '',
                contactOptions,
                contactSearch: selectedContact ? [selectedContact.name, selectedContact.company, selectedContact.mobile].filter(Boolean).join(' — ') : '',
                contactResults: [],
                showContactResults: false,
                isSearchingContacts: false,
                contactSearchTimer: null,
                contactSearchRequest: 0,
                newContact: {name:'',company:'',email:'',mobile:''},
                contactError: '',
                isCreatingContact: false,
                guestCount: this.initial.guestCount || 1,
                vatPercent: this.initial.vatPercent ?? 15,
                adjustment: this.initial.adjustment || 0,
                items: (this.initial.items || []).map((item, index) => ({...item, key: item.key || `item_${Date.now()}_${index}`})),
                menuSections: (this.initial.menuSections || []).map((section, index) => ({...section, _key: `section_${index}`, _expanded: false, selectedProduct: '', items: section.items || []})),
                setupDescription: this.initial.setupDescription || '',
                serviceInclusions: this.initial.serviceInclusions || '',
                selectedPackage: '',
            };
        },
        computed: {selectedContact() { return this.contactOptions.find(contact => Number(contact.id) === Number(this.selectedContactId)) || null; }, subtotal() { return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0); }, discountTotal() { return this.items.reduce((sum, item) => sum + Number(item.discount_amount || 0), 0); }, taxable() { return Math.max(0, this.subtotal - this.discountTotal + Number(this.adjustment || 0)); }, grandTotal() { return this.taxable + this.taxable * Number(this.vatPercent || 0) / 100; }},
        watch: {guestCount(value) { this.items.filter(item => item.pricing_type === 'per_person').forEach(item => item.guest_count = value); }},
        mounted() {
            this.handleOutsideContactClick = event => {
                if (!this.$refs.contactSearch?.contains(event.target)) this.showContactResults = false;
            };

            document.addEventListener('click', this.handleOutsideContactClick);
        },
        beforeUnmount() {
            clearTimeout(this.contactSearchTimer);
            document.removeEventListener('click', this.handleOutsideContactClick);
        },
        methods: {
            contactLabel(contact) { return [contact.name, contact.company, contact.mobile].filter(Boolean).join(' — '); },
            openContactResults() {
                if (!this.selectedContact || this.contactSearch !== this.contactLabel(this.selectedContact)) {
                    this.showContactResults = true;
                }
            },
            queueContactSearch() {
                clearTimeout(this.contactSearchTimer);
                this.showContactResults = true;

                if (this.selectedContact && this.contactSearch !== this.contactLabel(this.selectedContact)) {
                    this.selectedContactId = '';
                }

                const term = this.contactSearch.trim();

                if (term.length < 2) {
                    this.contactResults = [];
                    this.isSearchingContacts = false;
                    return;
                }

                const requestId = ++this.contactSearchRequest;
                this.contactSearchTimer = setTimeout(() => this.searchContacts(term, requestId), 300);
            },
            searchContacts(term, requestId) {
                this.isSearchingContacts = true;

                this.$axios.get('{{ route('admin.quotes.contacts.search') }}', {params: {q: term}})
                    .then(response => {
                        if (requestId !== this.contactSearchRequest) return;
                        this.contactResults = response.data.data || [];
                    })
                    .catch(() => {
                        if (requestId !== this.contactSearchRequest) return;
                        this.contactResults = [];
                        this.$emitter.emit('add-flash', {type:'error',message:@json(trans('admin::app.quotes.form.search-failed'))});
                    })
                    .finally(() => {
                        if (requestId === this.contactSearchRequest) this.isSearchingContacts = false;
                    });
            },
            selectContact(contact) {
                const existingIndex = this.contactOptions.findIndex(option => Number(option.id) === Number(contact.id));

                if (existingIndex === -1) this.contactOptions.push(contact);
                else this.contactOptions.splice(existingIndex, 1, contact);

                this.selectedContactId = String(contact.id);
                this.contactSearch = this.contactLabel(contact);
                this.contactResults = [];
                this.showContactResults = false;
            },
            createContact() {
                this.contactError = '';
                if (!this.newContact.name || !this.newContact.email) { this.contactError = @json(trans('admin::app.quotes.form.contact-required')); return; }
                this.isCreatingContact = true;
                const payload = {entity_type:'persons',name:this.newContact.name,organization_name:this.newContact.company,emails:[{label:'work',value:this.newContact.email}]};
                if (this.newContact.mobile) payload.contact_numbers = [{label:'work',value:this.newContact.mobile}];
                this.$axios.post('{{ route('admin.contacts.persons.store') }}', payload)
                    .then(response => { const person = response.data.data; const contact = {id:person.id,name:person.name,company:person.organization?.name || this.newContact.company,email:person.emails?.[0]?.value || this.newContact.email,mobile:person.contact_numbers?.[0]?.value || this.newContact.mobile}; this.selectContact(contact); this.newContact = {name:'',company:'',email:'',mobile:''}; this.$refs.contactModal.close(); this.$emitter.emit('add-flash', {type:'success',message:@json(trans('admin::app.quotes.form.contact-created'))}); })
                    .catch(error => { const errors = error.response?.data?.errors || {}; this.contactError = Object.values(errors).flat()[0] || error.response?.data?.message || @json(trans('admin::app.quotes.form.contact-create-failed')); })
                    .finally(() => this.isCreatingContact = false);
            },
            money(value) { return Number(value || 0).toLocaleString(this.isArabic ? 'ar-SA' : 'en-SA', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
            localizedName(item) { return this.isArabic ? (item.name_ar || item.name) : item.name; },
            lineTotal(item) { if (item.pricing_type === 'included') return 0; return Number(item.price || 0) * (item.pricing_type === 'per_person' ? Number(item.guest_count || this.guestCount || 0) : Number(item.quantity || 1)); },
            applyPackage() { const pkg = this.packages.find(item => Number(item.id) === Number(this.selectedPackage)); if (!pkg) return; const grouped = {}; (pkg.items || []).forEach(link => { const product = link.product; if (!product) return; const category = product.catering_menu_category || {id:null,name:'Menu'}; grouped[category.name] ||= {name:category.name,catering_menu_category_id:category.id,items:[]}; grouped[category.name].items.push({product_id:product.id,name:product.name || 'Unnamed dish',description:product.description || ''}); }); this.menuSections = Object.values(grouped).map((section, index) => ({...section,_key:`section_${Date.now()}_${index}`,_expanded:false,selectedProduct:''})); this.setupDescription = pkg.setup_description || ''; this.serviceInclusions = pkg.service_inclusions || ''; this.items = [{key:`item_${Date.now()}`,product_id:null,name:pkg.name,description:pkg.description || '',pricing_type:'per_person',price:Number(pkg.price_per_person),quantity:1,guest_count:this.guestCount,discount_amount:0}]; },
            addSection() { this.menuSections.push({_key:`section_${Date.now()}`,name:'',catering_menu_category_id:null,_expanded:true,selectedProduct:'',items:[]}); },
            productsFor(section) { const category = this.categories.find(item => Number(item.id) === Number(section.catering_menu_category_id)); return category?.products || this.categories.flatMap(item => item.products || []); },
            addCatalogProduct(section) { const product = this.categories.flatMap(item => item.products || []).find(item => Number(item.id) === Number(section.selectedProduct)); if (!product) return; section.items.push({product_id:product.id,name:product.name,description:product.description || ''}); section.selectedProduct = ''; },
            addPriceItem() { this.items.push({key:`item_${Date.now()}`,product_id:null,name:'',description:'',pricing_type:'fixed',price:0,quantity:1,guest_count:this.guestCount,discount_amount:0}); },
            removePriceItem(index) { if (this.items.length > 1) this.items.splice(index, 1); },
        },
    });
</script>
@endPushOnce
