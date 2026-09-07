<template>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3" :class="iconColorClass">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ label }}</span>
            <SvgIcon v-if="icon" :name="icon" size="lg" :class="iconColorClass" />
        </div>
        
        <!-- Multiple Stats -->
        <div class="flex justify-between flex-wrap gap-2">
            <div v-for="(item, index) in items" :key="index" class="flex items-center flex-col justify-between" @dblclick="handleDblClick(item.value)">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ item.label }}</span>
                <span class="text-base font-bold text-gray-900 dark:text-gray-100">
                    <span v-if="item.prefix || prefix">{{ item.prefix || prefix }}</span>
                    <span v-if="type === 'amount' || item.type === 'amount'">{{ formatCurrency(item.value) }}</span>
                     <span v-else>{{ formatValue(item.value) }}</span>
                     <span v-if="item.suffix || suffix">{{ item.suffix || suffix }}</span>
                </span>
            </div>
        </div>
        <Spinner v-if="loading" size="md" text="Loading..." centered />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { formatCurrency } from '@/utils/number'
import Spinner from '@/components/ui/spinner.vue'

const props = defineProps({
    label: {
        type: String,
        required: true
    },
    items: {
        type: Array,
        required: true,
        // Each item should have: { label, value, prefix?, suffix? }
    },
    icon: {
        type: String,
        default: null
    },
    iconColor: {
        type: String,
        default: 'blue',
        validator: (value) => ['blue', 'green', 'purple', 'orange', 'red', 'yellow', 'indigo', 'pink'].includes(value)
    },
    description: {
        type: String,
        default: null
    },
    prefix: {
        type: String,
        default: null
    },
    suffix: {
        type: String,
        default: null
    },
    formatType: {
        type: String,
        default: 'none',
        validator: (value) => ['none', 'number', 'currency', 'percentage'].includes(value)
    },
    decimals: {
        type: Number,
        default: 2
    },
    type: {
        type: String,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['dblclick'])

const handleDblClick = (value) => {
    emit('dblclick', value)
}

const iconColorClass = computed(() => {
    const colorMap = {
        blue: 'text-blue-500 dark:text-blue-400',
        green: 'text-green-500 dark:text-green-400',
        purple: 'text-purple-500 dark:text-purple-400',
        orange: 'text-orange-500 dark:text-orange-400',
        red: 'text-red-500 dark:text-red-400',
        yellow: 'text-yellow-500 dark:text-yellow-400',
        indigo: 'text-indigo-500 dark:text-indigo-400',
        pink: 'text-pink-500 dark:text-pink-400'
    }
    return colorMap[props.iconColor] || 'text-blue-500 dark:text-blue-400'
})

const formatValue = (value) => {
    if (value === null || value === undefined) {
        return '0'
    }
    
    switch (props.formatType) {
        case 'number':
            return parseFloat(value).toFixed(props.decimals)
        case 'currency':
            return parseFloat(value).toFixed(props.decimals)
        case 'percentage':
            return `${parseFloat(value).toFixed(props.decimals)}%`
        default:
            return value
    }
}
</script>

<style scoped>
/* Additional custom styles if needed */
</style>
