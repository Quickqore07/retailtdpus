<template>
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <header class="mb-6 sm:mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="!text-xl !sm:text-2xl !md:text-3xl font-bold text-gray-900 dark:text-white mb-2">Dashboard</h1>
                <p class="text-sm !mb-0 sm:text-base text-gray-500 dark:text-gray-400">
                    <template v-if="dashboardSegment === 'workbright'">
                        WorkBright onboarding status, exports, and refresh.
                    </template>
                    <template v-else-if="dashboardSegment === 'finance'">
                        Financial overview and fund requirements.
                    </template>
                    <template v-else>
                        Welcome back! Here's what's happening with your business.
                    </template>
                </p>
            </div>
            <div
                v-if="showTabs"
                class="flex-shrink-0 w-full sm:w-auto"
                role="tablist"
                aria-label="Dashboard view"
            >
                <div class="flex gap-6 border-b border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="dashboardSegment === 'dashboard'"
                        class="px-1 pb-3 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 border-b-2"
                        :class="
                            dashboardSegment === 'dashboard'
                                ? 'text-primary border-primary dark:text-emerald-400 dark:border-emerald-400'
                                : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'
                        "
                        @click="handleDashboardSegmentChange('dashboard')"
                    >
                        Dashboard
                    </button>
                    <button
                        v-if="showFinanceTab"
                        type="button"
                        role="tab"
                        :aria-selected="dashboardSegment === 'finance'"
                        class="px-1 pb-3 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 border-b-2"
                        :class="
                            dashboardSegment === 'finance'
                                ? 'text-primary border-primary dark:text-emerald-400 dark:border-emerald-400'
                                : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'
                        "
                        @click="handleDashboardSegmentChange('finance')"
                    >
                        Finance
                    </button>
                    <button
                        v-if="showWorkbrightTab"
                        type="button"
                        role="tab"
                        :aria-selected="dashboardSegment === 'workbright'"
                        class="px-1 pb-3 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 border-b-2"
                        :class="
                            dashboardSegment === 'workbright'
                                ? 'text-primary border-primary dark:text-emerald-400 dark:border-emerald-400'
                                : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'
                        "
                        @click="handleDashboardSegmentChange('workbright')"
                    >
                        Workbright
                    </button>
                </div>
            </div>
        </header>

        <!-- Tab Content -->
        <DashboardMainTab v-if="dashboardSegment === 'dashboard'" />
        <FinanceTab v-if="dashboardSegment === 'finance'" />
        <WorkbrightTab v-if="dashboardSegment === 'workbright'" />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import DashboardMainTab from '@/components/dashboard/DashboardMainTab.vue'
import FinanceTab from '@/components/dashboard/FinanceTab.vue'
import WorkbrightTab from '@/components/dashboard/WorkbrightTab.vue'
import { usePermission } from '@/composables/usePermission'

const { can } = usePermission()

const showWorkbrightTab = computed(() => can('workbright-dashboard', 'show'))
const showFinanceTab = computed(() => can('fund-requirement', 'show'))
const showTabs = computed(() => showWorkbrightTab.value || showFinanceTab.value)
const dashboardSegment = ref('dashboard')

watch([showWorkbrightTab, showFinanceTab], () => {
    if (dashboardSegment.value === 'workbright' && !showWorkbrightTab.value) {
        dashboardSegment.value = 'dashboard'
    }
    if (dashboardSegment.value === 'finance' && !showFinanceTab.value) {
        dashboardSegment.value = 'dashboard'
    }
})
onMounted(() => {
    dashboardSegment.value = localStorage.getItem('dashboardSegment') || 'dashboard'
})

const handleDashboardSegmentChange = (segment) => {
    dashboardSegment.value = segment
    localStorage.setItem('dashboardSegment', segment)
}
</script>