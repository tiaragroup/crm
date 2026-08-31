@php
    $attributeRepository = app('Webkul\Attribute\Repositories\AttributeRepository');
    $opportunityEntity = $lead ?? null;

    $sharedOpportunityAttributes = $attributeRepository->findWhere([
        ['code', 'IN', ['interested_services']],
        'entity_type' => 'leads',
        'quick_add'   => 1,
    ])->sortBy('sort_order');

    $eventOpportunityAttributes = $attributeRepository->findWhere([
        ['code', 'IN', [
            'event_date',
            'guest_count',
            'venue_location',
            'event_type',
            'service_style',
            'dietary_requirements',
            'budget_range',
            'date_flexibility',
            'expected_close_date',
        ]],
        'entity_type' => 'leads',
        'quick_add'   => 1,
    ])->sortBy('sort_order');

    $prospectOpportunityAttributes = $attributeRepository->findWhere([
        ['code', 'IN', [
            'business_category',
            'catering_frequency',
            'potential_guest_volume',
            'next_follow_up_date',
        ]],
        'entity_type' => 'leads',
        'quick_add'   => 1,
    ])->sortBy('sort_order');
@endphp

<div class="mt-5 flex flex-col gap-5 border-t border-gray-200 pt-5 dark:border-gray-800">
    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
        <x-admin::form.control-group class="!mb-0">
            <x-admin::form.control-group.label class="required">
                @lang('admin::app.leads.opportunity.type')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="select"
                name="opportunity_type"
                rules="required"
                v-model="opportunityType"
                @change="syncOpportunityPipeline"
            >
                @foreach ($opportunityTypeAttribute?->options()->orderBy('sort_order')->get() ?? [] as $option)
                    @php
                        $optionLabelKey = 'admin::app.attribute-options.opportunity_type.'.\Illuminate\Support\Str::slug($option->name);
                        $translatedOptionLabel = trans($optionLabelKey);
                        $optionLabel = $translatedOptionLabel !== $optionLabelKey ? $translatedOptionLabel : $option->name;
                    @endphp

                    <option value="{{ $option->id }}">{{ $optionLabel }}</option>
                @endforeach
            </x-admin::form.control-group.control>

            <x-admin::form.control-group.error control-name="opportunity_type" />
        </x-admin::form.control-group>

        <div class="rounded-lg border border-brandColor/20 bg-brandColor/5 px-4 py-3 text-sm text-gray-600 dark:border-brandColor/30 dark:text-gray-300">
            <p class="font-semibold text-gray-800 dark:text-white" v-text="opportunityHelpTitle"></p>
            <p class="mt-1" v-text="opportunityHelpText"></p>
        </div>
    </div>

    @if ($sharedOpportunityAttributes->isNotEmpty())
        <div>
            <p class="mb-2 text-sm font-semibold text-gray-800 dark:text-white">
                @lang('admin::app.leads.opportunity.interests')
            </p>

            <x-admin::attributes
                :custom-attributes="$sharedOpportunityAttributes"
                :entity="$opportunityEntity"
            />

            <p class="mt-1 text-xs text-gray-500">
                @lang('admin::app.leads.opportunity.interests-info')
            </p>
        </div>
    @endif

    <div
        v-if="isEventInquiry"
        class="rounded-lg border border-gray-200 p-4 dark:border-gray-800"
    >
        <div class="mb-4">
            <p class="font-semibold text-gray-800 dark:text-white">
                @lang('admin::app.leads.opportunity.event-details')
            </p>

            <p class="text-sm text-gray-500">
                @lang('admin::app.leads.opportunity.event-details-info')
            </p>
        </div>

        <div class="grid grid-cols-2 gap-x-4 max-md:grid-cols-1">
            <x-admin::attributes
                :custom-attributes="$eventOpportunityAttributes"
                :entity="$opportunityEntity"
            />
        </div>
    </div>

    <div
        v-else
        class="rounded-lg border border-gray-200 p-4 dark:border-gray-800"
    >
        <div class="mb-4">
            <p class="font-semibold text-gray-800 dark:text-white">
                @lang('admin::app.leads.opportunity.prospect-details')
            </p>

            <p class="text-sm text-gray-500">
                @lang('admin::app.leads.opportunity.prospect-details-info')
            </p>
        </div>

        <div class="grid grid-cols-2 gap-x-4 max-md:grid-cols-1">
            <x-admin::attributes
                :custom-attributes="$prospectOpportunityAttributes"
                :entity="$opportunityEntity"
            />
        </div>
    </div>

    <div class="rounded-lg bg-gray-50 px-4 py-3 text-xs text-gray-500 dark:bg-gray-950 dark:text-gray-400">
        @lang('admin::app.leads.opportunity.quotation-note')
    </div>
</div>
