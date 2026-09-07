<template>
    <div v-if="show" class="fund-requirement-show">
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="text-2xl font-bold !mb-0">Fund Requirement Details</h5>
                    <div class="flex items-center gap-2">
                        <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs"
                        to="/settings/fund-requirements">
                        </Button>
                        <Button icon-left="edit" icon-size="sm" variant="primary" size="xs" v-if="access.includes('update')"
                        :to="`/settings/fund-requirements/${model.id}/edit`">
                        </Button>
                        <Button icon-left="trash" icon-size="sm" variant="danger" size="xs" @click="handleDelete" v-if="access.includes('delete')">
                        </Button>
                    </div>
                </div>
            </template>

            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
                        Basic Information
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6">
                        <Label label="Label" :value="model.label" />
                        <Label label="Type" :value="renderType(model.type)" />
                        <Label label="Condition Type" :value="renderConditionType(model.condition_type)" />
                        <Label :label="renderConditionValueLabel(model.condition_type)">
                            <span v-if="model.condition_type === 'monthly'">
                                {{ renderDayOfMonth(model.condition_value) }}
                            </span>
                            <span v-else>
                                {{ formatDate(model.condition_value) }}
                            </span>
                        </Label>
                        <Label v-if="model.condition_type === 'weekly' || model.condition_type === 'bi-weekly'" label="Week Day">
                            {{ renderWeekDay(model.condition_value) }}
                        </Label>
                        
                        <Label label="Amount" :value="formatCurrency(model.amount)" />

                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Status
                            </label>
                            <p class="text-base">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="model.active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'">
                                    {{ model.active ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Company Wise Details -->
                <div v-if="model.type === 'company wise' && model.companies && model.companies.length > 0" class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Company Amounts
                    </h6>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        No
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Company
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="(company, index) in model.companies" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ company.company?.name || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">
                                        ${{ formatCurrency(company.amount) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white text-right">
                                        Total:
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600 dark:text-blue-400 text-right">
                                        ${{ formatCurrency(calculateTotal()) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h6 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                        Metadata
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                        <Label label="Created At" :value="formatDateTime(model.created_at)" />
                        <Label label="Updated At" :value="formatDateTime(model.updated_at)" />
                        <Label label="Created By" :value="model.created_by?.name || 'N/A'" />
                        <Label label="Updated By" :value="model.updated_by?.name || 'N/A'" />
                    </div>
                </div>
            </div>
        </Panel>
    </div>

    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading..." centered />
    </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Spinner from '@/components/ui/spinner.vue'
import Label from '@/components/ui/label.vue'
import { formatCurrency } from '@/utils/number'

const route = useRoute()
const resource = route.meta?.resource || 'settings/fund-requirements'

const { model, show, access, removeDB } = useShowable(resource, 'fund-requirement')

const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const formatDateTime = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const calculateTotal = () => {
    if (!model.value.companies || model.value.companies.length === 0) {
        return 0
    }
    return model.value.companies.reduce((sum, company) => {
        const amount = parseFloat(company.amount) || 0
        return sum + amount
    }, 0)
}

const handleDelete = async () => {
    const id = model.value?.id
    if (id) {
        await removeDB(resource, id)
    }
}

const renderDayOfMonth = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('en-US', {
        day: 'numeric'
    })
}

const renderConditionType = (conditionType) => {
    if (!conditionType) return 'N/A'
    return conditionType === 'monthly' ? 'Monthly' : conditionType === 'weekly' ? 'Weekly' : conditionType === 'bi-weekly' ? 'Bi-Weekly' : 'N/A'
}

const renderConditionValueLabel = (conditionType) => {
    if (!conditionType) return 'N/A'
    return conditionType === 'monthly' ? 'Day of Month' : conditionType === 'weekly' ? 'Start Date' : conditionType === 'bi-weekly' ? 'Start Date' : 'N/A'
}

const renderType = (type) => {
    if (!type) return 'N/A'
    return type === 'company wise' ? 'Company Wise' : type === 'fixed' ? 'Fixed' : 'N/A'
}

const renderWeekDay = (weekDay) => {
    if (!weekDay) return 'N/A'
    return new Date(weekDay).toLocaleDateString('en-US', {
        weekday: 'long'
    })
}

</script>

<style scoped>
.fund-requirement-show {
    padding: 1rem;
}
</style>
