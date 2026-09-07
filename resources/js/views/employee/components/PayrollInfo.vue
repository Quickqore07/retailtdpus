<template>
    <Panel  :divider="true">
        <template #header>
            <h5 class="text-xl font-bold !mb-0">Payroll Info</h5>
        </template>
        <div class="overflow-x-auto max-h-[calc(100vh-280px)] min-h-[200px] relative">

            <table class="w-full">
                <thead>
                    <tr>
                        <Th>Sr. No.</Th>
                        <Th>Date</Th>
                        <Th>Company</Th>
                        <Th>Role</Th>
                        <Th class="text-right">Total Hours</Th> 
                        <Th class="text-right">Payroll Methods</Th>
                        <Th class="text-right">Tips</Th>
                        <Th class="text-right">Tips Due</Th>
                        <Th class="text-right">Mileage Due</Th>
                        <Th class="text-right">Gross Pay</Th>
                        <Th class="text-right">Check Methods</Th>
                        <Th class="text-right">Instant Methods</Th>
                        <Th class="text-right">Total Earnings</Th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in payrollInfo" :key="item.id" class="border-b border-gray-200 dark:border-gray-700">
                        <Td>{{ index + 1 }}</Td>
                        <Td>{{ formatDate(item.eow) }}</Td>
                        <Td>{{ item.company?.name }}</Td>
                        <Td>{{ item.role?.name }}</Td>
                        <Td class="text-right">{{ item.total_hours }}</Td>
                        <Td class="text-right">{{ item.payroll_methods }}</Td>
                        <Td class="text-right">{{ item.tips }}</Td>
                        <Td class="text-right">{{ item.tips_due }}</Td>
                        <Td class="text-right">{{ item.mileage_due }}</Td>
                        <Td class="text-right">{{ item.gross_pay }}</Td>
                        <Td class="text-right">
                            <div 
                                class="flex flex-col items-end cursor-pointer hover:text-blue-600 transition-colors"
                                @dblclick="openCheckDetailsModal(item)"
                                :class="{ 'text-blue-500': item.check_data }"
                            >
                                <span>
                                    {{ item.check_methods }}
                                </span>
                                <small v-if="item.check_data">
                                    ({{ item.check_data.check_number }})
                                </small>
                            </div>
                        </Td>
                        <Td class="text-right">{{ item.instant_methods }}</Td>
                        <Td class="text-right">{{ item.total_earnings }}</Td>
                    </tr>
                </tbody>
            </table>
        </div>

    </Panel>

    <!-- Check Details Modal -->
    <Modal v-model="showCheckModal" title="Check Details" size="5xl">
        <div v-if="selectedCheck" class="space-y-6">
            <!-- Check Information -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h5 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Check Information</h5>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Check Number</label>
                        <p class="text-gray-900 dark:text-white">{{ selectedCheck.check_data?.check_number || 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Check Date</label>
                        <p class="text-gray-900 dark:text-white">{{ formatDate(selectedCheck.check_data?.check_date) || 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Check Amount</label>
                        <p class="text-gray-900 dark:text-white">${{ selectedCheck.check_data?.check_amount || '0.00' }}</p>
                    </div>
                </div>
            </div>

            <!-- Company Information -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h5 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Company & Role Information</h5>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Company</label>
                        <p class="text-gray-900 dark:text-white">{{ selectedCheck.company?.name || 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Role</label>
                        <p class="text-gray-900 dark:text-white">{{ selectedCheck.role?.name || 'N/A' }}</p>
                    </div>
                    <div v-if="selectedCheck.check_data?.from_company">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">From Company</label>
                        <p class="text-gray-900 dark:text-white">{{ selectedCheck.check_data?.from_company?.name || 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Bank Information -->
            <div v-if="selectedCheck.check_data?.ledger" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h5 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Bank Information</h5>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Ledger Name</label>
                        <p class="text-gray-900 dark:text-white">{{ selectedCheck.check_data.ledger?.name || 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Account Type</label>
                        <p class="text-gray-900 dark:text-white">{{ selectedCheck.check_data.ledger?.account_type || 'N/A' }}</p>
                    </div>
                    <div v-if="selectedCheck.check_data.ledger?.ledger_details">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Bank Name</label>
                        <p class="text-gray-900 dark:text-white">{{ selectedCheck.check_data.ledger.ledger_details?.bank_name || 'N/A' }}</p>
                    </div>
                    <div v-if="selectedCheck.check_data.ledger?.ledger_details">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Account Number</label>
                        <p class="text-gray-900 dark:text-white">{{ selectedCheck.check_data.ledger.ledger_details?.account_no || 'N/A' }}</p>
                    </div>
                    <div v-if="selectedCheck.check_data.ledger?.ledger_details" class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Bank Address</label>
                        <p class="text-gray-900 dark:text-white">{{ selectedCheck.check_data.ledger.ledger_details?.bank_address || 'N/A' }}</p>
                    </div>
                    <div v-if="selectedCheck.check_data.ledger?.ledger_details">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Routing Number</label>
                        <p class="text-gray-900 dark:text-white">{{ selectedCheck.check_data.ledger.ledger_details?.routing || 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Payroll Summary -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Payroll Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Hours</label>
                        <p class="text-gray-900 dark:text-white">{{ selectedCheck.total_hours || '0' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Gross Pay</label>
                        <p class="text-gray-900 dark:text-white">${{ selectedCheck.gross_pay || '0.00' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Tips</label>
                        <p class="text-gray-900 dark:text-white">${{ selectedCheck.tips || '0.00' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Tips Due</label>
                        <p class="text-gray-900 dark:text-white">${{ selectedCheck.tips_due || '0.00' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Mileage Due</label>
                        <p class="text-gray-900 dark:text-white">${{ selectedCheck.mileage_due || '0.00' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Earnings</label>
                        <p class="text-gray-900 dark:text-white font-semibold">${{ selectedCheck.total_earnings || '0.00' }}</p>
                    </div>
                </div>
            </div>

            <!-- Review Status -->
            <div v-if="selectedCheck.check_data" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Review Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">AM Review</label>
                        <p class="text-gray-900 dark:text-white">
                            <span v-if="selectedCheck.check_data.am_reviewed" class="text-green-600 dark:text-green-400">Reviewed</span>
                            <span v-else class="text-yellow-600 dark:text-yellow-400">Pending</span>
                        </p>
                        <p v-if="selectedCheck.check_data.am_reviewer" class="text-sm text-gray-600 dark:text-gray-400">
                            By: {{ selectedCheck.check_data.am_reviewer.name }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">HR Review</label>
                        <p class="text-gray-900 dark:text-white">
                            <span v-if="selectedCheck.check_data.hr_reviewed" class="text-green-600 dark:text-green-400">Reviewed</span>
                            <span v-else class="text-yellow-600 dark:text-yellow-400">Pending</span>
                        </p>
                        <p v-if="selectedCheck.check_data.hr_reviewer" class="text-sm text-gray-600 dark:text-gray-400">
                            By: {{ selectedCheck.check_data.hr_reviewer.name }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Admin Review</label>
                        <p class="text-gray-900 dark:text-white">
                            <span v-if="selectedCheck.check_data.admin_reviewed" class="text-green-600 dark:text-green-400">Reviewed</span>
                            <span v-else class="text-yellow-600 dark:text-yellow-400">Pending</span>
                        </p>
                        <p v-if="selectedCheck.check_data.admin_reviewer" class="text-sm text-gray-600 dark:text-gray-400">
                            By: {{ selectedCheck.check_data.admin_reviewer.name }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-8">
            <p class="text-gray-600 dark:text-gray-400">No check data available</p>
        </div>
    </Modal>
</template>

<script setup>
import Panel from '@/components/ui/panel.vue';
import Modal from '@/components/common/Modal.vue';
import { useRequest } from '@/services/api';
import { ref, onMounted } from 'vue';
import Td from '@/components/ui/td.vue';
import Th from '@/components/ui/th.vue';
import { formatDate } from '@/utils/date';

const props = defineProps({
    employeeId: {
        type: String,
        required: true,
    },
});

const payrollInfo = ref([]);
const showCheckModal = ref(false);
const selectedCheck = ref(null);

const getPayrollInfo = async () => {
    const response = await useRequest('post', '/employee/payroll-info', {
        employee_id: props.employeeId,
    })
    payrollInfo.value = response.data;
}

const openCheckDetailsModal = (item) => {
    if (item.check_data) {
        selectedCheck.value = item;
        showCheckModal.value = true;
    }
}

onMounted(() => {
    getPayrollInfo();
})
</script>