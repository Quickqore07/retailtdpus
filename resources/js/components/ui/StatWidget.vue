<template>
    <div
        class="flex items-center gap-3 sm:gap-4 p-4 sm:p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700"
        :class="{ 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/80': clickable }"
        @click="handleClick"
    >
        <div
            class="flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex-shrink-0"
            :class="iconBgClass"
        >
            <SvgIcon :name="icon" size="lg" :class="iconColorClass" />
        </div>
        <div class="flex flex-col min-w-0">
            <span class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ displayValue }}</span>
            <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 break-words">{{ label }}</span>
            <span
                v-if="subLabel"
                class="text-xs text-amber-600 dark:text-amber-400 mt-0.5"
            >{{ subLabel }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import SvgIcon from '@/components/SvgIcon.vue'

const props = defineProps({
    /** Main value to display (number or string). Shows '—' when null/undefined */
    value: {
        type: [Number, String],
        default: null
    },
    /** Label below the value */
    label: {
        type: String,
        required: true
    },
    /** Icon name for SvgIcon */
    icon: {
        type: String,
        default: 'chart'
    },
    /** Variant: primary, success, warning, danger */
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'success', 'warning', 'danger'].includes(v)
    },
    /** Optional sub-label (e.g. "HR Pending: 2 · DO Pending: 1") */
    subLabel: {
        type: String,
        default: null
    },
    /** Route path to navigate on click */
    to: {
        type: String,
        default: null
    }
})

const emit = defineEmits(['click'])

const router = useRouter()

const clickable = computed(() => !!props.to)

const displayValue = computed(() => {
    if (props.value === null || props.value === undefined) return '—'
    return props.value
})

const iconBgClass = computed(() => {
    const map = {
        primary: 'bg-blue-100 text-blue-500 dark:bg-blue-900/30 dark:text-blue-400',
        success: 'bg-green-100 text-green-500 dark:bg-green-900/30 dark:text-green-400',
        warning: 'bg-yellow-100 text-yellow-500 dark:bg-yellow-900/30 dark:text-yellow-400',
        danger: 'bg-red-100 text-red-500 dark:bg-red-900/30 dark:text-red-400',
    }
    return map[props.variant] || map.primary
})

const iconColorClass = computed(() => {
    const map = {
        primary: 'text-blue-500 dark:text-blue-400',
        success: 'text-green-500 dark:text-green-400',
        warning: 'text-yellow-500 dark:text-yellow-400',
        danger: 'text-red-500 dark:text-red-400',
    }
    return map[props.variant] || map.primary
})

const handleClick = () => {
    if (props.to) {
        router.push(props.to)
    }
    emit('click')
}
</script>
