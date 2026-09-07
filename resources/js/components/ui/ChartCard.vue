<template>
    <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 mb-4">
            <h4 class="text-sm sm:text-base font-semibold text-gray-900 dark:text-white shrink-0">{{ title }}</h4>
            <div class="w-full sm:w-auto sm:min-w-0">
                <slot name="toolbar" />
            </div>
        </div>
        <div v-if="loading" class="flex items-center justify-center h-80 text-gray-500 dark:text-gray-400">
            Loading chart...
        </div>
        <VueApexCharts
            v-else
            :type="chartType"
            :height="height"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
    /** Chart title */
    title: {
        type: String,
        default: ''
    },
    /** X-axis categories */
    labels: {
        type: Array,
        default: () => []
    },
    /** ApexCharts series: [{ name, data }] */
    series: {
        type: Array,
        default: () => []
    },
    /** Loading state */
    loading: {
        type: Boolean,
        default: false
    },
    /** Chart height in px */
    height: {
        type: [Number, String],
        default: 320
    },
    /** Chart type: line, area, bar */
    chartType: {
        type: String,
        default: 'line',
        validator: (v) => ['line', 'area', 'bar'].includes(v)
    },
    /** Y-axis / tooltip format: 'currency' | 'number' | custom formatter fn */
    valueFormat: {
        type: [String, Function],
        default: 'number'
    },
    /** Line chart stroke width */
    strokeWidth: {
        type: Number,
        default: 2
    },
    /** Show data labels on chart */
    showDataLabels: {
        type: Boolean,
        default: false
    },
    /** Chart color (hex) */
    color: {
        type: String,
        default: '#3b82f6'
    }
})

const formatValue = (val, opts = {}) => {
    if (val == null) return opts.empty ?? ''
    if (typeof props.valueFormat === 'function') {
        return props.valueFormat(val)
    }
    if (props.valueFormat === 'currency') {
        return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    }
    return Number(val).toLocaleString('en-US', { maximumFractionDigits: opts.decimals ?? 0 })
}

const chartOptions = computed(() => {
    const base = {
        chart: {
            type: props.chartType,
            toolbar: { show: false },
            background: 'transparent',
            fontFamily: 'inherit',
            zoom: { enabled: false },
        },
        stroke: {
            curve: 'smooth',
            width: props.strokeWidth,
        },
        dataLabels: {
            enabled: props.showDataLabels,
            formatter: (val) => formatValue(val, { empty: '' }),
            style: { fontSize: '11px' },
        },
        xaxis: {
            categories: props.labels,
            labels: { style: { colors: '#6b7280' } },
        },
        yaxis: {
            labels: {
                formatter: (val) => formatValue(val),
                style: { colors: '#6b7280' },
            },
        },
        tooltip: {
            y: {
                formatter: (val) => formatValue(val),
            },
        },
        grid: {
            borderColor: '#e5e7eb',
            strokeDashArray: 4,
            xaxis: { lines: { show: false } },
        },
        colors: [props.color],
        fill: props.chartType === 'area' ? {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.3,
                opacityFrom: 0.5,
                opacityTo: 0.1,
            },
        } : {},
    }
    if (props.chartType === 'bar') {
        base.plotOptions = {
            bar: {
                borderRadius: 4,
                columnWidth: '60%',
                dataLabels: { position: 'top' },
            },
        }
        base.dataLabels = {
            ...base.dataLabels,
            offsetY: -20,
        }
    }
    return base
})
</script>
