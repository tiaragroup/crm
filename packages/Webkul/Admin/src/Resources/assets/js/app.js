/**
 * This will track all the images and fonts for publishing.
 */
import.meta.glob(["../images/**", "../fonts/**"]);

/**
 * Main vue bundler.
 */
import { createApp } from "vue/dist/vue.esm-bundler";

/**
 * Main root application registry.
 */
window.app = createApp({
    data() {
        return {
            isMenuActive: false,

            hoveringMenu: '',
        };
    },

    created() {
        window.addEventListener('click', this.handleFocusOut);
    },

    beforeDestroy() {
        window.removeEventListener('click', this.handleFocusOut);
    },

    methods: {
        onSubmit() {},

        onInvalidSubmit({ errors }) {
            setTimeout(() => {
                const errorKeys = Object.entries(errors)
                    .map(([key, value]) => ({ key, value }))
                    .filter(error => error.value?.length);

                if (! errorKeys.length) {
                    return;
                }

                const firstErrorKey = errorKeys[0].key;
                const namedElements = Array.from(document.getElementsByName(firstErrorKey));
                const firstErrorElement = namedElements.find(element => element.type !== 'hidden')
                    ?? namedElements[0];

                const scrollTarget = firstErrorElement?.closest('.mb-2\\.5')
                    ?? firstErrorElement;

                if (scrollTarget) {
                    scrollTarget.scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });
                }

                if (firstErrorElement && firstErrorElement.type !== 'hidden') {
                    firstErrorElement.focus({ preventScroll: true });
                }

                this.$emitter.emit('add-flash', {
                    type: 'error',
                    message: document.documentElement.lang === 'ar'
                        ? 'يرجى تصحيح الحقول المحددة قبل الحفظ.'
                        : 'Please correct the highlighted fields before saving.',
                });
            }, 100);
        },

        handleMouseOver(event) {
            if (this.isMenuActive) {
                return;
            }

            const parentElement = event.currentTarget.parentElement;

            if (parentElement.classList.contains('sidebar-collapsed')) {
                parentElement.classList.remove('sidebar-collapsed');

                parentElement.classList.add('sidebar-not-collapsed');
            }

        },

        handleMouseLeave(event) {
            if (this.isMenuActive) {
                return;
            }

            const parentElement = event.currentTarget.parentElement;

            if (parentElement.classList.contains('sidebar-not-collapsed')) {
                parentElement.classList.remove('sidebar-not-collapsed');

                parentElement.classList.add('sidebar-collapsed');
            }
        },

        handleFocusOut(event) {
            const sidebar = this.$refs.sidebar;

            if (
                sidebar &&
                !sidebar.contains(event.target)
            ) {
                this.isMenuActive = false;

                const parentElement = sidebar.parentElement;

                if (parentElement.classList.contains('sidebar-not-collapsed')) {
                    parentElement.classList.remove('sidebar-not-collapsed');

                    parentElement.classList.add('sidebar-collapsed');
                }
            }
        },
    },
});

/**
 * Global plugins registration.
 */
import Admin from "./plugins/admin";
import Axios from "./plugins/axios";
import Emitter from "./plugins/emitter";
import Flatpickr from "./plugins/flatpickr";
import VeeValidate from "./plugins/vee-validate";
import CreateElement from "./plugins/createElement";
import Draggable from "./plugins/draggable";
import VueCal from "./plugins/vue-cal";

[
    Admin,
    Axios,
    Emitter,
    CreateElement,
    Draggable,
    Flatpickr,
    VeeValidate,
    VueCal,
].forEach((plugin) => app.use(plugin));

/**
 * Global directives.
 */
import Debounce from "./directives/debounce";
import DOMPurify from "./directives/dompurify";
import ToolTip from "./directives/tooltip";

app.directive("debounce", Debounce);
app.directive("safe-html", DOMPurify);
app.directive("tooltip", ToolTip);

export default app;
