<template>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ label }}</span>
            <div class="flex items-center gap-2">
                <Button
                    v-if="isDragging && canReorder"
                    @click="saveOrder"
                    icon-left="save"
                    icon-size="sm"
                    variant="primary"
                    size="sm"
                    title="Save Order"
                >
                    Save Order
                </Button>
                <Button
                    @click="exportToExcel"
                    icon-left="download"
                    icon-size="sm"
                    variant="secondary"
                    size="sm"
                    title="Export to Excel"
                >
                    Export to Excel
                </Button>
                <SvgIcon name="dollar" size="lg" color="green" />
            </div>
        </div>
        
        <div v-if="loading" class="flex justify-center py-8">
            <Spinner size="md" text="Loading..." centered />
        </div>
        
        <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <Th v-if="canReorder" width="40px">Drag</Th>
                        <Th 
                            @click="sortByColumn('category')"
                            class="cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        >
                            <div class="flex items-center gap-1">
                                Category
                                <span class="text-xs" v-if="sortColumn === 'category'">
                                    {{ sortDirection === 'asc' ? '▲' : '▼' }}
                                </span>
                            </div>
                        </Th>
                        <Th 
                            v-for="date in dates" 
                            :key="date.date" 
                            @click="sortByColumn(date.date)"
                            class="text-right py-2 px-3 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        >
                            <div class="flex items-center justify-end gap-1">
                                {{ formatDateHeader(date.date) }} ({{ date.day }})
                                <span class="text-xs" v-if="sortColumn === date.date">
                                    {{ sortDirection === 'asc' ? '▲' : '▼' }}
                                </span>
                            </div>
                        </Th>
                    </tr>
                </thead>
                <tbody ref="tableBody">
                    <tr 
                        v-for="category in sortedCategories" 
                        :key="category.key"
                        :data-key="category.key"
                        @dblclick="handleDblClick(category.key)"
                        :class="[
                            'border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors draggable-row',
                            { 'fixed-category': isFixedCategory(category.key) }
                        ]"
                    >
                        <Td v-if="canReorder" :class="isFixedCategory(category.key) ? 'cursor-not-allowed opacity-50' : 'cursor-move drag-handle'">
                            <SvgIcon name="grip-vertical" size="lg" class="text-gray-400" />
                        </Td>
                        <Td>
                            {{ category.label }}
                        </Td>
                        <Td     
                            v-for="date in dates" 
                            :key="date"
                            class="text-right"
                            :title="renderTooltip(date, category.key)"
                        >
                            {{ formatAmount(getCellValue(date.date, category.key)) }}
                        </Td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 font-semibold">
                        <Td v-if="canReorder"></Td>
                        <Td class="font-semibold">
                            Total
                        </Td>
                        <Td 
                            v-for="date in dates" 
                            :key="date"
                            class="text-right font-semibold"
                        >
                            {{ formatAmount(getTotalForDate(date.date)) }}
                        </Td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, nextTick, watch } from 'vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { formatCurrency } from '@/utils/number'
import Spinner from '@/components/ui/spinner.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import { useRouter } from 'vue-router'
import { formatDate } from '@/utils/date'
import Sortable from 'sortablejs'
import * as XLSX from 'xlsx'
import Button from './button.vue'

const router = useRouter()

const props = defineProps({
    label: {
        type: String,
        required: true
    },
    data: {
        type: Array,
        required: true,
        default: () => []
    },
    labels: {
        type: Array,
        required: true,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    },
    canReorder: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['dblclick', 'order-changed'])

const tableBody = ref(null)
let sortableInstance = null
const isDragging = ref(false)
const localCategories = ref([])
const sortColumn = ref(null)
const sortDirection = ref('asc')

const handleDblClick = (categoryKey) => {
    if(categoryKey == 'royalty_payment' || categoryKey == 'advertisement_payment'){
        router.push('/reports/sales/network-weekly-sales')
    }
}

const isFixedCategory = (categoryKey) => {
    return ['food_purchase', 'royalty_payment', 'advertisement_payment'].includes(categoryKey)
}

// Extract unique dates from data
const dates = computed(() => {
    if (!props.data || props.data.length === 0) return []
    return props.data.map(item => item  ).filter(Boolean)
})

// Define categories to display
const categories = computed(() => 
   [{ key: 'food_purchase', label: 'Food' },
    { key: 'royalty_payment', label: 'Royalty' },
    { key: 'advertisement_payment', label: 'Advt' },
    ...props.labels.map(label => ({ key: label, label: label }))
]
)


// Apply saved order from localStorage
const applySavedOrder = (categories) => {
    if (!props.canReorder) return categories
    
    const savedOrder = localStorage.getItem('fundRequirementCategoryOrder')
    if (!savedOrder) return categories
    try {
        const orderedCategories = categories
        return orderedCategories
    } catch (err) {
        console.error('Failed to parse saved order:', err)
        return categories
    }
}


// Initialize local categories
watch(categories, (newCategories) => {
    localCategories.value = applySavedOrder([...newCategories])
    nextTick(() => {
        initSortable()
    })
}, { immediate: true })

const initSortable = () => {
    if (!props.canReorder || !tableBody.value) return
    
    if (sortableInstance) {
        sortableInstance.destroy()
    }

    sortableInstance = Sortable.create(tableBody.value, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        filter: '.fixed-category',
        preventOnFilter: true,
        onStart: () => {
            isDragging.value = true
        },
        onEnd: (evt) => {
            const { oldIndex, newIndex } = evt
            if (oldIndex !== newIndex) {
                const movedItem = localCategories.value.splice(oldIndex, 1)[0]
                localCategories.value.splice(newIndex, 0, movedItem)
            }
        }
    })
}

const saveOrder = () => {
    const orderedKeys = localCategories.value.map(cat => cat.key)
    emit('order-changed', orderedKeys)
    isDragging.value = false
}

const sortByColumn = (column) => {
    if (sortColumn.value === column) {
        // Toggle direction if same column
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        // New column, default to ascending
        sortColumn.value = column
        sortDirection.value = 'asc'
    }
}

// Computed property for sorted categories
const sortedCategories = computed(() => {
    if (!sortColumn.value) {
        return localCategories.value
    }

    const sorted = [...localCategories.value]
    
    sorted.sort((a, b) => {
        let aValue, bValue
        
        if (sortColumn.value === 'category') {
            // Sort by category label
            aValue = a.label.toLowerCase()
            bValue = b.label.toLowerCase()
            
            if (sortDirection.value === 'asc') {
                return aValue.localeCompare(bValue)
            } else {
                return bValue.localeCompare(aValue)
            }
        } else {
            // Sort by date column value
            aValue = getCellValue(sortColumn.value, a.key)
            bValue = getCellValue(sortColumn.value, b.key)
            
            if (sortDirection.value === 'asc') {
                return aValue - bValue
            } else {
                return bValue - aValue
            }
        }
    })
    
    return sorted
})

onMounted(() => {
    nextTick(() => {
        initSortable()
    })
})


// Get value for specific date and category
const getCellValue = (date, categoryKey) => {
    const item = props.data.find(d => d.date === date)
    return item ? (item[categoryKey] ? parseFloat(item[categoryKey]) : 0) : 0
}

// Get total for a specific date (sum of all categories)
const getTotalForDate = (date) => {
    return categories.value.reduce((sum, category) => {    
        return sum + getCellValue(date, category.key)
    }, 0)
}

// Format date for header (e.g., "4/23")
const formatDateHeader = (dateString) => {
    if (!dateString) return ''

    let date = dateString.includes('T') 
        ? dateString.split('T')[0] 
        : dateString.split(' ')[0]

    const dateObj = new Date(date + 'T00:00:00') // 👈 fix

    return `${dateObj.getMonth() + 1}/${dateObj.getDate()}`
}
// Format amount as currency
const formatAmount = (value) => {
    if (value === null || value === undefined || value === 0) {
        return '0'
    }
    return formatCurrency(parseFloat(value))
}

const renderTooltip = (date, categoryKey) => {
    if(categoryKey == 'food_purchase'){
        if(Array.isArray(date.target_date)){
            return date.target_date.reduce((acc, curr) => acc + `${formatDate(curr.date)}: ${formatCurrency(curr.amount)}\n`, "")
        }
        return formatDate(date.target_date)
        // return Array.isArray(date.target_date) ? date.target_date.map(date => formatDate(date)).join(', ') : formatDate(date.target_date)
    }
    else if(categoryKey == 'royalty_payment' && date.total_sales && date.royalty_percentage && date.royalty_payment){
        return `Royalty Payment: ${formatCurrency(date.total_sales)} (${date.royalty_percentage}%)`
    }
    else if(categoryKey == 'advertisement_payment' && date.total_sales && date.advertisement_percentage && date.advertisement_payment){
        return `Advertisement Payment: ${formatCurrency(date.total_sales)} (${date.advertisement_percentage}%)`
    }
    return ''
}

const exportToExcel = () => {
    // Create the worksheet data
    const data = []
    
    // Add header row with dates
    const headerRow = ['Category']
    dates.value.forEach(date => {
        headerRow.push(`${formatDateHeader(date.date)} (${date.day})`)
    })
    data.push(headerRow)
    
    // Add each category row
    sortedCategories.value.forEach(category => {
        const row = [category.label]
        dates.value.forEach(date => {
            const value = getCellValue(date.date, category.key)
            row.push(value || 0)
        })
        data.push(row)
    })
    
    // Add total row
    const totalRow = ['Total']
    dates.value.forEach(date => {
        totalRow.push(getTotalForDate(date.date))
    })
    data.push(totalRow)
    
    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(data)
    
    // Set column widths
    const colWidths = [{ wch: 20 }]
    dates.value.forEach(() => {
        colWidths.push({ wch: 15 })
    })
    ws['!cols'] = colWidths
    
    // Create workbook
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Fund Requirements')
    
    // Generate filename with current date
    const today = new Date()
    const filename = `${props.label.replace(/\s+/g, '_')}_${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}.xlsx`
    
    // Save file
    XLSX.writeFile(wb, filename)
}
</script>

<style scoped>
table {
    border-collapse: collapse;
}

.drag-handle {
    cursor: move;
    cursor: grab;
    user-select: none;
}

.drag-handle:active {
    cursor: grabbing;
}

.fixed-category {
    background-color: rgba(249, 250, 251, 0.5);
}

.dark .fixed-category {
    background-color: rgba(55, 65, 81, 0.3);
}

.sortable-ghost {
    opacity: 0.4;
    background: #f0f9ff;
}

.sortable-drag {
    opacity: 1;
    cursor: grabbing;
    background: #ffffff;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.draggable-row {
    transition: background-color 0.2s;
}
</style>
