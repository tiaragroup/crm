{!! view_render_event('admin.dashboard.index.top_salespeople.before') !!}

<v-dashboard-top-salespeople>
    <x-admin::shimmer.dashboard.index.top-persons />
</v-dashboard-top-salespeople>

{!! view_render_event('admin.dashboard.index.top_salespeople.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-top-salespeople-template"
    >
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.top-persons />
        </template>

        <template v-else>
            <div class="w-full rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between p-4">
                    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                        @lang('admin::app.dashboard.index.top-salespeople.title')
                    </p>
                </div>

                <div
                    class="flex flex-col"
                    v-if="report.statistics.length"
                >
                    <div
                        class="flex items-center gap-2.5 border-b p-4 last:border-b-0 dark:border-gray-800"
                        v-for="item in report.statistics"
                        :key="item.id"
                    >
                        <x-admin::avatar ::name="item.name" />

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-800 dark:text-white">
                                @{{ item.name }}
                            </p>

                            <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                                @{{ item.email }}
                            </p>
                        </div>

                        <div class="shrink-0 text-end">
                            <p class="font-semibold text-gray-800 dark:text-white">
                                @{{ item.won_count }}
                                @lang('admin::app.dashboard.index.top-salespeople.confirmed-won')
                            </p>

                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @{{ item.formatted_revenue }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col gap-8 p-4"
                    v-else
                >
                    <div class="grid justify-center justify-items-center gap-3.5 py-2.5">
                        <img
                            src="{{ vite()->asset('images/empty-placeholders/users.svg') }}"
                            class="dark:mix-blend-exclusion dark:invert"
                        >

                        <div class="flex flex-col items-center">
                            <p class="text-base font-semibold text-gray-400">
                                @lang('admin::app.dashboard.index.top-salespeople.empty-title')
                            </p>

                            <p class="text-center text-gray-400">
                                @lang('admin::app.dashboard.index.top-salespeople.empty-info')
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-top-salespeople', {
            template: '#v-dashboard-top-salespeople-template',

            data() {
                return {
                    report: [],
                    isLoading: true,
                };
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    filters = Object.assign({}, filters, {
                        type: 'top-salespeople',
                    });

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters,
                        })
                        .then(response => {
                            this.report = response.data;
                        })
                        .finally(() => {
                            this.isLoading = false;
                        });
                },
            },
        });
    </script>
@endPushOnce
