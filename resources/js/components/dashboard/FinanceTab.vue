<template>
    <div>
        <!-- Fund Required Section -->
        <div class="mb-6 sm:mb-8">
            <FundRequirementTable 
                label="Day wise Fund Requirements" 
                :data="fundRequirementData" 
                icon="dollar"
                icon-color="green"
                :labels="labels"
                :loading="fundRequiredLoading"
                :can-reorder="true"
                @order-changed="handleOrderChanged"
            />
        </div>
        <div>
            <CompanyWiseFundReport />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import FundRequirementTable from '@/components/ui/fund-requirement-table.vue'
import CompanyWiseFundReport from '@/views/reports/fund/company-wise-fund-report.vue'

const message = useMessage()
const fundRequirementData = ref([])
const labels = ref([])
const fundRequiredLoading = ref(false)

async function loadFundRequirement() {
    fundRequiredLoading.value = true
    try {
        const data = await useRequest('get', 'dashboard/fund-requirement')
        fundRequirementData.value = data?.data ?? []
        labels.value = data?.labels ?? []
    } catch (err) {
        console.error('Failed to load fund requirement:', err)
    } finally {
        fundRequiredLoading.value = false
    }
}

async function handleOrderChanged(orderedKeys) {
    try {
        // Save the new order to localStorage for this user
        localStorage.setItem('fundRequirementCategoryOrder', JSON.stringify(orderedKeys))
        
        // Filter out fixed categories (food, adv, royalty)
        const fixedCategories = ['food_purchase', 'royalty_payment', 'advertisement_payment']
        const dynamicKeys = orderedKeys.filter(key => !fixedCategories.includes(key))
        
        // Save to backend
        await useRequest('post', 'settings/fund-requirements/update-order', { order: dynamicKeys })
        message.success('Category order saved successfully')
    } catch (err) {
        console.error('Failed to save order:', err)
        message.error('Failed to save category order')
    }
}

onMounted(() => {
    let companyId = localStorage.getItem('company')
    if (companyId) {
        loadFundRequirement()
    }
})
</script>
