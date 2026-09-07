<template>
    <Modal
        v-model="open"
        title="Notifications"
        size="2xl"
        :show-footer="false"
        :close-on-backdrop="true"
        body-class="!pt-2 !pb-4 max-h-[min(70vh,32rem)] flex flex-col min-h-0"
        @close="onClose"
    >
        <div class="flex flex-col gap-3 min-h-0 flex-1">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 shrink-0">
                <div
                    class="inline-flex rounded-lg border border-gray-200 dark:border-gray-600 p-0.5 bg-gray-50 dark:bg-gray-900/50"
                    role="tablist"
                    aria-label="Notification filter"
                >
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="filter === 'unread'"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        :class="
                            filter === 'unread'
                                ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
                        "
                        @click="setFilter('unread')"
                    >
                        Unread
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="filter === 'all'"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        :class="
                            filter === 'all'
                                ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
                        "
                        @click="setFilter('all')"
                    >
                        All
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="loading" class="text-xs text-gray-500 dark:text-gray-400">Loading…</span>
                    <button
                        v-if="!loading && unreadOnPage.length > 0"
                        type="button"
                        class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md text-xs font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700/80 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 transition-colors disabled:opacity-50"
                        :disabled="markingMany"
                        @click="markPageRead"
                    >
                        <SvgIcon
                            v-if="!markingMany"
                            name="check-circle"
                            size="sm"
                            class="shrink-0"
                        />
                        <SvgIcon v-else name="refresh" size="sm" class="shrink-0 animate-spin" />
                        Mark page read
                    </button>
                </div>
            </div>

            <div
                class="overflow-x-auto overflow-y-auto flex-1 min-h-0 rounded-lg border border-gray-100 dark:border-gray-700"
            >
                <p
                    v-if="!loading && items.length === 0"
                    class="px-4 py-12 text-sm text-center text-gray-500 dark:text-gray-400"
                >
                    {{ filter === 'unread' ? 'No unread notifications.' : 'No notifications yet.' }}
                </p>
                <table
                    v-else
                    class="w-full table-fixed border-collapse text-left"
                >
                    <colgroup>
                        <col class="w-[6.5rem] sm:w-[7.5rem]" />
                        <col />
                        <col class="w-[9.5rem] sm:w-[10.5rem]" />
                        <col class="w-11" />
                    </colgroup>
                    <thead
                        class="sticky top-0 z-10 border-b border-gray-200 dark:border-gray-600 bg-gray-50/95 dark:bg-gray-900/95 backdrop-blur-sm"
                    >
                        <tr>
                            <Th
                                scope="col"
                                custom-class="!px-2 sm:!px-3 !py-2 !text-[10px] bg-transparent dark:bg-transparent"
                            >
                                Type
                            </Th>
                            <Th
                                scope="col"
                                custom-class="!px-2 !py-2 !text-[10px] bg-transparent dark:bg-transparent"
                            >
                                Message
                            </Th>
                            <Th
                                scope="col"
                                custom-class="!px-2 !py-2 !text-[10px] bg-transparent dark:bg-transparent"
                            >
                                Date
                            </Th>
                            <Th
                                scope="col"
                                custom-class="!p-0 w-11 bg-transparent dark:bg-transparent"
                            >
                                <span class="sr-only">Actions</span>
                            </Th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr
                            v-for="n in items"
                            :key="n.id"
                            :class="n.read_at ? '' : 'bg-blue-50/50 dark:bg-blue-950/20'"
                        >
                            <Td
                                custom-class="align-top !px-2 sm:!px-3 !py-3 min-w-0 !whitespace-normal"
                            >
                                <span
                                    class="inline-block max-w-full truncate align-middle text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded"
                                    :class="notificationTypeClass(n.type)"
                                    :title="renderNotificationType(n.type)"
                                >
                                    {{ renderNotificationType(n.type) }}
                                </span>
                            </Td>
                            <Td
                                custom-class="align-top !px-2 !py-3 min-w-0 !whitespace-normal break-words"
                            >
                                <button
                                    type="button"
                                    class="w-full min-w-0 text-left text-sm text-gray-900 dark:text-gray-100 break-words rounded-md hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 py-0.5 -my-0.5 px-1 -mx-1"
                                    :class="n.read_at ? 'opacity-90' : ''"
                                    @click="handleRowClick(n)"
                                >
                                    {{ n.content }}
                                </button>
                            </Td>
                            <Td
                                color="secondary"
                                size="xs"
                                custom-class="align-top !px-2 !py-3 tabular-nums sm:!whitespace-nowrap !whitespace-normal break-words"
                            >
                                {{ formatDateTime(n.created_at) }}
                                <span
                                    v-if="n.read_at"
                                    class="block sm:inline sm:ml-1 text-gray-400 dark:text-gray-500"
                                >
                                    · Read
                                </span>
                            </Td>
                            <Td
                                align="center"
                                custom-class="align-middle !px-1 !py-2 !w-11"
                            >
                                <button
                                    v-if="!n.read_at"
                                    type="button"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 dark:text-gray-400 hover:text-primary hover:bg-primary/10 dark:hover:bg-primary/20 transition-colors disabled:opacity-40"
                                    :disabled="markingId === n.id"
                                    aria-label="Mark as read"
                                    @click.stop="markOne(n)"
                                >
                                    <SvgIcon
                                        v-if="markingId !== n.id"
                                        name="check-circle"
                                        size="md"
                                        class="shrink-0"
                                    />
                                    <SvgIcon
                                        v-else
                                        name="refresh"
                                        size="md"
                                        class="shrink-0 animate-spin text-primary"
                                    />
                                </button>
                            </Td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="pagination.total > 0" class="shrink-0 pt-2 border-t border-gray-200 dark:border-gray-700">
                <Pagination
                    :collection="pagination"
                    :loading="loading"
                    :show-jump="true"
                    @page-change="onPageChange"
                />
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import Modal from '@/components/common/Modal.vue'
import Pagination from '@/components/ui/pagination.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import { formatDateTime } from '@/utils/date'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue', 'notifications-changed'])

const open = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
})

const filter = ref('unread')
const loading = ref(false)
const markingId = ref(null)
const markingMany = ref(false)
const items = ref([])
const pagination = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    per_page: 15,
    has_prev: false,
    has_next: false,
})

const unreadOnPage = computed(() => items.value.filter((n) => !n.read_at))

function renderNotificationType(type) {
    if (!type) {
        return 'Info'
    }
    const s = String(type).replaceAll('_', ' ')
    return s.charAt(0).toUpperCase() + s.slice(1)
}

function notificationTypeClass(type) {
    switch (type) {
        case 'info':
            return 'bg-blue-500 text-white'
        case 'warning':
            return 'bg-yellow-500 text-white'
        case 'new_hire':
            return 'bg-emerald-600 text-white'
        case 'overtime_notification':
            return 'bg-amber-600 text-white'
        case 'chargeback_submitted':
            return 'bg-violet-600 text-white'
        case 'sales_receipt_uploaded':
            return 'bg-indigo-600 text-white'
        default:
            return 'bg-gray-500 text-white'
    }
}

async function fetchPage(page = 1, perPage = null) {
    loading.value = true
    const limit = perPage ?? pagination.value.per_page ?? 15
    try {
        const params = {
            page,
            per_page: limit,
            include_read: filter.value === 'all' ? 1 : 0,
        }
        const res = await axios.get('/api/notifications', { params })
        items.value = res.data?.data ?? []
        const pag = res.data?.pagination
        if (pag) {
            pagination.value = {
                current_page: pag.current_page ?? 1,
                last_page: pag.last_page ?? 1,
                from: pag.from ?? 0,
                to: pag.to ?? 0,
                total: pag.total ?? 0,
                per_page: pag.per_page ?? limit,
                has_prev: pag.has_prev ?? false,
                has_next: pag.has_next ?? false,
            }
        }
    } catch (e) {
        console.error('Failed to load notifications', e)
        items.value = []
    } finally {
        loading.value = false
    }
}

function setFilter(next) {
    if (filter.value === next) {
        return
    }
    filter.value = next
    fetchPage(1, pagination.value.per_page)
}

function onPageChange(page, perPage) {
    const prevPerPage = pagination.value.per_page
    if (perPage !== prevPerPage) {
        fetchPage(1, perPage)
    } else {
        fetchPage(page, perPage)
    }
}

async function markOne(n) {
    if (n.read_at || markingId.value === n.id) {
        return
    }
    markingId.value = n.id
    try {
        await axios.patch(`/api/notifications/${n.id}/read`)
        n.read_at = new Date().toISOString()
        emit('notifications-changed')
        if (filter.value === 'unread') {
            await fetchPage(pagination.value.current_page, pagination.value.per_page)
        }
    } catch (e) {
        console.error('Failed to mark notification read', e)
    } finally {
        markingId.value = null
    }
}

async function handleRowClick(n) {
    if (!n.read_at) {
        await markOne(n)
    }
}

async function markPageRead() {
    const ids = unreadOnPage.value.map((n) => n.id)
    if (!ids.length || markingMany.value) {
        return
    }
    markingMany.value = true
    try {
        await axios.patch('/api/notifications/read', { ids })
        for (const n of items.value) {
            if (ids.includes(n.id)) {
                n.read_at = new Date().toISOString()
            }
        }
        emit('notifications-changed')
        await fetchPage(pagination.value.current_page, pagination.value.per_page)
    } catch (e) {
        console.error('Failed to mark notifications read', e)
    } finally {
        markingMany.value = false
    }
}

function onClose() {
    filter.value = 'unread'
}

watch(
    () => props.modelValue,
    (isOpen) => {
        if (isOpen) {
            fetchPage(1, pagination.value.per_page)
        }
    }
)
</script>
