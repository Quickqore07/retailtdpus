<template>
    <div>
        <div
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8"
        >
            <MultiStatCard
                class="sm:col-span-2 xl:col-span-4"
                label="WorkBright Onboarding"
                :items="[
                    { label: 'In Progress', value: workbrightStats.workbright_in_progress_count ?? 0 },
                    { label: 'Waiting for Approval', value: workbrightStats.waiting_for_approval_count ?? 0 },
                    { label: 'Waiting for Section 2 Verification', value: workbrightStats.waiting_for_section2_verification_count ?? 0 },
                    { label: 'Waiting for Employee Authorized', value: workbrightStats.waiting_for_employee_authorized_count ?? 0 },
                    // { label: 'Waiting for Internal Review', value: workbrightStats.waiting_for_internal_review_count ?? 0 },
                    { label: 'TNC', value: workbrightStats.tnc_count ?? 0 },
                ]"
            />
        </div>

        <div class="mb-4 sm:mb-6">
            <Button
                type="button"
                variant="primary"
                :loading="refreshLoading"
                @click="refreshWorkbrightStatus"
            >
                Refresh WorkBright Status
            </Button>
        </div>

        <div
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8"
        >
            <WorkbrightOverview
                title="WorkBright In Progress"
                export-endpoint="dashboard/workbright-export"
                :rows="inProgressData"
                :loading="inProgressLoading"
                :collection="inProgressCollection"
                @page-change="loadInProgressData"
                @search-change="onInProgressSearchChange"
            />
            <WorkbrightOverview
                title="Waiting for Approval"
                export-endpoint="dashboard/workbright-export"
                :rows="waitingForApprovalData"
                :loading="waitingForApprovalLoading"
                :collection="waitingForApprovalCollection"
                @page-change="loadWaitingForApprovalData"
                @search-change="onWaitingForApprovalSearchChange"
            />
            <WorkbrightOverview
                title="Waiting for Section 2 Verification"
                export-endpoint="dashboard/workbright-export"
                :rows="waitingForSection2Data"
                :loading="waitingForSection2Loading"
                :collection="waitingForSection2Collection"
                @page-change="loadWaitingForSection2Data"
                @search-change="onWaitingForSection2SearchChange"
            />
            <WorkbrightOverview
                title="Waiting for Employee Authorized"
                export-endpoint="dashboard/workbright-export"
                :rows="waitingForEmployeeAuthorizedData"
                :loading="waitingForEmployeeAuthorizedLoading"
                :collection="waitingForEmployeeAuthorizedCollection"
                selectable
                :authorize-loading="authorizeEmployeeLoading"
                @page-change="loadWaitingForEmployeeAuthorizedData"
                @search-change="onWaitingForEmployeeAuthorizedSearchChange"
                @authorize="authorizeEmployees"
            />
            <!-- <WorkbrightOverview
                title="Waiting for Internal Review"
                export-endpoint="dashboard/workbright-export"
                :rows="waitingForInternalReviewData"
                :loading="waitingForInternalReviewLoading"
                :collection="waitingForInternalReviewCollection"
                @page-change="loadWaitingForInternalReviewData"
                @search-change="onWaitingForInternalReviewSearchChange"
            /> -->
            <WorkbrightOverview
                title="TNC"
                export-endpoint="dashboard/workbright-export"
                :rows="tncData"
                :loading="tncLoading"
                :collection="tncCollection"
                @page-change="loadTncData"
                @search-change="onTncSearchChange"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import Button from '@/components/ui/button.vue'
import MultiStatCard from '@/components/ui/multi-stat-card.vue'
import WorkbrightOverview from '@/components/dashboard/WorkbrightOverview.vue'

const message = useMessage()

const inProgressData = ref([])
const inProgressLoading = ref(false)
const inProgressSearch = ref('')
const inProgressCollection = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    per_page: 1,
    prev_page_url: null,
    next_page_url: null,
})
const waitingForApprovalData = ref([])
const waitingForApprovalLoading = ref(false)
const waitingForApprovalSearch = ref('')
const waitingForApprovalCollection = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    per_page: 1,
    prev_page_url: null,
    next_page_url: null,
})
const waitingForSection2Data = ref([])
const waitingForSection2Loading = ref(false)
const waitingForSection2Search = ref('')
const waitingForSection2Collection = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    per_page: 1,
    prev_page_url: null,
    next_page_url: null,
})
const waitingForEmployeeAuthorizedData = ref([])
const waitingForEmployeeAuthorizedLoading = ref(false)
const waitingForEmployeeAuthorizedSearch = ref('')
const waitingForEmployeeAuthorizedCollection = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    per_page: 1,
    prev_page_url: null,
    next_page_url: null,
})
// const waitingForInternalReviewData = ref([])
// const waitingForInternalReviewLoading = ref(false)
// const waitingForInternalReviewSearch = ref('')
// const waitingForInternalReviewCollection = ref({
//     current_page: 1,
//     last_page: 1,
//     from: 0,
//     to: 0,
//     total: 0,
//     per_page: 1,
//     prev_page_url: null,
//     next_page_url: null,
// })
const tncData = ref([])
const tncLoading = ref(false)
const tncSearch = ref('')
const tncCollection = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    per_page: 1,
    prev_page_url: null,
    next_page_url: null,
})
const refreshLoading = ref(false)
const authorizeEmployeeLoading = ref(false)
const workbrightStats = ref({})

const emptyPagination = (perPage = 30) => ({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    per_page: perPage,
    prev_page_url: null,
    next_page_url: null,
})

async function loadInProgressData(page = 1, limit = 30) {
    inProgressLoading.value = true
    try {
        const data = await useRequest('get', 'dashboard/workbright-in-progress-data', null, {
            params: { page, limit, search: inProgressSearch.value },
        })
        inProgressData.value = data?.data || []
        inProgressCollection.value = data?.pagination || {
            current_page: 1,
            last_page: data?.pagination?.last_page || 1,
            from: data?.pagination?.from || 0,
            to: data?.pagination?.to || 0,
            total: data?.pagination?.total || 0,
            per_page: data?.pagination?.per_page || 30,
            has_prev: data?.pagination?.has_prev || false,
            has_next: data?.pagination?.has_next || false,
        }
    } catch (err) {
        console.error('Failed to load WorkBright in progress data:', err)
        inProgressData.value = []
        inProgressCollection.value = emptyPagination(30)
    } finally {
        inProgressLoading.value = false
    }
}

function onInProgressSearchChange(value) {
    inProgressSearch.value = value || ''
    loadInProgressData(1, inProgressCollection.value?.per_page || 30)
}

async function loadWaitingForApprovalData(page = 1, limit = 30) {
    waitingForApprovalLoading.value = true
    try {
        const data = await useRequest('get', 'dashboard/workbright-waiting-for-approval-data', null, {
            params: { page, limit, search: waitingForApprovalSearch.value },
        })
        waitingForApprovalData.value = data?.data || []
        waitingForApprovalCollection.value = data?.pagination || emptyPagination(30)
    } catch (err) {
        console.error('Failed to load waiting for approval data:', err)
        waitingForApprovalData.value = []
        waitingForApprovalCollection.value = emptyPagination(30)
    } finally {
        waitingForApprovalLoading.value = false
    }
}

function onWaitingForApprovalSearchChange(value) {
    waitingForApprovalSearch.value = value || ''
    loadWaitingForApprovalData(1, waitingForApprovalCollection.value?.per_page || 30)
}

async function loadWaitingForSection2Data(page = 1, limit = 30) {
    waitingForSection2Loading.value = true
    try {
        const data = await useRequest('get', 'dashboard/workbright-waiting-for-section2-verification-data', null, {
            params: { page, limit, search: waitingForSection2Search.value },
        })
        waitingForSection2Data.value = data?.data || []
        waitingForSection2Collection.value = data?.pagination || emptyPagination(30)
    } catch (err) {
        console.error('Failed to load section 2 verification data:', err)
        waitingForSection2Data.value = []
        waitingForSection2Collection.value = emptyPagination(30)
    } finally {
        waitingForSection2Loading.value = false
    }
}

function onWaitingForSection2SearchChange(value) {
    waitingForSection2Search.value = value || ''
    loadWaitingForSection2Data(1, waitingForSection2Collection.value?.per_page || 30)
}

async function loadWaitingForEmployeeAuthorizedData(page = 1, limit = 30) {
    waitingForEmployeeAuthorizedLoading.value = true
    try {
        const data = await useRequest('get', 'dashboard/workbright-waiting-for-employee-authorized-data', null, {
            params: { page, limit, search: waitingForEmployeeAuthorizedSearch.value },
        })
        waitingForEmployeeAuthorizedData.value = data?.data || []
        waitingForEmployeeAuthorizedCollection.value = data?.pagination || emptyPagination(30)
    } catch (err) {
        console.error('Failed to load waiting for employee authorized data:', err)
        waitingForEmployeeAuthorizedData.value = []
        waitingForEmployeeAuthorizedCollection.value = emptyPagination(30)
    } finally {
        waitingForEmployeeAuthorizedLoading.value = false
    }
}

function onWaitingForEmployeeAuthorizedSearchChange(value) {
    waitingForEmployeeAuthorizedSearch.value = value || ''
    loadWaitingForEmployeeAuthorizedData(1, waitingForEmployeeAuthorizedCollection.value?.per_page || 30)
}

async function authorizeEmployees(ids = []) {
    if (!ids.length) {
        message.error('Select at least one employee to authorize.')
        return
    }

    const confirmed = confirm(
        `Authorize ${ids.length} selected employee${ids.length === 1 ? '' : 's'}?\n\nThis will mark them as authorized.`
    )
    if (!confirmed) {
        return
    }

    authorizeEmployeeLoading.value = true
    try {
        const data = await useRequest('post', 'dashboard/workbright-authorize-employees', { ids })
        if (data?.success) {
            message.success(data.message || 'Employees authorized successfully.')
            await Promise.all([
                loadWaitingForEmployeeAuthorizedData(1, waitingForEmployeeAuthorizedCollection.value?.per_page || 30),
                loadWorkbrightStats(),
            ])
        } else {
            message.error(data?.message || 'Failed to authorize employees.')
        }
    } catch (err) {
        console.error('Failed to authorize employees:', err)
        message.error(err?.response?.data?.message || 'Failed to authorize employees.')
    } finally {
        authorizeEmployeeLoading.value = false
    }
}

// async function loadWaitingForInternalReviewData(page = 1, limit = 30) {
//     waitingForInternalReviewLoading.value = true
//     try {
//         const data = await useRequest('get', 'dashboard/workbright-waiting-for-internal-review-data', null, {
//             params: { page, limit, search: waitingForInternalReviewSearch.value },
//         })
//         waitingForInternalReviewData.value = data?.data || []
//         waitingForInternalReviewCollection.value = data?.pagination || emptyPagination(30)
//     } catch (err) {
//         console.error('Failed to load waiting for internal review data:', err)
//         waitingForInternalReviewData.value = []
//         waitingForInternalReviewCollection.value = emptyPagination(30)
//     } finally {
//         waitingForInternalReviewLoading.value = false
//     }
// }

// function onWaitingForInternalReviewSearchChange(value) {
//     waitingForInternalReviewSearch.value = value || ''
//     loadWaitingForInternalReviewData(1, waitingForInternalReviewCollection.value?.per_page || 30)
// }


async function loadTncData(page = 1, limit = tncCollection.value?.per_page || 30) {
    tncLoading.value = true
    try {
        const data = await useRequest('get', 'dashboard/workbright-tnc-data', null, {
            params: { page, limit, search: tncSearch.value },
        })
        tncData.value = data?.data || []
        tncCollection.value = data?.pagination || emptyPagination(limit)
    } catch (err) {
        console.error('Failed to load TNC data:', err)
        tncData.value = []
        tncCollection.value = emptyPagination(limit)
    } finally {
        tncLoading.value = false
    }
}

function onTncSearchChange(value) {
    tncSearch.value = value || ''
    loadTncData(1, tncCollection.value?.per_page || 30)
}

async function loadWorkbrightStats() {
    const data = await useRequest('get', 'dashboard/workbright-stats')
    workbrightStats.value = data || {}

    console.log(workbrightStats.value)
}

async function reloadWorkbrightDashboardData() {
    await Promise.all([
        loadInProgressData(),
        loadWaitingForApprovalData(),
        loadWaitingForSection2Data(),
        loadWaitingForEmployeeAuthorizedData(),
        // loadWaitingForInternalReviewData(),
        loadTncData(),
        loadWorkbrightStats(),
    ])
}

async function refreshWorkbrightStatus() {
    refreshLoading.value = true
    try {
        const data = await useRequest('post', 'dashboard/workbright-refresh-status')
        if (data.success) {
            message.success(data.message || 'WorkBright status sync started')
            window.setTimeout(() => {
                reloadWorkbrightDashboardData()
            }, 15000)
        } else {
            message.error(data.message || 'Failed to refresh WorkBright status')
        }
    } catch (err) {
        console.error('Failed to refresh WorkBright status:', err)
        message.error(err?.response?.data?.message || 'Failed to refresh WorkBright status')
    } finally {
        refreshLoading.value = false
    }
}

onMounted(() => {
    const tasks = [
        loadInProgressData(),
        loadWaitingForApprovalData(),
        loadWaitingForSection2Data(),
        loadWaitingForEmployeeAuthorizedData(),
        // loadWaitingForInternalReviewData(),
        loadTncData(),
        loadWorkbrightStats(),
    ]
    Promise.all(tasks)
})
</script>
