<v-lookup {{ $attributes }}></v-lookup>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-lookup-template"
    >
        <div
            class="relative"
            ref="lookup"
        >
            <!-- Input Box (Button) -->
            <div
                class="relative inline-block w-full"
                @click="toggle"
            >
                <!-- Input Container -->
                <div class="relative flex cursor-pointer items-center justify-between rounded border border-gray-200 p-2 hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:text-gray-300">
                    <!-- Selected Item or Placeholder Text -->
                    <span
                        class="overflow-hidden text-ellipsis"
                        :title="selectedItem?.name"
                    >
                        @{{ selectedItem?.name !== "" ? selectedItem?.name : "@lang('admin::app.components.lookup.click-to-add')" }}
                    </span>

                    <!-- Icons Container -->
                    <div class="flex items-center gap-2">
                        <!-- Close Icon -->
                        <i
                            v-if="(selectedItem?.name) && ! isSearching"
                            class="icon-cross-large cursor-pointer text-xl text-gray-600"
                            @click.stop="remove"
                        ></i>

                        <!-- Arrow Icon -->
                        <i
                            class="text-2xl text-gray-600"
                            :class="showPopup ? 'icon-up-arrow' : 'icon-down-arrow'"
                        ></i>
                    </div>
                </div>
            </div>

            <!-- Hidden Input Box -->
            <x-admin::form.control-group.control
                type="hidden"
                ::name="name"
                ::rules="rules"
                ::label="label"
                v-model="selectedItem.id"
            />

            <!-- Popup Box -->
            <div
                v-if="showPopup"
                class="absolute top-full z-10 mt-1 flex w-full origin-top transform flex-col gap-2 rounded-lg border border-gray-200 bg-white p-2 shadow-lg transition-transform dark:border-gray-900 dark:bg-gray-800"
            >
                <!-- Search Bar -->
                <div class="relative flex items-center">
                    <input
                        type="text"
                        v-model="searchTerm"
                        class="w-full rounded border border-gray-200 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                        placeholder="@lang('admin::app.components.lookup.search')"
                        ref="searchInput"
                    />

                    <!-- Search Icon (absolute positioned) -->
                    <span class="absolute flex items-center ltr:right-2 rtl:left-2">
                        <!-- Loader (optional, based on condition) -->
                        <div
                            class="relative"
                            v-if="isSearching"
                        >
                            <x-admin::spinner />
                        </div>
                    </span>
                </div>

                <!-- Results List -->
                <ul
                    class="max-h-40 divide-y divide-gray-100 overflow-y-auto"
                    @scroll.passive="handleResultsScroll"
                >
                    <li
                        v-for="item in filteredResults"
                        :key="item.id"
                        class="cursor-pointer px-4 py-2 text-gray-800 transition-colors hover:bg-blue-100 dark:text-white dark:hover:bg-gray-900"
                        @click="selectItem(item)"
                    >
                        @{{ item.name }}
                    </li>

                    <li v-if="isSearching && filteredResults.length === 0" class="flex justify-center px-4 py-4">
                        <x-admin::spinner />
                    </li>

                    <template v-else-if="filteredResults.length === 0">
                        <li class="px-4 py-2 text-gray-500">
                            @lang('admin::app.components.lookup.no-results')
                        </li>
                    </template>

                    <li v-if="isLoadingMore" class="flex justify-center px-4 py-3">
                        <x-admin::spinner />
                    </li>
                </ul>

                <button
                    v-if="canAddNew && searchTerm.trim() && hasSearched && ! isSearching && ! searchFailed && filteredResults.length === 0"
                    type="button"
                    class="flex w-full shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-md border border-brandColor px-3 py-2 text-sm font-semibold text-brandColor transition-colors hover:bg-brandColor hover:text-white"
                    @click.stop="selectItem({ id: '', name: searchTerm.trim() })"
                >
                    <i class="icon-add text-lg"></i>

                    <span>@lang('admin::app.components.lookup.add-as-new')</span>

                    <span v-if="searchTerm.trim()">“@{{ searchTerm.trim() }}”</span>
                </button>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-lookup', {
            template: '#v-lookup-template',

            props: {
                src: {
                    type: String,
                    required: true,
                },

                params: {
                    type: Object,
                    default: () => ({}),
                },

                name: {
                    type: String,
                    required: true,
                },

                placeholder: {
                    type: String,
                    required: true,
                },

                value: {
                    type: Object,
                    default: () => ({}),
                },

                rules: {
                    type: String,
                    default: '',
                },

                label: {
                    type: String,
                    default: '',
                },

                canAddNew: {
                    type: Boolean,
                    default: false,
                },

                preload: {
                    type: Boolean,
                    default: false,
                }
            },

            emits: ['on-selected'],

            data() {
                return {
                    showPopup: false,

                    searchTerm: '',

                    selectedItem: {},

                    searchedResults: [],

                    isSearching: false,

                    cancelToken: null,

                    isLoadingMore: false,

                    page: 1,

                    perPage: 10,

                    hasMore: true,

                    searchTimer: null,

                    requestSequence: 0,

                    ignoreNextSearch: false,

                    hasSearched: false,

                    searchFailed: false,
                };
            },

            mounted() {
                if (this.value) {
                    this.selectedItem = this.value;
                }

                if (this.preload) {
                    this.search(true);
                }
            },

            created() {
                window.addEventListener('click', this.handleFocusOut);
            },

            beforeUnmount() {
                window.removeEventListener('click', this.handleFocusOut);

                clearTimeout(this.searchTimer);

                this.cancelToken?.cancel();
            },

            watch: {
                searchTerm() {
                    if (this.ignoreNextSearch) {
                        this.ignoreNextSearch = false;

                        return;
                    }

                    clearTimeout(this.searchTimer);

                    this.searchTimer = setTimeout(() => this.search(true), 300);
                },
            },

            computed: {
                /**
                 * Filter the searchedResults based on the search query.
                 *
                 * @return {Array}
                 */
                filteredResults() {
                    return this.searchedResults;
                }
            },

            methods: {
                /**
                 * Toggle the popup.
                 *
                 * @return {void}
                 */
                toggle() {
                    this.showPopup = ! this.showPopup;

                    if (this.showPopup) {
                        this.$nextTick(() => {
                            this.$refs.searchInput.focus();

                            if (! this.searchedResults.length) {
                                this.search(true);
                            }
                        });
                    }
                },

                /**
                 * Select an item from the list.
                 *
                 * @param {Object} item
                 *
                 * @return {void}
                 */
                selectItem(item) {
                    this.showPopup = false;

                    this.ignoreNextSearch = this.searchTerm !== '';

                    this.searchTerm = '';

                    this.selectedItem = item;

                    this.$emit('on-selected', item);
                },

                /**
                 * Initialize the items.
                 *
                 * @return {void}
                 */
                search(reset = true) {
                    if (! reset && (! this.hasMore || this.isLoadingMore || this.isSearching)) {
                        return;
                    }

                    if (reset) {
                        this.page = 1;
                        this.hasMore = true;
                        this.searchedResults = [];
                        this.isSearching = true;
                        this.hasSearched = false;
                        this.searchFailed = false;

                        this.cancelToken?.cancel();
                        this.cancelToken = this.$axios.CancelToken.source();
                    } else {
                        this.isLoadingMore = true;
                    }

                    const requestSequence = ++this.requestSequence;
                    const requestedPage = this.page;

                    this.$axios.get(this.src, {
                            params: {
                                ...this.params,
                                query: this.searchTerm.trim(),
                                paginate: 1,
                                page: requestedPage,
                                per_page: this.perPage,
                            },
                            cancelToken: this.cancelToken?.token,
                        })
                        .then(response => {
                            if (requestSequence !== this.requestSequence) {
                                return;
                            }

                            const payload = response.data;
                            const results = Array.isArray(payload) ? payload : (payload.data ?? []);

                            this.searchedResults = reset
                                ? results
                                : this.mergeResults(this.searchedResults, results);

                            const currentPage = payload.meta?.current_page ?? payload.current_page;
                            const lastPage = payload.meta?.last_page ?? payload.last_page;

                            this.hasMore = Array.isArray(payload)
                                ? false
                                : currentPage < lastPage;

                            this.hasSearched = true;
                            this.searchFailed = false;

                            if (this.hasMore) {
                                this.page = requestedPage + 1;
                            }
                        })
                        .catch(error => {
                            if (! this.$axios.isCancel(error)) {
                                console.error("Search request failed:", error);

                                if (requestSequence === this.requestSequence) {
                                    this.hasMore = false;
                                    this.hasSearched = false;
                                    this.searchFailed = true;
                                }
                            }
                        })
                        .finally(() => {
                            if (requestSequence === this.requestSequence) {
                                this.isSearching = false;
                                this.isLoadingMore = false;
                            }
                        });
                },

                mergeResults(currentResults, newResults) {
                    const results = new Map(currentResults.map(item => [String(item.id), item]));

                    newResults.forEach(item => results.set(String(item.id), item));

                    return Array.from(results.values());
                },

                handleResultsScroll(event) {
                    const list = event.currentTarget;

                    if (list.scrollTop + list.clientHeight >= list.scrollHeight - 32) {
                        this.search(false);
                    }
                },

                /**
                 * Handle the focus out event.
                 *
                 * @param {Event} event
                 *
                 * @return {void}
                 */
                handleFocusOut(event) {
                    const lookup = this.$refs.lookup;

                    if (
                        lookup &&
                        ! lookup.contains(event.target)
                    ) {
                        this.showPopup = false;
                    }
                },

                /**
                 * Remove the selected item.
                 *
                 * @return {void}
                 */
                remove() {
                    this.selectedItem = {
                        id: '',
                        name: '',
                    };

                    this.$emit('on-selected', {});
                }
            },
        });
    </script>
@endPushOnce
