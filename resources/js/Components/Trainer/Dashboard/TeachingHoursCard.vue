<script setup>
import { ref } from 'vue';
import { Clock } from 'lucide-vue-next';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    days: {
        type: Array,
        required: true,
        default: () => [],
    },
    hours: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const chartOptions = ref({
    chart: {
        type: 'line',
        height: 280,
        toolbar: {
            show: false,
        },
        fontFamily: 'Prompt, sans-serif',
        zoom: {
            enabled: false,
        },
    },
    stroke: {
        curve: 'smooth',
        width: 3.5,
    },
    colors: ['#3D9792'],
    xaxis: {
        categories: props.days,
        labels: {
            style: {
                colors: '#64748b',
                fontSize: '11px',
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
                colors: '#64748b',
                fontSize: '11px',
                fontWeight: 500,
            },
            formatter: function (val) {
                return val + 'h';
            },
        },
    },
    grid: {
        borderColor: '#f1f5f9',
        strokeDashArray: 3,
        padding: {
            top: 0,
            right: 10,
            bottom: 0,
            left: 5,
        },
    },
    tooltip: {
        y: {
            formatter: function (val) {
                return val + ' hours';
            },
        },
    },
    markers: {
        size: 0,
        hover: {
            size: 5,
        },
    },
});

const series = ref([
    {
        name: 'Teaching Hours',
        data: props.hours,
    },
]);
</script>

<template>
    <div class="rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-lg">
        <!-- Header -->
        <div class="mb-6 flex items-start justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <div class="rounded-lg bg-teal-100 p-2">
                        <Clock :size="20" class="text-teal-600" />
                    </div>
                    <h3 class="text-base font-bold text-gray-900">
                        {{ $t('Teaching Hours') }}
                    </h3>
                </div>
                <p class="mt-2 text-sm text-gray-500">
                    {{ $t('Last 7 days') }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-gray-900">
                    {{ hours.reduce((a, b) => a + b, 0) }}h
                </p>
                <p class="text-xs text-gray-500">{{ $t('Total hours') }}</p>
            </div>
        </div>

        <!-- Chart -->
        <div class="mt-4">
            <VueApexCharts
                type="line"
                height="280"
                :options="chartOptions"
                :series="series"
            />
        </div>
    </div>
</template>
