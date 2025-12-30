<script setup>
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    growth: {
        type: Number,
        default: null,
    },
    icon: {
        type: Object,
        default: null,
    },
    iconColor: {
        type: String,
        default: "blue",
    },
    hoverColor: {
        type: String,
        default: "blue",
    },
    badge: {
        type: String,
        default: null,
    },
    badgeColor: {
        type: String,
        default: "amber",
    },
    sparklineData: {
        type: Array,
        default: null,
    },
    sparklineColor: {
        type: String,
        default: "emerald",
    },
    progressBar: {
        type: Number,
        default: null,
    },
    stars: {
        type: Number,
        default: null,
    },
    maxStars: {
        type: Number,
        default: 5,
    },
    subtitle: {
        type: String,
        default: null,
    },
});

const iconColorClasses = computed(() => {
    const colors = {
        blue: "from-blue-500 to-blue-600 shadow-blue-500/25 group-hover:shadow-blue-500/30",
        purple:
            "from-purple-500 to-purple-600 shadow-purple-500/25 group-hover:shadow-purple-500/30",
        emerald:
            "from-emerald-500 to-emerald-600 shadow-emerald-500/25 group-hover:shadow-emerald-500/30",
        amber: "from-amber-500 to-amber-600 shadow-amber-500/25 group-hover:shadow-amber-500/30",
    };
    return colors[props.iconColor] || colors.blue;
});

const hoverBorderClass = computed(() => {
    const colors = {
        blue: "hover:border-blue-300",
        purple: "hover:border-purple-300",
        emerald: "hover:border-emerald-300",
        amber: "hover:border-amber-300",
    };
    return colors[props.hoverColor] || colors.blue;
});

const glowColorClass = computed(() => {
    const colors = {
        blue: "bg-blue-50/50 group-hover:bg-blue-100/70",
        purple: "bg-purple-50/50 group-hover:bg-purple-100/70",
        emerald: "bg-emerald-50/50 group-hover:bg-emerald-100/70",
        amber: "bg-amber-50/50 group-hover:bg-amber-100/70",
    };
    return colors[props.hoverColor] || colors.blue;
});

const badgeColorClasses = computed(() => {
    const colors = {
        amber: "bg-amber-50 text-amber-700 ring-amber-100",
        emerald: "bg-emerald-50 text-emerald-700 ring-emerald-100",
    };
    return colors[props.badgeColor] || colors.amber;
});

const progressColorClass = computed(() => {
    const colors = {
        emerald: "from-emerald-500 to-emerald-600",
    };
    return colors[props.hoverColor] || "from-emerald-500 to-emerald-600";
});

const sparklineColorValue = computed(() => {
    const colors = {
        emerald: "#10b981",
        blue: "#3b82f6",
        purple: "#8b5cf6",
        amber: "#f59e0b",
    };
    return colors[props.sparklineColor] || colors.emerald;
});

const sparklineOptions = computed(() => {
    const color = sparklineColorValue.value;
    return {
        chart: {
            type: "area",
            sparkline: {
                enabled: true,
            },
            animations: {
                enabled: true,
                easing: "easeinout",
                speed: 800,
            },
        },
        colors: [color],
        stroke: {
            curve: "smooth",
            width: 2.5,
        },
        fill: {
            type: "gradient",
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1,
                stops: [0, 90, 100],
            },
        },
        tooltip: {
            enabled: false,
        },
    };
});
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
        :class="hoverBorderClass"
    >
        <div
            class="absolute -right-8 -top-8 h-24 w-24 rounded-full blur-2xl transition-all duration-300"
            :class="glowColorClass"
        ></div>

        <div class="relative flex items-start justify-between">
            <div class="flex-1">
                <p
                    class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                >
                    {{ title }}
                </p>

                <div class="mt-3 flex items-baseline gap-2.5">
                    <h3 class="text-4xl font-bold text-gray-900">
                        {{ value }}
                    </h3>
                    <span
                        v-if="growth !== null"
                        class="flex items-center gap-0.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100"
                    >
                        <slot name="growth-icon"></slot>
                        {{ growth }}%
                    </span>
                    <span
                        v-if="stars !== null"
                        class="text-xl font-semibold text-gray-400"
                    >
                        / {{ maxStars }}
                    </span>
                </div>

                <!-- Badge slot -->
                <p v-if="badge" class="mt-2.5 text-xs">
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-1 font-semibold ring-1"
                        :class="badgeColorClasses"
                    >
                        {{ badge }}
                    </span>
                </p>

                <!-- Stars display -->
                <div v-if="stars !== null" class="relative mt-3 flex items-center gap-0.5">
                    <slot name="stars"></slot>
                </div>

                <!-- Subtitle -->
                <p v-if="subtitle" class="relative mt-2.5 text-xs text-gray-500">
                    {{ subtitle }}
                </p>
            </div>

            <!-- Icon -->
            <div
                v-if="icon"
                class="rounded-xl bg-gradient-to-br p-3 shadow-lg transition-all duration-300 group-hover:shadow-xl"
                :class="iconColorClasses"
            >
                <component :is="icon" :size="24" class="text-white" />
            </div>
        </div>

        <!-- Sparkline -->
        <div v-if="sparklineData" class="relative mt-5 h-12">
            <VueApexCharts
                type="area"
                height="48"
                :options="sparklineOptions"
                :series="[{ data: sparklineData }]"
            />
        </div>

        <!-- Progress bar -->
        <div v-if="progressBar !== null" class="relative mt-5">
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                <div
                    class="h-full rounded-full bg-gradient-to-r shadow-md transition-all duration-700 ease-out"
                    :class="progressColorClass"
                    :style="{ width: progressBar + '%' }"
                ></div>
            </div>
        </div>
    </div>
</template>
