<template>
    <div class="mb-6">
      <h4 class="text-3xl font-bold text-gray-900 dark:text-white !mb-1">Settings</h4>
    </div>
    <div
      v-if="!canView"
      class="text-center py-16 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
    >
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
        You do not have permission to manage AR invoice number settings.
      </p>
    </div>

    <template v-else>
        <div v-if="loading" class="flex justify-center py-16">
            <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">Loading settings…</p>
        </div>
        <div v-else class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Input
                    v-model="form.invoice_company_name"
                    label="Invoice company name"
                    placeholder="Company name"
                />
                <Input
                    v-model="form.invoice_company_address"
                    label="Invoice company address"
                    placeholder="Company address"
                />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <Input
                    v-model="form.invoice_prefix"
                    label="Invoice prefix"
                    placeholder="INV"
                />
                <div>
                    <span class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Include year in number</span>
                    <div class="flex items-center gap-6 pt-0.5">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-gray-900 dark:text-white">
                            <input
                                v-model.number="form.invoice_year"
                                type="radio"
                                name="invoice_year"
                                class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                                :value="1"
                            />
                            <span>Yes</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-gray-900 dark:text-white">
                            <input
                                v-model.number="form.invoice_year"
                                type="radio"
                                name="invoice_year"
                                class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                                :value="0"
                            />
                            <span>No</span>
                        </label>
                    </div>
                </div>
                <Input
                    v-model="form.invoice_starting_number"
                    label="Invoice starting number"
                    placeholder="1"
                    type="number"
                    :max="1000000"
                    :min="1"
                />
                <Input
                    v-model="form.invoice_length"
                    label="Invoice length"
                    placeholder="5"
                    type="number"
                    :max="10"
                    :min="1"
                />
            </div>

            <div class="text-sm text-gray-600 dark:text-gray-400 !mb-0 mt-3">
                Example: <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ renderInvoiceNumber }}</span>
            </div>
            <Button
                type="button"
                class="mt-4"
                :disabled="saving"
                @click="saveSettings"
            >
                {{ saving ? 'Saving…' : 'Save' }}
            </Button>
        </div>
    </template>
</template>
<script setup>
import { computed, reactive, ref, onMounted } from 'vue'
import { useUser } from '../../../composables/useUser'
import { useMessage } from '@/composables/useMessage'
import axios from '../../../plugins/axios'
import Input from '@/components/ui/input.vue'
import Button from '@/components/ui/button.vue'

const { can } = useUser()
const message = useMessage()

const canView = computed(() => can('upload-portal-ar-settings', 'index'))

const loading = ref(true)
const saving = ref(false)

const form = reactive({
    invoice_prefix: 'INV',
    invoice_year: 1,
    invoice_starting_number: 1,
    invoice_length: 5,
    invoice_company_name: '',
    invoice_company_address: '',
})

const renderInvoiceNumber = computed(() => {
    const prefix = String(form.invoice_prefix ?? '').trim() || 'INV'
    const yearOn = Number(form.invoice_year) === 1
    const y = yearOn ? new Date().getFullYear() : null
    const starting = Number(form.invoice_starting_number)
    const n = Number.isFinite(starting) ? starting : 1
    let len = Number(form.invoice_length)
    if (!Number.isFinite(len) || len < 1) {
        len = 5
    }
    if (len > 10) {
        len = 10
    }
    const padded = String(n).padStart(len, '0')
    let core = prefix
    if (y !== null) {
        core += '-' + y
    }
    core += '-' + padded
    return core
})

const loadSettings = async () => {
    if (!canView.value) {
        loading.value = false
        return
    }
    loading.value = true
    try {
        const { data } = await axios.get('/upload-portal/api/ar-invoice-settings')
        if (data.success && data.settings) {
            const s = data.settings
            form.invoice_prefix = s.invoice_prefix ?? 'INV'
            form.invoice_year = Number(s.invoice_year) === 0 ? 0 : 1
            form.invoice_starting_number = s.invoice_starting_number ?? 1
            form.invoice_length = s.invoice_length ?? 5
            form.invoice_company_name = s.invoice_company_name ?? ''
            form.invoice_company_address = s.invoice_company_address ?? ''
        }
    } catch (e) {
        message.error('Could not load settings.')
    } finally {
        loading.value = false
    }
}

const saveSettings = async () => {
    if (!canView.value || saving.value) {
        return
    }
    saving.value = true
    try {
        const { data } = await axios.put('/upload-portal/api/ar-invoice-settings', {
            invoice_prefix: form.invoice_prefix,
            invoice_year: Number(form.invoice_year) === 0 ? 0 : 1,
            invoice_starting_number: Number(form.invoice_starting_number),
            invoice_length: Number(form.invoice_length),
            invoice_company_name: form.invoice_company_name,
            invoice_company_address: form.invoice_company_address,
        })
        if (data.success) {
            message.success(data.message || 'Settings saved.')
        } else {
            message.error(data.message || 'Save failed.')
        }
    } catch (e) {
        const msg = e.response?.data?.message || 'Save failed.'
        message.error(msg)
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    loadSettings()
})
</script>
