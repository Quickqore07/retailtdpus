<template>
    <Panel title="Amount by status" padding="md">
        <div class="space-y-4">
            <div
                v-for="(row, index) in rows"
                :key="index"
                class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto] gap-2 sm:gap-4 items-center"
            >
                <div class="text-sm text-gray-700 dark:text-gray-300 min-w-0">
                    {{ row.label }}<span v-if="row.count != null" class="text-gray-500"> ({{ row.count }})</span>
                </div>
                <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="row.color"
                        :style="{ width: `${barWidth(row.amount)}%` }"
                    />
                </div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white sm:text-right tabular-nums">
                    {{ formatCurrency(row.amount) }}
                </div>
            </div>
        </div>
    </Panel>
</template>

<script setup>
import { computed } from 'vue'
import Panel from '@/components/ui/panel.vue'
import { formatCurrency } from '@/utils/number'

const props = defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
})

const maxAmount = computed(() => {
    if (!props.rows.length) return 0
    return Math.max(...props.rows.map((row) => Number(row.amount) || 0))
})

const barWidth = (amount) => {
    if (!maxAmount.value) return 0
    return Math.max(4, (Number(amount) / maxAmount.value) * 100)
}
</script>
