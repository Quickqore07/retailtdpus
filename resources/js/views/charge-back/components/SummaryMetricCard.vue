<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 sm:p-5"
        :class="accentBorderClass"
    >
        <p class="text-[10px] sm:text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase mb-2">
            {{ label }}
        </p>
        <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white leading-tight">
            <template v-if="casesOnly">
                {{ cases }} {{ cases === 1 ? 'case' : 'cases' }}
            </template>
            <template v-else>
                {{ formatCurrency(amount) }} / {{ cases }} {{ cases === 1 ? 'case' : 'cases' }}
            </template>
        </p>
        <p v-if="subLabel" class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-0">
            {{ subLabel }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { formatCurrency } from '@/utils/number'

const props = defineProps({
    label: { type: String, required: true },
    amount: { type: Number, default: null },
    cases: { type: Number, default: 0 },
    subLabel: { type: String, default: null },
    casesOnly: { type: Boolean, default: false },
    accent: {
        type: String,
        default: 'emerald',
        validator: (v) => ['emerald', 'orange', 'purple', 'green', 'red', 'blue'].includes(v),
    },
})

const accentBorderClass = computed(() => {
    const map = {
        emerald: 'border-t-4 border-t-emerald-500',
        orange: 'border-t-4 border-t-orange-500',
        purple: 'border-t-4 border-t-purple-500',
        green: 'border-t-4 border-t-green-600',
        red: 'border-t-4 border-t-red-500',
        blue: 'border-t-4 border-t-blue-500',
    }
    return map[props.accent] || map.emerald
})
</script>
