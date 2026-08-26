@php
    $requestedPipelineId = request('pipeline_id');
    $isProspectRequest = $accountProspectPipeline && (int) $requestedPipelineId === (int) $accountProspectPipeline->id;
    $initialOpportunityTypeId = old('opportunity_type')
        ?? ($isProspectRequest ? $accountProspectOption?->id : $eventInquiryOption?->id);
    $initialPipelineId = $requestedPipelineId
        ?? ($isProspectRequest ? $accountProspectPipeline?->id : $eventSalesPipeline?->id);
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.leads.create.title')
    </x-slot>

    {!! view_render_event('admin.leads.create.form.before') !!}

    <!-- Create Lead Form -->
    <x-admin::form :action="route('admin.leads.store')">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <x-admin::breadcrumbs name="leads.create" />

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.leads.create.title')
                    </div>
                </div>

                {!! view_render_event('admin.leads.create.save_button.before') !!}

                <div class="flex items-center gap-x-2.5">
                    <!-- Save button for person -->
                    <div class="flex items-center gap-x-2.5">
                        {!! view_render_event('admin.leads.create.form_buttons.before') !!}

                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.leads.create.save-btn')
                        </button>

                        {!! view_render_event('admin.leads.create.form_buttons.after') !!}
                    </div>
                </div>

                {!! view_render_event('admin.leads.create.save_button.after') !!}
            </div>

            <!-- Lead Create Component -->
            <v-lead-create>
                <x-admin::shimmer.leads.datagrid />
            </v-lead-create>
        </div>
    </x-admin::form>

    {!! view_render_event('admin.leads.create.form.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-lead-create-template"
        >
            <div class="box-shadow flex flex-col gap-4 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <input type="hidden" name="lead_pipeline_id" :value="selectedPipelineId" />
                <input v-if="selectedStageId" type="hidden" name="lead_pipeline_stage_id" :value="selectedStageId" />

                {!! view_render_event('admin.leads.edit.form_controls.before') !!}

                <div class="flex w-full gap-2 border-b border-gray-200 dark:border-gray-800">
                    <!-- Tabs -->
                    <template
                        v-for="tab in tabs"
                        :key="tab.id"
                    >
                        {!! view_render_event('admin.leads.create.tabs.before') !!}

                        <a
                            :href="'#' + tab.id"
                            :class="[
                                'inline-block px-3 py-2.5 border-b-2  text-sm font-medium ',
                                activeTab === tab.id
                                ? 'text-brandColor border-brandColor dark:brandColor dark:brandColor'
                                : 'text-gray-600 dark:text-gray-300  border-transparent hover:text-gray-800 hover:border-gray-400 dark:hover:border-gray-400  dark:hover:text-white'
                            ]"
                            @click="scrollToSection(tab.id)"
                            :text="tab.label"
                        >
                        </a>

                        {!! view_render_event('admin.leads.create.tabs.after') !!}
                    </template>
                </div>

                <div class="flex flex-col gap-4 px-4 py-2">
                    {!! view_render_event('admin.leads.create.details.before') !!}

                    <!-- Details section -->
                    <div
                        class="flex flex-col gap-4"
                        id="lead-details"
                    >
                        <div class="flex flex-col gap-1">
                            <p class="text-base font-semibold dark:text-white">
                                @lang('admin::app.leads.create.details')
                            </p>

                            <p class="text-gray-600 dark:text-white">
                                @lang('admin::app.leads.create.details-info')
                            </p>
                        </div>

                        <div class="w-full max-w-5xl">
                            {!! view_render_event('admin.leads.create.details.attributes.before') !!}

                            <!-- Lead Details Title and Description -->
                            <x-admin::attributes
                                :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                    ['code', 'NOTIN', [
                                        'lead_value', 'lead_type_id', 'lead_source_id', 'expected_close_date', 'user_id',
                                        'lead_pipeline_id', 'lead_pipeline_stage_id', 'opportunity_type', 'interested_services',
                                        'event_date', 'guest_count', 'venue_location', 'event_type', 'service_style',
                                        'dietary_requirements', 'budget_range', 'date_flexibility', 'business_category',
                                        'catering_frequency', 'potential_guest_volume', 'next_follow_up_date'
                                    ]],
                                    'entity_type' => 'leads',
                                    'quick_add'   => 1
                                ])"
                                :custom-validations="[
                                    'expected_close_date' => [
                                        'date_format:yyyy-MM-dd',
                                        'after:' .  \Carbon\Carbon::yesterday()->format('Y-m-d')
                                    ],
                                ]"
                            />

                            @include('admin::leads.common.catering-opportunity-fields')

                            <!-- Lead Details Other input fields -->
                            <div class="flex gap-4 max-sm:flex-wrap">
                                <div class="w-full">
                                    <x-admin::attributes
                                        :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                            ['code', 'IN', ['lead_value', 'lead_type_id', 'lead_source_id']],
                                            'entity_type' => 'leads',
                                            'quick_add'   => 1
                                        ])"
                                        :custom-validations="[
                                            'expected_close_date' => [
                                                'date_format:yyyy-MM-dd',
                                                'after:' .  \Carbon\Carbon::yesterday()->format('Y-m-d')
                                            ],
                                        ]"
                                    />
                                </div>

                                <div class="w-full">
                                    <x-admin::attributes
                                        :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                            ['code', 'IN', ['user_id']],
                                            'entity_type' => 'leads',
                                            'quick_add'   => 1
                                        ])"
                                        :custom-validations="[
                                            'expected_close_date' => [
                                                'date_format:yyyy-MM-dd',
                                                'after:' .  \Carbon\Carbon::yesterday()->format('Y-m-d')
                                            ],
                                        ]"
                                    />
                                </div>
                            </div>

                            {!! view_render_event('admin.leads.create.details.attributes.after') !!}
                        </div>
                    </div>

                    {!! view_render_event('admin.leads.create.details.after') !!}

                    {!! view_render_event('admin.leads.create.contact_person.before') !!}

                    <!-- Contact Person -->
                    <div
                        class="flex flex-col gap-4"
                        id="contact-person"
                    >
                        <div class="flex flex-col gap-1">
                            <p class="text-base font-semibold dark:text-white">
                                @lang('admin::app.leads.create.contact-person')
                            </p>

                            <p class="text-gray-600 dark:text-white">
                                @lang('admin::app.leads.create.contact-info')
                            </p>
                        </div>

                        <div class="w-1/2 max-md:w-full">
                            <!-- Contact Person Component -->
                            @include('admin::leads.common.contact')
                        </div>
                    </div>

                    {!! view_render_event('admin.leads.create.contact_person.after') !!}

                </div>

                {!! view_render_event('admin.leads.form_controls.after') !!}
            </div>
        </script>

        <script type="module">
            app.component('v-lead-create', {
                template: '#v-lead-create-template',

                data() {
                    return {
                        activeTab: 'lead-details',

                        opportunityType: @json((string) $initialOpportunityTypeId),

                        eventInquiryOptionId: @json((string) $eventInquiryOption?->id),

                        accountProspectOptionId: @json((string) $accountProspectOption?->id),

                        eventPipelineId: @json($eventSalesPipeline?->id),

                        accountProspectPipelineId: @json($accountProspectPipeline?->id),

                        selectedPipelineId: @json($initialPipelineId),

                        selectedStageId: @json(request('stage_id')),

                        tabs: [
                            { id: 'lead-details', label: '@lang('admin::app.leads.create.details')' },
                            { id: 'contact-person', label: '@lang('admin::app.leads.create.contact-person')' }
                        ],
                    };
                },

                computed: {
                    isEventInquiry() {
                        return String(this.opportunityType) === String(this.eventInquiryOptionId);
                    },

                    opportunityHelpTitle() {
                        return this.isEventInquiry
                            ? @json(trans('admin::app.leads.opportunity.event-help-title'))
                            : @json(trans('admin::app.leads.opportunity.prospect-help-title'));
                    },

                    opportunityHelpText() {
                        return this.isEventInquiry
                            ? @json(trans('admin::app.leads.opportunity.event-help-text'))
                            : @json(trans('admin::app.leads.opportunity.prospect-help-text'));
                    },
                },

                methods: {
                    syncOpportunityPipeline() {
                        const targetPipelineId = this.isEventInquiry
                            ? this.eventPipelineId
                            : this.accountProspectPipelineId;

                        if (String(this.selectedPipelineId) !== String(targetPipelineId)) {
                            this.selectedStageId = null;
                        }

                        this.selectedPipelineId = targetPipelineId;
                    },

                    /**
                     * Scroll to the section.
                     *
                     * @param {String} tabId
                     *
                     * @returns {void}
                     */
                    scrollToSection(tabId) {
                        const section = document.getElementById(tabId);

                        if (section) {
                            section.scrollIntoView({ behavior: 'smooth' });
                        }
                    },
                },
            });
        </script>
    @endPushOnce

    @pushOnce('styles')
        <style>
            html {
                scroll-behavior: smooth;
            }
        </style>
    @endPushOnce
</x-admin::layouts>
