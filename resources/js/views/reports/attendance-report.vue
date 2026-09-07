<template>
    <div class="attendance-report p-2 bg-gray-50 dark:bg-gray-900">
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Attendance Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View employee check-in, check-out, and break time for the selected date range.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Start Date
                    </label>
                    <input
                        type="date"
                        v-model="filters.startDate"
                        class="form-input w-full"
                        @change="applyFilters"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        End Date
                    </label>
                    <input
                        type="date"
                        v-model="filters.endDate"
                        class="form-input w-full"
                        @change="applyFilters"
                    />
                </div>

                <div>
                    <DynamicDropdown
                        v-model="filters.employee"
                        resource="users"
                        display-name="name"
                        placeholder="All Employees"
                        label="Employee"
                        icon-left="user"
                        :removable="true"
                        @change="applyFilters"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Report Type
                    </label>
                    <select
                        v-model="filters.reportType"
                        class="form-select w-full"
                        @change="applyFilters"
                    >
                        <option value="summary">Summary</option>
                        <option value="detailed">Detailed</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3">
                <Button
                    icon-left="refresh"
                    icon-size="sm"
                    variant="primary"
                    size="sm"
                    @click="applyFilters"
                    :loading="loading"
                >
                    Apply Filters
                </Button>
                <Button
                    icon-left="upload"
                    icon-size="sm"
                    variant="secondary"
                    size="sm"
                    @click="exportReport"
                    :loading="exportLoading"
                    :disabled="rows.length === 0"
                >
                    Export to Excel
                </Button>
            </div>
        </Panel>

        <Panel v-if="rows.length">
            <div class="mb-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2">
                    <p class="m-0 text-xs text-gray-500">Records</p>
                    <p class="m-0 mt-1 font-semibold text-gray-900 dark:text-white">{{ totals.records }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2">
                    <p class="m-0 text-xs text-gray-500">Work Hours</p>
                    <p class="m-0 mt-1 font-semibold text-gray-900 dark:text-white">{{ formatHours(totals.work_hours) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2">
                    <p class="m-0 text-xs text-gray-500">Break Hours</p>
                    <p class="m-0 mt-1 font-semibold text-gray-900 dark:text-white">{{ formatHours(totals.break_hours) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2">
                    <p class="m-0 text-xs text-gray-500">Report Type</p>
                    <p class="m-0 mt-1 font-semibold capitalize text-gray-900 dark:text-white">{{ filters.reportType }}</p>
                </div>
            </div>

            <div class="overflow-x-auto max-h-[calc(100vh-220px)] relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr>
                            <Th>#</Th>
                            <Th>Employee</Th>
                            <!-- <Th>Code</Th> -->
                            <Th>Date</Th>
                            <Th>Check In</Th>
                            <Th>Check Out</Th>
                            <Th>Work Hours</Th>
                            <Th>Break Hours</Th>
                            <Th>Status</Th>
                            <Th v-if="isDetailed">Break Details</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                        <template v-for="(row, index) in rows" :key="row.id">
                            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <Td>{{ index + 1 }}</Td>
                                <Td class="!text-left">{{ row.employee_name || '—' }}</Td>
                                <!-- <Td>{{ row.employee_code || '—' }}</Td> -->
                                <Td>{{ formatDate(row.check_in || row.date, row.timezone) }}</Td>
                                <Td>{{ formatTime(row.check_in, row.timezone) }}</Td>
                                <Td>
                                    <span
                                        v-if="isEditingWorkHours(row) || isEditingBreakOnRow(row)"
                                        class="text-blue-600 dark:text-blue-400"
                                    >
                                        {{ formatTime(previewAttendanceCheckOut(row), row.timezone) || '—' }}
                                    </span>
                                    <span v-else>{{ formatTime(row.check_out, row.timezone) || '—' }}</span>
                                </Td>
                                <Td
                                    class="relative"
                                    :class="canUpdate && row.check_in ? 'cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20' : ''"
                                    :title="canUpdate && row.check_in ? 'Double-click to edit work hours' : ''"
                                    @dblclick.stop="canUpdate && row.check_in ? startEditWorkHours(row) : null"
                                >
                                    <input
                                        v-if="isEditingWorkHours(row)"
                                        v-model="editingWorkHoursValue"
                                        type="number"
                                        min="0"
                                        max="48"
                                        step="0.01"
                                        class="w-full min-w-[80px] rounded border border-blue-500 bg-white px-2 py-1 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-blue-400 dark:bg-gray-700 dark:text-gray-100"
                                        :disabled="savingWorkHoursId === row.id"
                                        @input="onWorkHoursInput(row)"
                                        @keydown.enter.prevent="saveWorkHours(row)"
                                        @keydown.esc.prevent="cancelEditWorkHours"
                                        @blur="saveWorkHours(row)"
                                        ref="workHoursInput"
                                    />
                                    <span v-else>{{ formatHours(row.work_hours) }}</span>
                                </Td>
                                <Td>
                                    <span
                                        v-if="isEditingBreakOnRow(row)"
                                        class="text-blue-600 dark:text-blue-400"
                                    >
                                        {{ formatHours(previewAttendanceBreakHours(row)) }}
                                    </span>
                                    <span v-else>{{ formatHours(row.break_hours) }}</span>
                                </Td>
                                <Td>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass(row.status)">
                                        {{ statusLabel(row.status) }}
                                    </span>
                                </Td>
                                <Td v-if="isDetailed">
                                    <span class="text-xs text-gray-500">
                                        {{ row.breaks?.length || 0 }} break(s)
                                    </span>
                                </Td>
                            </tr>

                            <tr
                                v-for="breakItem in (isDetailed ? row.breaks : [])"
                                :key="`${row.id}-break-${breakItem.index}`"
                                class="border-t border-gray-100 dark:border-gray-700/60 bg-slate-50 dark:bg-gray-900/40"
                            >
                                <Td></Td>
                                <Td class="!text-left" colspan="3">
                                    <span class="pl-4 text-xs text-gray-600 dark:text-gray-300">
                                        Break {{ breakItem.index }}
                                        <span v-if="breakItem.is_open" class="ml-1 text-amber-600">(in progress)</span>
                                    </span>
                                </Td>
                                <Td>{{ formatTime(breakItem.start_time, row.timezone) }}</Td>
                                <Td>
                                    <span
                                        v-if="isEditingBreakHours(row, breakItem)"
                                        class="text-blue-600 dark:text-blue-400"
                                    >
                                        {{ formatTime(previewBreakEndTime(breakItem), row.timezone) || '—' }}
                                    </span>
                                    <span v-else>{{ formatTime(breakItem.end_time, row.timezone) || '—' }}</span>
                                </Td>
                                <Td>—</Td>
                                <Td
                                    class="relative"
                                    :class="canUpdate && breakItem.start_time ? 'cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20' : ''"
                                    :title="canUpdate && breakItem.start_time ? 'Double-click to edit break hours' : ''"
                                    @dblclick.stop="handleBreakHoursDblClick(row, breakItem)"
                                >
                                    <input
                                        v-if="isEditingBreakHours(row, breakItem)"
                                        v-model="editingBreakHoursValue"
                                        type="number"
                                        min="0"
                                        max="12"
                                        step="0.01"
                                        class="w-full min-w-[80px] rounded border border-blue-500 bg-white px-2 py-1 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-blue-400 dark:bg-gray-700 dark:text-gray-100"
                                        :disabled="savingBreakHoursKey === breakEditKey(row, breakItem)"
                                        @input="onBreakHoursInput(row, breakItem)"
                                        @keydown.enter.prevent="saveBreakHours(row, breakItem)"
                                        @keydown.esc.prevent="cancelEditBreakHours"
                                        @blur="saveBreakHours(row, breakItem)"
                                        ref="breakHoursInput"
                                    />
                                    <span v-else>{{ formatHours(breakItem.hours) }}</span>
                                </Td>
                                <Td colspan="2" class="!text-left text-xs text-gray-500">
                                    Break duration
                                </Td>
                            </tr>
                        </template>

                        <tr class="bg-gray-100 dark:bg-gray-700 font-semibold border-t border-gray-300 dark:border-gray-600">
                            <Td weight="bold" :colspan="isDetailed ? 6 : 6">Total</Td>
                            <Td weight="bold">{{ formatHours(totals.work_hours) }}</Td>
                            <Td weight="bold">{{ formatHours(totals.break_hours) }}</Td>
                            <Td weight="bold" :colspan="isDetailed ? 2 : 1"></Td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Panel>

        <Panel v-else>
            <NoData
                icon="files"
                icon-color="blue"
                title="No Attendance Data Found"
                message="No attendance records available for the selected date range."
                size="sm"
                icon-size="lg"
                :show-action="false"
            />
        </Panel>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import NoData from '@/components/ui/no-data.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission'

const message = useMessage()
const { can } = usePermission()
const loading = ref(false)
const exportLoading = ref(false)
const rows = ref([])
const editingWorkHoursRowId = ref(null)
const editingWorkHoursValue = ref('')
const editingWorkHoursOriginal = ref('')
const editingWorkHoursSnapshot = ref(null)
const savingWorkHoursId = ref(null)
const workHoursInput = ref(null)
const editingBreakHoursKey = ref(null)
const editingBreakHoursValue = ref('')
const editingBreakHoursOriginal = ref('')
const editingBreakHoursSnapshot = ref(null)
const savingBreakHoursKey = ref(null)
const breakHoursInput = ref(null)
const totals = ref({
    records: 0,
    work_minutes: 0,
    break_minutes: 0,
    work_hours: 0,
    break_hours: 0,
})

function todayIso() {
    const d = new Date()
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
}

function monthStartIso() {
    const d = new Date()
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    return `${y}-${m}-01`
}

const filters = ref({
    startDate: monthStartIso(),
    endDate: todayIso(),
    reportType: 'summary',
    employee: null,
})

const isDetailed = computed(() => filters.value.reportType === 'detailed')
const canUpdate = computed(() => can('attendance-report', 'update'))

function isEditingWorkHours(row) {
    return editingWorkHoursRowId.value === row.id
}

function previewCheckOut(row) {
    if (!row?.check_in || editingWorkHoursValue.value === '') {
        return row?.check_out || null
    }

    const workMinutes = Math.round(Number(editingWorkHoursValue.value) * 60)
    if (!Number.isFinite(workMinutes) || workMinutes < 0) {
        return row.check_out || null
    }

    const checkIn = new Date(row.check_in).getTime()
    const breakMinutes = Number(row.break_minutes || 0)
    return new Date(checkIn + (workMinutes + breakMinutes) * 60 * 1000).toISOString()
}

function breakEditKey(row, breakItem) {
    return `${row.id}-${breakItem.id}`
}

function isEditingBreakHours(row, breakItem) {
    return editingBreakHoursKey.value === breakEditKey(row, breakItem)
}

function isEditingBreakOnRow(row) {
    return editingBreakHoursKey.value?.startsWith(`${row.id}-`) ?? false
}

function previewBreakEndTime(breakItem) {
    if (!breakItem?.start_time || editingBreakHoursValue.value === '') {
        return breakItem?.end_time || null
    }

    const breakMinutes = Math.round(Number(editingBreakHoursValue.value) * 60)
    if (!Number.isFinite(breakMinutes) || breakMinutes < 0) {
        return breakItem.end_time || null
    }

    const start = new Date(breakItem.start_time).getTime()
    return new Date(start + breakMinutes * 60 * 1000).toISOString()
}

function sumBreakMinutesFromRow(row) {
    return (row.breaks || []).reduce((sum, breakItem) => {
        if (!breakItem.start_time) {
            return sum
        }

        const endTime = isEditingBreakHours(row, breakItem)
            ? previewBreakEndTime(breakItem)
            : breakItem.end_time

        if (!endTime) {
            return sum
        }

        const minutes = Math.max(
            0,
            Math.floor((new Date(endTime).getTime() - new Date(breakItem.start_time).getTime()) / 60000)
        )
        return sum + minutes
    }, 0)
}

function previewAttendanceBreakHours(row) {
    const breakMinutes = sumBreakMinutesFromRow(row)
    return roundHours(breakMinutes / 60)
}

function previewAttendanceCheckOut(row) {
    if (isEditingWorkHours(row)) {
        return previewCheckOut(row)
    }

    if (!row?.check_in || !isEditingBreakOnRow(row)) {
        return row?.check_out || null
    }

    const workMinutes = Number(row.work_minutes || 0)
    const breakMinutes = sumBreakMinutesFromRow(row)

    if (!row.check_out && row.status !== 'checked_out') {
        return row.check_out || null
    }

    const checkIn = new Date(row.check_in).getTime()
    return new Date(checkIn + (workMinutes + breakMinutes) * 60 * 1000).toISOString()
}

function recalculateTotals() {
    const workMinutes = rows.value.reduce((sum, row) => sum + Number(row.work_minutes || 0), 0)
    const breakMinutes = rows.value.reduce((sum, row) => sum + Number(row.break_minutes || 0), 0)

    totals.value = {
        records: rows.value.length,
        work_minutes: workMinutes,
        break_minutes: breakMinutes,
        work_hours: roundHours(workMinutes / 60),
        break_hours: roundHours(breakMinutes / 60),
    }
}

function roundHours(value) {
    return Math.round(Number(value || 0) * 100) / 100
}

function startEditWorkHours(row) {
    if (row.status === 'on_break') {
        message.error('End the active break before updating work hours.')
        return
    }

    editingWorkHoursRowId.value = row.id
    editingWorkHoursValue.value = formatHours(row.work_hours)
    editingWorkHoursOriginal.value = formatHours(row.work_hours)
    editingWorkHoursSnapshot.value = {
        work_hours: row.work_hours,
        work_minutes: row.work_minutes,
        check_out: row.check_out,
        status: row.status,
    }

    nextTick(() => {
        workHoursInput.value?.focus()
        workHoursInput.value?.select()
    })
}

function onWorkHoursInput(row) {
    const preview = previewCheckOut(row)
    if (preview) {
        row.check_out = preview
        row.work_hours = roundHours(editingWorkHoursValue.value)
        row.work_minutes = Math.round(Number(editingWorkHoursValue.value || 0) * 60)
    }
}

function cancelEditWorkHours() {
    const row = rows.value.find((item) => item.id === editingWorkHoursRowId.value)
    if (row && editingWorkHoursSnapshot.value) {
        Object.assign(row, editingWorkHoursSnapshot.value)
    }

    editingWorkHoursRowId.value = null
    editingWorkHoursValue.value = ''
    editingWorkHoursOriginal.value = ''
    editingWorkHoursSnapshot.value = null
}

async function saveWorkHours(row) {
    if (!isEditingWorkHours(row) || savingWorkHoursId.value === row.id) {
        return
    }

    const nextValue = formatHours(editingWorkHoursValue.value)
    if (nextValue === editingWorkHoursOriginal.value) {
        cancelEditWorkHours()
        return
    }

    if (!Number.isFinite(Number(nextValue)) || Number(nextValue) < 0) {
        message.error('Enter a valid number of work hours.')
        return
    }

    savingWorkHoursId.value = row.id

    try {
        const response = await useRequest('post', `/reports/attendance/${row.id}/work-hours`, {
            work_hours: Number(nextValue),
        })

        const updatedRow = response.row
        const index = rows.value.findIndex((item) => item.id === row.id)
        if (index !== -1 && updatedRow) {
            rows.value[index] = {
                ...rows.value[index],
                ...updatedRow,
                breaks: updatedRow.breaks ?? rows.value[index].breaks,
            }
        }

        recalculateTotals()
        message.success(response.message || 'Work hours updated successfully.')
        editingWorkHoursRowId.value = null
        editingWorkHoursValue.value = ''
        editingWorkHoursOriginal.value = ''
        editingWorkHoursSnapshot.value = null
    } catch (error) {
        message.error(error?.response?.data?.message || 'Failed to update work hours.')
        await applyFilters()
    } finally {
        savingWorkHoursId.value = null
    }
}

function startEditBreakHours(row, breakItem) {
    editingBreakHoursKey.value = breakEditKey(row, breakItem)
    editingBreakHoursValue.value = formatHours(breakItem.hours)
    editingBreakHoursOriginal.value = formatHours(breakItem.hours)
    editingBreakHoursSnapshot.value = {
        row: {
            break_hours: row.break_hours,
            break_minutes: row.break_minutes,
            check_out: row.check_out,
            status: row.status,
        },
        breakItem: {
            hours: breakItem.hours,
            minutes: breakItem.minutes,
            end_time: breakItem.end_time,
            is_open: breakItem.is_open,
        },
    }

    nextTick(() => {
        breakHoursInput.value?.focus()
        breakHoursInput.value?.select()
    })
}

function handleBreakHoursDblClick(row, breakItem) {
    if (!canUpdate.value || !breakItem.start_time) {
        return
    }

    startEditBreakHours(row, breakItem)
}

function onBreakHoursInput(row, breakItem) {
    const previewEnd = previewBreakEndTime(breakItem)
    if (previewEnd) {
        breakItem.end_time = previewEnd
        breakItem.hours = roundHours(editingBreakHoursValue.value)
        breakItem.minutes = Math.round(Number(editingBreakHoursValue.value || 0) * 60)
    }

    const breakMinutes = sumBreakMinutesFromRow(row)
    row.break_minutes = breakMinutes
    row.break_hours = roundHours(breakMinutes / 60)

    const previewCheckOut = previewAttendanceCheckOut(row)
    if (previewCheckOut) {
        row.check_out = previewCheckOut
    }
}

function cancelEditBreakHours() {
    const key = editingBreakHoursKey.value
    if (!key) {
        return
    }

    const separatorIndex = key.indexOf('-')
    const rowId = key.slice(0, separatorIndex)
    const breakId = key.slice(separatorIndex + 1)
    const row = rows.value.find((item) => String(item.id) === rowId)
    const breakItem = row?.breaks?.find((item) => String(item.id) === breakId)

    if (row && editingBreakHoursSnapshot.value?.row) {
        Object.assign(row, editingBreakHoursSnapshot.value.row)
    }

    if (breakItem && editingBreakHoursSnapshot.value?.breakItem) {
        Object.assign(breakItem, editingBreakHoursSnapshot.value.breakItem)
    }

    editingBreakHoursKey.value = null
    editingBreakHoursValue.value = ''
    editingBreakHoursOriginal.value = ''
    editingBreakHoursSnapshot.value = null
}

async function saveBreakHours(row, breakItem) {
    const key = breakEditKey(row, breakItem)
    if (!isEditingBreakHours(row, breakItem) || savingBreakHoursKey.value === key) {
        return
    }

    const nextValue = formatHours(editingBreakHoursValue.value)
    if (nextValue === editingBreakHoursOriginal.value) {
        cancelEditBreakHours()
        return
    }

    if (!Number.isFinite(Number(nextValue)) || Number(nextValue) < 0) {
        message.error('Enter a valid number of break hours.')
        return
    }

    savingBreakHoursKey.value = key

    try {
        const response = await useRequest('post', `/reports/attendance/breaks/${breakItem.id}/break-hours`, {
            break_hours: Number(nextValue),
        })

        const updatedRow = response.row
        const index = rows.value.findIndex((item) => item.id === row.id)
        if (index !== -1 && updatedRow) {
            rows.value[index] = updatedRow
        }

        recalculateTotals()
        message.success(response.message || 'Break hours updated successfully.')
        editingBreakHoursKey.value = null
        editingBreakHoursValue.value = ''
        editingBreakHoursOriginal.value = ''
        editingBreakHoursSnapshot.value = null
    } catch (error) {
        message.error(error?.response?.data?.message || 'Failed to update break hours.')
        await applyFilters()
    } finally {
        savingBreakHoursKey.value = null
    }
}

// Fallback when office timezone is missing.
const DEFAULT_ATTENDANCE_TIMEZONE = 'America/New_York'

function formatDate(value, timezone = DEFAULT_ATTENDANCE_TIMEZONE) {
    if (!value) return '—'
    const date = String(value).includes('T') ? new Date(value) : new Date(`${value}T12:00:00Z`)
    return date.toLocaleDateString([], {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        timeZone: timezone || DEFAULT_ATTENDANCE_TIMEZONE,
    })
}

function formatTime(value, timezone = DEFAULT_ATTENDANCE_TIMEZONE) {
    if (!value) return ''
    return new Date(value).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: timezone || DEFAULT_ATTENDANCE_TIMEZONE,
    })
}

function formatHours(value) {
    const hours = Number(value || 0)
    if (!Number.isFinite(hours)) return '0.00'
    return hours.toFixed(2)
}

function statusLabel(status) {
    if (status === 'checked_in') return 'Checked In'
    if (status === 'on_break') return 'On Break'
    if (status === 'checked_out') return 'Checked Out'
    return status || '—'
}

function statusClass(status) {
    if (status === 'checked_in') return 'bg-emerald-100 text-emerald-800'
    if (status === 'on_break') return 'bg-amber-100 text-amber-800'
    if (status === 'checked_out') return 'bg-slate-200 text-slate-800'
    return 'bg-gray-100 text-gray-700'
}

const applyFilters = async () => {
    try {
        if (!filters.value.startDate || !filters.value.endDate) {
            message.error('Please select start and end dates.')
            return
        }

        if (filters.value.startDate > filters.value.endDate) {
            message.error('Start date cannot be after end date.')
            return
        }

        loading.value = true
        const payload = {
            start_date: filters.value.startDate,
            end_date: filters.value.endDate,
            report_type: filters.value.reportType,
        }

        if (filters.value.employee?.id) {
            payload.employee_id = filters.value.employee.id
        }

        const response = await useRequest('post', '/reports/attendance', payload)

        rows.value = response.data || []
        totals.value = response.totals || {
            records: rows.value.length,
            work_minutes: 0,
            break_minutes: 0,
            work_hours: 0,
            break_hours: 0,
        }
    } catch (error) {
        message.error(error?.response?.data?.message || 'Failed to load attendance report.')
    } finally {
        loading.value = false
    }
}

const exportReport = async () => {
    try {
        if (!rows.value.length) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        await import('xlsx').then((XLSX) => {
            const exportData = []
            const typeLabel = isDetailed.value ? 'Detailed' : 'Summary'

            exportData.push(['Attendance Report'])
            exportData.push(['Report Type:', typeLabel])
            exportData.push(['Start Date:', filters.value.startDate])
            exportData.push(['End Date:', filters.value.endDate])
            exportData.push(['Employee:', filters.value.employee?.name || 'All Employees'])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([])

            if (isDetailed.value) {
                exportData.push([
                    '#',
                    'Employee',
                    // 'Code',
                    'Date',
                    'Type',
                    'Check In / Break Start',
                    'Check Out / Break End',
                    'Work Hours',
                    'Break Hours',
                    'Status',
                ])

                rows.value.forEach((row, index) => {
                    exportData.push([
                        index + 1,
                        row.employee_name || '',
                        // row.employee_code || '',
                        row.date || '',
                        'Attendance',
                        formatTime(row.check_in, row.timezone),
                        formatTime(row.check_out, row.timezone) || '',
                        formatHours(row.work_hours),
                        formatHours(row.break_hours),
                        statusLabel(row.status),
                    ])

                    ;(row.breaks || []).forEach((breakItem) => {
                        exportData.push([
                            '',
                            '',
                            // '',
                            '',
                            `Break ${breakItem.index}${breakItem.is_open ? ' (in progress)' : ''}`,
                            formatTime(breakItem.start_time, row.timezone),
                            formatTime(breakItem.end_time, row.timezone) || '',
                            '',
                            formatHours(breakItem.hours),
                            'Break',
                        ])
                    })
                })
            } else {
                exportData.push([
                    '#',
                    'Employee',
                    // 'Code',
                    'Date',
                    'Check In',
                    'Check Out',
                    'Work Hours',
                    'Break Hours',
                    'Status',
                ])

                rows.value.forEach((row, index) => {
                    exportData.push([
                        index + 1,
                        row.employee_name || '',
                        // row.employee_code || '',
                        row.date || '',
                        formatTime(row.check_in, row.timezone),
                        formatTime(row.check_out, row.timezone) || '',
                        formatHours(row.work_hours),
                        formatHours(row.break_hours),
                        statusLabel(row.status),
                    ])
                })
            }

            exportData.push([])
            exportData.push([
                'Total',
                '',
                '',
                // '',
                '',
                '',
                formatHours(totals.value.work_hours),
                formatHours(totals.value.break_hours),
                `${totals.value.records} record(s)`,
            ])

            const ws = XLSX.utils.aoa_to_sheet(exportData)
            ws['!cols'] = isDetailed.value
                ? [
                    { wch: 6 },
                    { wch: 24 },
                    { wch: 10 },
                    { wch: 12 },
                    { wch: 22 },
                    { wch: 18 },
                    { wch: 18 },
                    { wch: 12 },
                    { wch: 12 },
                    { wch: 14 },
                ]
                : [
                    { wch: 6 },
                    { wch: 24 },
                    { wch: 10 },
                    { wch: 12 },
                    { wch: 12 },
                    { wch: 12 },
                    { wch: 12 },
                    { wch: 12 },
                    { wch: 14 },
                ]

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Attendance Report')
            const fileName = `Attendance_Report_${typeLabel}_${filters.value.startDate}_${filters.value.endDate}_${Date.now()}.xlsx`
            XLSX.writeFile(wb, fileName)
            message.success('Report exported successfully!')
        }).catch((error) => {
            console.error('Failed to load XLSX library:', error)
            message.error('Failed to export report. Please try again.')
        })
    } catch (error) {
        console.error('Export error:', error)
        message.error('An error occurred while exporting the report')
    } finally {
        exportLoading.value = false
    }
}

onMounted(() => {
    applyFilters()
})
</script>
