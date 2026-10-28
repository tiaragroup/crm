@pushOnce('scripts')
<script type="text/x-template" id="tax-draft-items-template">
    <div class="flex flex-col gap-4">
        <div v-for="(item, index) in items" :key="item._key" class="rounded border p-4">
            <input type="hidden" :name="'items['+index+'][product_id]'" :value="item.product_id || ''">
            <input type="hidden" :name="'items['+index+'][source_quote_item_id]'" :value="item.source_quote_item_id || ''">
            <div class="grid grid-cols-3 gap-3 max-md:grid-cols-1">
                @foreach (['description_ar','description_en','quantity','unit_price','discount_amount','unit_code','tax_rate','exemption_code','exemption_reason'] as $field)
                    <label class="flex flex-col gap-1 text-sm"><span>@lang('admin::tax.'.$field)</span><input :name="'items['+index+'][{{ $field }}]'" v-model="item.{{ $field }}" class="rounded border border-gray-300 bg-white p-2 text-gray-900" @if (in_array($field, ['quantity','unit_price','discount_amount','tax_rate'])) inputmode="decimal" @endif @if (in_array($field, ['quantity','unit_price','unit_code'])) required @endif></label>
                @endforeach
                <label class="flex flex-col gap-1 text-sm"><span>@lang('admin::tax.price_basis')</span><select :name="'items['+index+'][price_basis]'" v-model="item.price_basis" class="rounded border bg-white p-2 text-gray-900"><option value="exclusive">@lang('admin::tax.exclusive')</option><option value="inclusive">@lang('admin::tax.inclusive')</option></select></label>
                <label class="flex flex-col gap-1 text-sm"><span>@lang('admin::tax.tax_category')</span><select :name="'items['+index+'][tax_category]'" v-model="item.tax_category" class="rounded border bg-white p-2 text-gray-900"><option value="">@lang('admin::tax.unclassified')</option>@foreach (['S','Z','E','O'] as $category)<option value="{{ $category }}">@lang('admin::tax.'.$category)</option>@endforeach</select></label>
            </div>
            <button type="button" class="secondary-button mt-3" @click="items.splice(index, 1)" :disabled="items.length === 1">@lang('admin::tax.remove-line')</button>
        </div>
        <button type="button" class="secondary-button" @click="add" :disabled="items.length >= 200">@lang('admin::tax.add-line')</button>
    </div>
</script>
<script type="module">
app.component('v-tax-draft-items', {
    template: '#tax-draft-items-template',
    props: ['initial'],
    data() {
        return { items: this.initial.map((item, index) => ({...item, tax_category: item.tax_category || '', _key: index})), nextKey: this.initial.length };
    },
    methods: {
        add() {
            this.items.push({_key: this.nextKey++, description_ar: '', description_en: '', quantity: '1', unit_price: '0', discount_amount: '0', unit_code: 'C62', price_basis: 'exclusive', tax_category: '', tax_rate: ''});
        }
    }
});
</script>
@endPushOnce

