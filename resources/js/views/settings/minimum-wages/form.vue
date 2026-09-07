<template>
    <div v-if="show" class="minimum-wage-form">
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="font-bold !mb-0">
                        {{ mode === 'edit' ? 'Edit Minimum Wage' : 'Create New Minimum Wage' }}
                    </h5>
                </div>
            </template>

            <form @submit.prevent="handleSave" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <DynamicDropdown
                        label="State"
                        v-model="form.state"
                        resource="states"
                        display-name="name"
                        placeholder="Select a state"
                        :required="true"
                        :error="errors.state_id ? errors.state_id[0] : null"
                        icon-left="map"
                    />
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded-md">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h6 class="font-semibold !mb-0">Wage Rates by Effective Date</h6>
                        <Button type="button" size="sm" variant="outline-primary" icon-left="plus" @click="addItem">
                            Add Row
                        </Button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <Th class="text-left px-3 py-3 w-14">No</Th>
                                    <Th class="text-left px-3 py-3 min-w-[180px]">Effective Date</Th>
                                    <Th class="text-left px-3 py-3 min-w-[160px]">Minimum Wage ($)</Th>
                                    <Th class="text-left px-3 py-3 min-w-[180px]">Tipped Minimum Wage ($)</Th>
                                    <Th class="text-left px-3 py-3 w-16">Actions</Th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in form.items"
                                    :key="`wage-item-${index}`"
                                    class="border-t border-gray-100 dark:border-gray-700"
                                >
                                    <Td class="px-3 py-3">{{ index + 1 }}</Td>
                                    <Td class="px-3 py-3">
                                        <Input
                                            v-model="item.effective_date"
                                            type="date"
                                            :required="true"
                                            :error="getItemError(index, 'effective_date')"
                                        />
                                    </Td>
                                    <Td class="px-3 py-3">
                                        <Input
                                            v-model="item.minimum_wage"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            :required="true"
                                            :error="getItemError(index, 'minimum_wage')"
                                        />
                                    </Td>
                                    <Td class="px-3 py-3">
                                        <Input
                                            v-model="item.tipped_minimum_wage"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            :required="true"
                                            :error="getItemError(index, 'tipped_minimum_wage')"
                                        />
                                    </Td>
                                    <Td class="px-3 py-3">
                                        <Button
                                            type="button"
                                            variant="outline-danger"
                                            size="sm"
                                            icon-left="trash"
                                            :disabled="form.items.length === 1"
                                            @click="removeItem(index)"
                                        />
                                    </Td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <Button variant="outline-secondary" size="md" @click="cancel" type="button">
                        Cancel
                    </Button>
                    <Button variant="primary" size="md" type="submit" :loading="isSaving"
                        v-if="mode === 'create' ? access.includes('create') : access.includes('update')">
                        {{ mode === 'edit' ? 'Update Minimum Wage' : 'Create Minimum Wage' }}
                    </Button>
                </div>
            </form>
        </Panel>
    </div>

    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading form..." centered />
    </div>
</template>

<script setup>
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Spinner from '@/components/ui/spinner.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'

const route = useRoute()
const resource = route.meta?.resource || 'settings/minimum-wages'

const { form, errors, isSaving, show, mode, save, cancel, setData, access } = useFormable(resource, 'settings/minimum-wages', 'minimum-wage')

const createEmptyItem = () => ({
    effective_date: null,
    minimum_wage: null,
    tipped_minimum_wage: 0,
})

const addItem = () => {
    form.value.items.push(createEmptyItem())
}

const removeItem = (index) => {
    if (form.value.items.length > 1) {
        form.value.items.splice(index, 1)
    }
}

const getItemError = (index, field) => {
    const key = `items.${index}.${field}`
    return errors.value[key] ? errors.value[key][0] : null
}

const handleSave = () => {
    const obj = {
        ...form.value,
        state_id: form.value.state?.id,
        county_id: form.value.county?.id ?? null,
        items: (form.value.items || []).map((item) => ({
            effective_date: item.effective_date,
            minimum_wage: item.minimum_wage,
            tipped_minimum_wage: item.tipped_minimum_wage ?? 0,
        })),
    }
    save(obj)
}

watch(() => form.value, (newVal) => {
    if (!newVal.items || newVal.items.length === 0) {
        form.value.items = [createEmptyItem()]
    }
    form.value.items = form.value.items.map((item) => ({
        ...item,
        tipped_minimum_wage: item.tipped_minimum_wage ?? 0,
    }))
})

defineExpose({
    setData
})
</script>

<style scoped>
@media (max-width: 640px) {
    .minimum-wage-form {
        padding: 1rem;
    }
}
</style>
