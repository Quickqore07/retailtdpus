<template>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 shadow-sm">
        <div class="flex items-center justify-between mb-1" :class="iconColorClass">
            <span class="!xl:text-sm !text-xs font-medium text-gray-600 dark:text-gray-400">{{ label }}</span>
            <SvgIcon v-if="icon" :name="icon" size="lg" :class="iconColorClass" />
        </div>
        <div class="!xl:text-2xl !text-xl font-bold text-gray-900 dark:text-gray-100">
            <span v-if="prefix">{{ prefix }}</span>{{ formattedValue }}<span v-if="suffix">{{ suffix }}</span>
        </div>
        <p v-if="description" class="text-xs text-gray-500 dark:text-gray-400 mt-1 !mb-0">{{ description }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import SvgIcon from '@/components/SvgIcon.vue'

const props = defineProps({
    label: {
        type: String,
        required: true
    },
    value: {
        type: [Number, String],
        required: true
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
    }
})

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

const formattedValue = computed(() => {
    const value = props.value
    
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
})
</script>

<style scoped>
/* Additional custom styles if needed */
</style>
