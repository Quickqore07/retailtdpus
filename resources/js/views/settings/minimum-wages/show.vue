<template>
    <div v-if="show" class="minimum-wage-show">
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="text-2xl font-bold !mb-0">Minimum Wage Details</h5>
                    <div class="flex items-center gap-2">
                        <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs"
                        to="/settings/minimum-wages">
                        </Button>
                        <Button icon-left="edit" icon-size="sm" variant="primary" size="xs" v-if="access.includes('update')"
                        :to="`/settings/minimum-wages/${model.id}/edit`">
                        </Button>
                        <Button icon-left="trash" icon-size="sm" variant="danger" size="xs" @click="handleDelete" v-if="access.includes('delete')">
                        </Button>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <Label label="State" :value="model.state?.name" />
                <Label label="Created At" :value="formatDateTime(model.created_at)" />
                <Label label="Updated At" :value="formatDateTime(model.updated_at)" />
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="text-left px-4 py-3">Effective Date</th>
                            <th class="text-left px-4 py-3">Minimum Wage</th>
                            <th class="text-left px-4 py-3">Tipped Minimum Wage</th>
                            <th class="text-left px-4 py-3">Annual Estimate (40hrs/week)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in sortedItems"
                            :key="item.id"
                            class="border-t border-gray-100 dark:border-gray-700"
                        >
                            <td class="px-4 py-3">{{ formatDate(item.effective_date) }}</td>
                            <td class="px-4 py-3">${{ formatCurrency(item.minimum_wage) }}</td>
                            <td class="px-4 py-3">${{ formatCurrency(item.tipped_minimum_wage) }}</td>
                            <td class="px-4 py-3">${{ formatCurrency(item.minimum_wage * 40 * 52) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Panel>
    </div>

    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading minimum wage details..." centered />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDate, formatDateTime as formatDateTime } from '@/utils/date'

const route = useRoute()
const resource = route.meta?.resource || 'settings/minimum-wages'

const { model, show, setData, removeDB, access } = useShowable(resource, 'minimum-wage')

const sortedItems = computed(() => {
    if (!model.value?.items?.length) return []
    return [...model.value.items].sort((a, b) => new Date(b.effective_date) - new Date(a.effective_date))
})

const formatCurrency = (value) => {
    if (!value) return '0.00'
    return parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

const handleDelete = async () => {
    const id = model.value?.id
    if (id) {
        await removeDB(resource, id)
    }
}

defineExpose({
    setData
})
</script>

<style scoped>
@media (max-width: 640px) {
    .minimum-wage-show {
        padding: 1rem;
    }
}
</style>
