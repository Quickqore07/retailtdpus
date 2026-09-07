<template>
    <div class="missing-bank-amount-report p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-credit-card me-2"></i>
                    Missing Bank Amount Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Unsettled bank amounts with settlement capabilities
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <DynamicDropdown
                    v-model="filters.ledger"
                    :custom-options="ledgers"
                    display-name="name"
                    label="Ledger"
                    placeholder="Select Ledger"
                    @change="() => applyFilters(1, itemsPerPage)"
                /> 
                <div>
                    <DynamicDropdown
                        v-model="filters.company"
                        resource="companies"
                        display-name="name"
                        label="Company"
                        placeholder="Select Company"
                        @change="() => applyFilters(1, itemsPerPage)"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Start Date
                    </label>
                    <Input
                        type="date"
                        v-model="filters.start_date"
                        @change="() => applyFilters(1, itemsPerPage)"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        End Date
                    </label>
                    <Input
                        type="date"
                        v-model="filters.end_date"
                        @change="() => applyFilters(1, itemsPerPage)"
                    />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <Button
                    icon-left="refresh"
                    icon-size="sm"
                    variant="primary"
                    size="sm"
                    @click="() => applyFilters(1, itemsPerPage)"
                    :loading="loading"
                >
                    Load Report
                </Button>
                <Button
                  v-if="can('missing-bank-amount-report', 'update')"
                    icon-left="save"
                    icon-size="sm"
                    variant="success"
                    size="sm"
                    @click="updateAllFees"
                    :loading="updating"
                    :disabled="reportData.length === 0"
                >
                    Update All Fees
                </Button>
            </div>
        </Panel>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" v-if="summary">
            <StatCard
                label="Total Records"
                :value="summary.total_records"
                icon="list"
                icon-color="blue"
            />
            <StatCard
                label="Total Deposit"
                :value="summary.total_deposit"
                prefix="$"
                icon="money-bill-wave"
                icon-color="green"
                format-type="currency"
            />
            <StatCard
                label="Total Fees"
                :value="summary.total_fees"
                prefix="$"
                icon="receipt"
                icon-color="yellow"
                format-type="currency"
            />
            <StatCard
                label="Net Amount"
                :value="summary.total_amount"
                prefix="$"
                icon="calculator"
                icon-color="indigo"
                format-type="currency"
            />
        </div>

        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-200px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-100 dark:bg-gray-800 md:sticky top-0 !z-[101]">
                        <tr>
                            <Th>Company</Th>
                            <Th>Bank Date</Th>
                            <Th>Ledger</Th>
                            <Th class="text-right">Deposit</Th>
                            <Th class="text-right">Input Fees</Th>
                            <Th class="text-right">Net Amount</Th>
                            <Th>Actions</Th>
                        </tr>
                    </thead>
                    <tbody
                        v-if="paginatedData.length > 0"
                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <tr
                            v-for="item in paginatedData"
                            :key="item.child_amount_id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <Td>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ item.store_number }} - {{ item.company_name }}</div>
                                </div>
                            </Td>
                            <Td>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ formatDate(item.bank_date, true) }}
                                </span>
                            </Td>
                            <Td>
                                <span class="text-primary-600 dark:text-primary-400">{{ filters.ledger?.name }}</span>
                            </Td>
                            <Td align="right">
                                <span class="text-green-600 dark:text-green-400 font-medium">${{ formatNumber(item.deposit) }}</span>
                            </Td>
                            <Td class="!p-1 text-right">
                                <div class="flex items-center justify-end">
                                    <Input 
                                        type="number" 
                                        :disabled="!can('missing-bank-amount-report', 'update')"
                                        class="w-20"
                                        step="0.01"
                                        v-model.number="item.input_fees"
                                        :placeholder="item.fees > 0 ? `${formatNumber(item.fees)}` : '0.00'"
                                    />
                                </div>
                            </Td>
                            <Td align="right">
                                <span 
                                    class="font-medium"
                                    :class="getNetAmountClass(item)"
                                >
                                    ${{ formatNumber(getNetAmount(item)) }}
                                </span>
                            </Td>
                            <Td>
                                <div class="flex gap-1">
                                    <Button
                                        v-if="can('missing-bank-amount-report', 'update')"
                                        icon-left="save"
                                        size="xs"
                                        variant="primary"
                                        @click="updateSingleItem(item)"
                                        :loading="updating"
                                        :title="'Update fees for this item'"
                                    />
                                    <IconMenuDropdown icon="more-vertical" icon-size="sm">
                                        <template #default="{ close }">
                                            <button
                                                v-if="can('missing-bank-amount-report', 'manual-settlement')"
                                                @click="openManualSettlementModal(item); close()"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                                            >
                                                <i class="fas fa-link"></i>
                                                Settle Manually
                                            </button>
                                            <button
                                                v-if="isCashClearing()"
                                                @click="transferToAccount(item); close()"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                                            >
                                                <i class="fas fa-exchange-alt"></i>
                                                Transfer to Account
                                            </button>
                                            <button
                                                @click="viewBankEntry(item.bank_entry_id); close()"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                                            >
                                                <i class="fas fa-eye"></i>
                                                View Details
                                            </button>
                                        </template>
                                    </IconMenuDropdown>
                                </div>
                            </Td>
                        </tr>
                    </tbody>
                    <tbody v-else class="bg-white dark:bg-gray-800">
                        <tr>
                            <td colspan="7" class="p-8">  
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Missing Amounts Found"
                                    message="All Visa/Master amounts for the selected period are settled."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div
                v-if="reportData.length > 0"
                class="px-4 py-3 border-t border-gray-200 dark:border-gray-700"
            >
                <Pagination
                    :collection="paginationCollection"
                    :loading="loading"
                    :limit="itemsPerPage"
                    :limit-options="[10, 20, 50, 100]"
                    @page-change="handlePageChange"
                />
            </div>
        </Panel>

        <!-- Update Summary Modal -->
        <Modal
            v-model="showModal"
            title="Update & Settlement Summary"
            size="lg"
            :show-footer="true"
        >
            <div v-if="updateResult">
                <div v-if="updateResult.success" class="bg-green-100 dark:bg-green-800 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4">
                    <h6 class="flex items-center font-semibold"><i class="fas fa-check-circle me-2"></i>Update Successful</h6>
                    <p class="mb-0">{{ updateResult.message }}</p>
                </div>
                <div v-else class="bg-red-100 dark:bg-red-800 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4">
                    <h6 class="flex items-center font-semibold"><i class="fas fa-exclamation-triangle me-2"></i>Update Failed</h6>
                    <p class="mb-0">{{ updateResult.message }}</p>
                </div>
                
                <div v-if="updateResult.data" class="mt-3">
                    <h6 class="font-semibold text-gray-900 dark:text-white mb-2">Summary:</h6>
                    <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                        <li><strong>Updated Items:</strong> {{ updateResult.data.updated_items?.length || 0 }}</li>
                        <li><strong>Total Amount:</strong> ${{ formatNumber(updateResult.data.total_amount || 0) }}</li>
                        <li><strong>Total Fees:</strong> ${{ formatNumber(updateResult.data.total_fees || 0) }}</li>
                    </ul>

                    <div v-if="updateResult.data.settlement_result" class="mt-3">
                        <h6 class="font-semibold text-gray-900 dark:text-white mb-2">Settlement Result:</h6>
                        <div 
                            class="px-4 py-3 rounded"
                            :class="updateResult.data.settlement_result.success ? 'bg-green-100 dark:bg-green-800 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200' : 'bg-yellow-100 dark:bg-yellow-800 border border-yellow-400 dark:border-yellow-600 text-yellow-700 dark:text-yellow-200'"
                        >
                            {{ updateResult.data.settlement_result.message }}
                        </div>
                    </div>
                </div>
            </div>
            
            <template #footer>
                <Button variant="secondary" @click="showModal = false">Close</Button>
                <!-- <Button variant="primary" @click="refreshAndCloseModal">Refresh Data</Button> -->
            </template>
        </Modal>

        <!-- Manual Settlement Modal -->
        <Modal
            v-model="showManualSettlementModal"
            title="Settle Manually"
            size="2xl"
            :show-footer="true"
        >
            <div v-if="selectedItemForSettlement">
                <div class="mb-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <strong>Company:</strong> {{ selectedItemForSettlement.company_display }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <strong>Bank Date:</strong> {{ formatDate(selectedItemForSettlement.bank_date) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <strong>Deposit Amount:</strong> ${{ formatNumber(selectedItemForSettlement.total_amount) }}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <Input
                            type="date"
                            v-model="manualSettlementForm.start_date"
                            @change="validateDateRange"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            End Date <span class="text-red-500">*</span>
                        </label>
                        <Input
                            type="date"
                            v-model="manualSettlementForm.end_date"
                            @change="validateDateRange"
                        />
                    </div>
                </div>

                <div class="mb-4">
                    <Button
                        icon-left="search"
                        size="sm"
                        variant="primary"
                        @click="fetchAvailableItems"
                        :loading="loadingAvailableItems"
                        :disabled="!manualSettlementForm.start_date || !manualSettlementForm.end_date"
                    >
                        Search Items
                    </Button>
                </div>

                <div v-if="dateRangeError" class="bg-red-100 dark:bg-red-800 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 px-4 py-2 rounded mb-4 text-sm">
                    {{ dateRangeError }}
                </div>

                <div v-if="availableItems.length > 0" class="mt-4">
                    <h6 class="font-semibold text-gray-900 dark:text-white mb-3">Available Items:</h6>
                    
                    <!-- Search Filter -->
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Search by Description or Amount
                        </label>
                        <Input
                            type="text"
                            v-model="manualSettlementForm.search"
                            placeholder="Enter description or amount..."
                        />
                    </div>

                    <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                            <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="filteredAvailableItems.length === 0">
                                    <td colspan="4" class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No items match your search criteria
                                    </td>
                                </tr>
                                <tr v-for="item in filteredAvailableItems" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ formatDate(item.date) }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-300">
                                        {{ item.description }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                                        ${{ formatNumber(item.amount) }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-center">
                                        <Button
                                            size="xs"
                                            variant="success"
                                            @click="confirmManualSettlement(item)"
                                            :loading="settlingManually"
                                        >
                                            Select
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else-if="availableItemsFetched && availableItems.length === 0" class="mt-4">
                    <div class="bg-yellow-100 dark:bg-yellow-800 border border-yellow-400 dark:border-yellow-600 text-yellow-700 dark:text-yellow-200 px-4 py-3 rounded text-sm">
                        No unsettled items found for the selected date range.
                    </div>
                </div>
            </div>
            
            <template #footer>
                <Button variant="secondary" @click="closeManualSettlementModal">Close</Button>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import NoData from '@/components/ui/no-data.vue'
import Modal from '@/components/common/Modal.vue'
import StatCard from '@/components/ui/stat-card.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'
import Input from '@/components/ui/input.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { usePermission } from '@/composables/usePermission'
import Pagination from '@/components/ui/pagination.vue'
import IconMenuDropdown from '@/components/ui/IconMenuDropdown.vue'

    
const { can } = usePermission()

const message = useMessage()

const loading = ref(false)
const updating = ref(false)
const reportData = ref([])
const companies = ref([])
const selectedItems = ref([])
const updateResult = ref(null)
const showModal = ref(false)
const showManualSettlementModal = ref(false)
const selectedItemForSettlement = ref(null)
const loadingAvailableItems = ref(false)
const availableItems = ref([])
const availableItemsFetched = ref(false)
const settlingManually = ref(false)
const dateRangeError = ref('')
const debouncedSearch = ref('')
const summary = ref(null)
let debounceTimeout = null
const paginationCollection = ref(null)
const transferring = ref(false)
const manualSettlementForm = ref({
    start_date: '',
    end_date: '',
    search: '',
})

const filters = ref({
    company: null,
    ledger: null,
    start_date: '',
    end_date: ''
})

const ledgers = ref(null)

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(20)
const totalPages = computed(() => Math.ceil(reportData.value.length / itemsPerPage.value))

// Debounce search input
watch(() => manualSettlementForm.value.search, (newValue) => {
    if (debounceTimeout) {
        clearTimeout(debounceTimeout)
    }
    debounceTimeout = setTimeout(() => {
        debouncedSearch.value = newValue
    }, 300)
})

// Fuzzy search helper function
const fuzzyMatch = (text, search) => {
    if (!text || !search) return false
    
    text = text.toLowerCase()
    search = search.toLowerCase()
    
    // Direct substring match
    if (text.includes(search)) return true
    
    // Fuzzy match - check if all characters in search appear in order in text
    let searchIndex = 0
    for (let i = 0; i < text.length && searchIndex < search.length; i++) {
        if (text[i] === search[searchIndex]) {
            searchIndex++
        }
    }
    return searchIndex === search.length
}

onMounted(async () => {
    await loadLedgers()
    await applyFilters()
})
const loadLedgers = async () => {
    try {
        const response = await useRequest('get', '/search/ledgers',null, {
            params: {
                codes: ['1001.01', '1003.01']
            }
        })
        ledgers.value = response?.collection || []
        filters.value.ledger = ledgers.value.find(ledger => ledger.name.includes('1001.01'))
    } catch (error) {
        console.error('Failed to load ledgers:', error)
        message.error('Failed to load ledgers')
    }
}

const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return reportData.value.slice(start, end)
})



const filteredAvailableItems = computed(() => {
    let items = availableItems.value

    if (debouncedSearch.value) {
        const searchTerm = debouncedSearch.value.trim()
        
        items = items.filter(item => {
            // Fuzzy search in description
            const matchesDescription = fuzzyMatch(item.description, searchTerm)
            
            // Search by amount (exact match with tolerance or partial match)
            const searchAsNumber = parseFloat(searchTerm)
            const matchesAmount = !isNaN(searchAsNumber) && Math.abs(item.amount - searchAsNumber) < 0.01
            
            // Also check if the search term appears in the formatted amount
            const formattedAmount = item.amount.toString()
            const matchesPartialAmount = formattedAmount.includes(searchTerm)
            
            return matchesDescription || matchesAmount || matchesPartialAmount
        })
    }

    return items
})

const applyFilters = async (page = 1, perPage = 20) => {
    loading.value = true
    currentPage.value = page
    itemsPerPage.value = perPage
    const payload = {
        company_id: filters.value.company?.id,
        ledger_id: filters.value.ledger?.id,
        start_date: filters.value.start_date || null,
        end_date: filters.value.end_date || null,
    }
    try {
        const response = await useRequest('post', `/ar/missing-bank-amount-report?page=${page}&limit=${perPage}`, payload)
        
        reportData.value = response?.collection?.data || []
        selectedItems.value = []
        
        // Prefill input_fees with current fees value if it exists and input_fees is not already set
        reportData.value.forEach(item => {
            if (item.fees > 0 && (item.input_fees === undefined || item.input_fees === null || item.input_fees === 0)) {
                item.input_fees = parseFloat(item.fees)
            }
        })
        paginationCollection.value = {
            current_page: response?.collection?.current_page || 1,
            last_page: response?.collection?.last_page || 1,
            from: response?.collection?.from || 0,
            to: response?.collection?.to || 0,
            total: response?.collection?.total || 0,
            per_page: response?.collection?.per_page || 20,
            has_prev: response?.collection?.has_prev || false,
            has_next: response?.collection?.has_next || false,
        }
        summary.value = {
            total_records: response?.total || 0,
            total_fees: response?.total_fees || 0,
            total_deposit: response?.total_deposit || 0,
            total_amount: response?.total_amount || 0,
        }
    } catch (error) {
        console.error('Failed to load missing bank amounts:', error)
        message.error('Failed to load missing bank amounts')
    } finally {
        loading.value = false
    }
}
const getNetAmount = (item) => {
    return Math.max(0, parseFloat(item.deposit) + (parseFloat(item.input_fees) || 0))
}

const getNetAmountClass = (item) => {
    const netAmount = getNetAmount(item)
    const originalNet = item.total_amount
    
    if (netAmount > originalNet) {
        return 'text-green-600 dark:text-green-400'
    } else if (netAmount < originalNet) {
        return 'text-red-600 dark:text-red-400'
    }
    return 'text-gray-900 dark:text-white'
}

const updateSingleItem = async (item) => {
    const items = [{
        child_amount_id: item.id,
        fees: item.input_fees || 0,
    }]
    
    await performUpdate(items)
}


const updateAllFees = async () => {
    const items = reportData.value.map(item => ({
        child_amount_id: item.id,
        fees: item.input_fees || 0,
    }))
    
    await performUpdate(items)
}
const performUpdate = async (items) => {
    updating.value = true
    try {
        const response = await useRequest('post', '/ar/missing-bank-amount-report/update-settle-bank-amounts', {
            items: items,
            ledger_id: filters.value.ledger?.id
        })
        
        updateResult.value = response
        showModal.value = true
        refreshAndCloseModal()
        if (response?.success) {
            message.success('Amounts updated and settlement attempted successfully')
        } else {
            message.error('Update failed: ' + (response?.message || 'Unknown error'))
        }
    } catch (error) {
        console.error('Failed to update amounts:', error)
        message.error('Failed to update amounts')
    } finally {
        updating.value = false
    }
}
const viewBankEntry = (bankEntryId) => {
    // Navigate to bank entry details if route exists
    window.open(`/data-entry/bank-entries/${bankEntryId}`, '_blank')
}

const refreshAndCloseModal = async () => {
    showModal.value = false
    await applyFilters()
    // message.success('Data refreshed successfully')
}

const formatDate = (date, withDay = false) => {
    if (!date) return ''
    
    // Parse the date string directly to avoid timezone issues
    const dateStr = date.split('T')[0] // Get only the date part (YYYY-MM-DD)
    const [year, month, day] = dateStr.split('-').map(Number)
    
    // Create date using UTC to avoid timezone conversion
    const dateObj = new Date(Date.UTC(year, month - 1, day))
    
    return dateObj.toLocaleDateString('en-US', {
        weekday: withDay ? 'long' : undefined,
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        timeZone: 'UTC' // Force UTC to prevent timezone shifts
    })
}

const openManualSettlementModal = (item) => {
    selectedItemForSettlement.value = item
    showManualSettlementModal.value = true
    availableItems.value = []
    availableItemsFetched.value = false
    dateRangeError.value = ''
    
    // Set default date range (last 7 days)
    const endDate = new Date(item.bank_date)
    const startDate = new Date(endDate)
    startDate.setDate(startDate.getDate() - 7)
    
    manualSettlementForm.value = {
        start_date: startDate.toISOString().split('T')[0],
        end_date: endDate.toISOString().split('T')[0],
        search: '',
    }
}

const closeManualSettlementModal = () => {
    showManualSettlementModal.value = false
    selectedItemForSettlement.value = null
    availableItems.value = []
    availableItemsFetched.value = false
    dateRangeError.value = ''
    manualSettlementForm.value = {
        start_date: '',
        end_date: '',
        search: '',
    }
}

const validateDateRange = () => {
    dateRangeError.value = ''
    
    if (!manualSettlementForm.value.start_date || !manualSettlementForm.value.end_date) {
        return false
    }
    
    const startDate = new Date(manualSettlementForm.value.start_date)
    const endDate = new Date(manualSettlementForm.value.end_date)
    
    if (startDate > endDate) {
        dateRangeError.value = 'Start date must be before or equal to end date'
        return false
    }
    
    const diffDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24))
    
    if (diffDays > 60) {
        dateRangeError.value = 'Date range cannot exceed 60 days'
        return false
    }
    
    return true
}

const fetchAvailableItems = async () => {
    if (!validateDateRange()) {
        return
    }
    
    loadingAvailableItems.value = true
    availableItemsFetched.value = false
    
    try {
        const response = await useRequest('post', '/ar/missing-bank-amount-report/available-items', {
            ledger_id: filters.value.ledger?.id,
            company_id: selectedItemForSettlement.value.company_id,
            start_date: manualSettlementForm.value.start_date,
            end_date: manualSettlementForm.value.end_date,
            target_amount: selectedItemForSettlement.value.total_amount,
        })
        
        if (response?.success) {
            availableItems.value = response.items || []
            availableItemsFetched.value = true
        } else {
            message.error(response?.message || 'Failed to fetch available items')
        }
    } catch (error) {
        console.error('Failed to fetch available items:', error)
        message.error('Failed to fetch available items')
    } finally {
        loadingAvailableItems.value = false
    }
}

const confirmManualSettlement = async (item) => {
    if (!confirm(`Are you sure you want to settle this bank entry with the selected item?\n\nBank Entry: $${formatNumber(selectedItemForSettlement.value.deposit)}\nSelected Item: $${formatNumber(item.amount)}\nDate: ${formatDate(item.date)}`)) {
        return
    }
    
    settlingManually.value = true
    
    try {
        const response = await useRequest('post', '/ar/missing-bank-amount-report/manual-settlement', {
            child_amount_id: selectedItemForSettlement.value.child_amount_id,
            item_type: filters.value.ledger?.name.includes('1001.01') ? 'pj_payment' : 'daily_sale',
            item_id: item.id,
            ledger_id: filters.value.ledger?.id,
            bank_entry_id: selectedItemForSettlement.value.bank_entry_id,
        })
        
        if (response?.success) {
            message.success('Successfully settled manually')
            closeManualSettlementModal()
            await applyFilters()
        } else {
            message.error(response?.message || 'Failed to settle manually')
        }
    } catch (error) {
        console.error('Failed to settle manually:', error)
        message.error('Failed to settle manually')
    } finally {
        settlingManually.value = false
    }
}

const handlePageChange = (page, perPage) => {
    applyFilters(page, perPage)
}

const isCashClearing = () => {
    return filters.value.ledger?.name?.includes('1003.01')
}

const transferToAccount = async (item) => {
    if (!confirm(`Are you sure you want to transfer this amount to account?\n\nAmount: $${formatNumber(item.deposit)}\n\nThis will update the bank entry and create a new entry with ledger 1001.55.`)) {
        return
    }
    
    transferring.value = true
    
    try {
        const response = await useRequest('post', '/ar/missing-bank-amount-report/transfer-to-account', {
            child_amount_id: item.child_amount_id,
            amount: item.deposit,
        })
        
        if (response?.success) {
            message.success('Successfully transferred to account')
            await applyFilters(currentPage.value, itemsPerPage.value)
        } else {
            message.error(response?.message || 'Failed to transfer to account')
        }
    } catch (error) {
        console.error('Failed to transfer to account:', error)
        message.error('Failed to transfer to account')
    } finally {
        transferring.value = false
    }
}

</script>