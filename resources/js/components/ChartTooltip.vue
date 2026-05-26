<template>
    <span v-if="text" class="relative inline-flex items-center">
        <button
            ref="trigger"
            type="button"
            class="cursor-help inline-flex items-center bg-transparent border-0 p-0"
            :aria-label="text"
            @mouseenter="show"
            @mouseleave="hide"
            @focus="show"
            @blur="hide"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                fill="none"
                viewBox="0 0 16 16"
                :class="['text-slate-800 dark:text-white', visible ? 'opacity-50' : 'opacity-30']"
            >
                <path
                    fill="currentColor"
                    d="M8 16c-4.406 0-8-3.594-8-8s3.594-8 8-8 8 3.594 8 8-3.594 8-8 8M8 5.5c.563 0 1 .438 1 1 0 .438-.219.75-.562.969s-.688.312-1.063.437c-.031 0-.094 0-.125.032v1.75h1.5V9a3.5 3.5 0 0 0 .563-.312C9.875 8.312 10.5 7.594 10.5 6.5 10.5 5.125 9.375 4 8 4a2.507 2.507 0 0 0-2.5 2.5H7c0-.562.438-1 1-1m-.75 5.25v1.5h1.5v-1.5z"
                />
            </svg>
        </button>

        <teleport to="body">
            <span
                v-if="visible"
                ref="tooltip"
                role="tooltip"
                class="pointer-events-none fixed z-[9999] text-xs leading-snug rounded px-2 py-1 shadow-lg whitespace-normal max-w-xs bg-slate-800 text-white dark:bg-slate-200 dark:text-gray-900 dark:ring-1 dark:ring-slate-700"
                :style="positionStyle"
            >
                {{ text }}
            </span>
        </teleport>
    </span>
</template>

<script>
export default {
    name: 'ChartTooltip',
    props: {
        text: {
            type: String,
            default: null,
        },
    },
    data() {
        return {
            visible: false,
            positionStyle: { top: '0px', left: '0px' },
        };
    },
    methods: {
        show() {
            this.visible = true;
            this.$nextTick(this.updatePosition);
            window.addEventListener('scroll', this.updatePosition, true);
            window.addEventListener('resize', this.updatePosition);
        },
        hide() {
            this.visible = false;
            window.removeEventListener('scroll', this.updatePosition, true);
            window.removeEventListener('resize', this.updatePosition);
        },
        updatePosition() {
            const trigger = this.$refs.trigger;
            const tooltip = this.$refs.tooltip;
            if (!trigger || !tooltip) return;
            const triggerRect = trigger.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            this.positionStyle = {
                top: `${triggerRect.top - tooltipRect.height - 6}px`,
                left: `${triggerRect.left + triggerRect.width / 2 - tooltipRect.width / 2}px`,
            };
        },
    },
    beforeUnmount() {
        window.removeEventListener('scroll', this.updatePosition, true);
        window.removeEventListener('resize', this.updatePosition);
    },
};
</script>
