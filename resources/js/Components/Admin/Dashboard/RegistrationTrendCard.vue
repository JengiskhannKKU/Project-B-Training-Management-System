<script setup>
import { ref, computed } from "vue";
import VueApexCharts from "vue3-apexcharts";
import { ChevronDown } from "lucide-vue-next";

const props = defineProps({
    months: {
        type: Array,
        required: true,
    },
    series: {
        type: Array,
        required: true,
    },
    periodOptions: {
        type: Array,
        default: () => ["Last 3 Months", "Last 6 Months", "Last Year", "All Time"],
    },
    defaultPeriod: {
        type: String,
        default: "Last 6 Months",
    },
});

const selectedPeriod = ref(props.defaultPeriod);
const showPeriodDropdown = ref(false);

const chartOptions = computed(() => ({
    chart: {
        type: "line",
        height: 350,
        toolbar: {
            show: false,
        },
        fontFamily: "Prompt, sans-serif",
        zoom: {
            enabled: false,
        },
    },
    stroke: {
        curve: "smooth",
        width: 3.5,
    },
    colors: ["#cbd5e1", "#60a5fa", "#3b82f6"],
    xaxis: {
        categories: props.months,
        labels: {
            style: {
                colors: "#64748b",
                fontSize: "11px",
                fontWeight: 500,
            },
        },
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
    },
    yaxis: {
        labels: {
            style: {
                colors: "#64748b",
                fontSize: "11px",
                fontWeight: 500,
            },
            formatter: (val) => Math.round(val),
        },
    },
    grid: {
        borderColor: "#f1f5f9",
        strokeDashArray: 3,
        padding: {
            top: 0,
            right: 10,
            bottom: 0,
            left: 5,
        },
    },
    legend: {
        position: "top",
        horizontalAlign: "right",
        fontSize: "12px",
        fontWeight: 600,
        markers: {
            width: 8,
            height: 8,
            radius: 10,
        },
        itemMargin: {
            horizontal: 10,
        },
    },
    tooltip: {
        shared: true,
        intersect: false,
        y: {
            formatter: (val) => `${val} registrations`,
        },
    },
    markers: {
        size: 0,
        hover: {
            size: 5,
        },
    },
}));
</script>

<template>
    <div
        class="rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-lg lg:col-span-2"
    >
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900">
                    Registration Trend
                </h2>
                <p class="mt-1 text-xs text-gray-500">
                    Track trainee registrations over time
                </p>
            </div>
            <div class="relative">
                <button
                    @click="showPeriodDropdown = !showPeriodDropdown"
                    class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 hover:shadow"
                >
                    {{ selectedPeriod }}
                    <ChevronDown
                        :size="14"
                        class="transition-transform duration-200"
                        :class="{ 'rotate-180': showPeriodDropdown }"
                    />
                </button>
                <div
                    v-if="showPeriodDropdown"
                    class="absolute right-0 z-10 mt-2 w-44 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl"
                >
                    <button
                        v-for="option in periodOptions"
                        :key="option"
                        @click="
                            selectedPeriod = option;
                            showPeriodDropdown = false;
                        "
                        class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50"
                        :class="{
                            'bg-blue-50 text-blue-700':
                                selectedPeriod === option,
                        }"
                    >
                        {{ option }}
                    </button>
                </div>
            </div>
        </div>
        <div class="-mx-2">
            <VueApexCharts
                type="line"
                height="350"
                :options="chartOptions"
                :series="series"
            />
        </div>
    </div>
</template>
