<script setup>
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
    data: {
        type: Array,
        required: true,
    },
    labels: {
        type: Array,
        required: true,
    },
});

const donutOptions = computed(() => ({
    chart: {
        type: "donut",
        height: 120,
        animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
        },
    },
    labels: props.labels,
    colors: ["#3b82f6", "#8b5cf6", "#ec4899", "#f59e0b", "#10b981"],
    legend: {
        show: false,
    },
    dataLabels: {
        enabled: false,
    },
    plotOptions: {
        pie: {
            donut: {
                size: "65%",
                labels: {
                    show: false,
                },
            },
        },
    },
    stroke: {
        width: 2,
        colors: ["#ffffff"],
    },
    tooltip: {
        enabled: true,
        y: {
            formatter: (val) => `${val}%`,
        },
    },
    states: {
        hover: {
            filter: {
                type: "lighten",
                value: 0.1,
            },
        },
        active: {
            filter: {
                type: "none",
            },
        },
    },
}));
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg"
    >
        <div
            class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-indigo-50/50 blur-2xl transition-all duration-300 group-hover:bg-indigo-100/70"
        ></div>
        <p
            class="relative mb-4 text-xs font-semibold uppercase tracking-wider text-gray-500"
        >
            Department Distribution
        </p>
        <div class="relative flex items-center gap-5">
            <!-- Donut chart on the left -->
            <div class="flex-shrink-0">
                <VueApexCharts
                    type="donut"
                    width="120"
                    height="120"
                    :options="donutOptions"
                    :series="data"
                />
            </div>
            <!-- Legend on the right -->
            <div class="min-w-0 flex-1 space-y-2">
                <div
                    v-for="(label, index) in labels"
                    :key="label"
                    class="flex items-center justify-between gap-2"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <div
                            class="h-2.5 w-2.5 flex-shrink-0 rounded-full shadow-sm ring-2 ring-white"
                            :style="{
                                backgroundColor: donutOptions.colors[index],
                            }"
                        ></div>
                        <span
                            class="truncate text-xs font-medium text-gray-700"
                        >
                            {{ label }}
                        </span>
                    </div>
                    <span
                        class="ml-2 text-xs font-bold tabular-nums text-gray-900"
                    >
                        {{ data[index] }}%
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
