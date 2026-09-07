<template>
    <Teleport to="body">
        <Transition name="dropdown">
            <div
                v-if="show"
                v-click-outside="onClickOutside"
                aria-label="Notifications"
                class="fixed z-[1050] w-[min(calc(100vw-1rem),22rem)] sm:w-96 min-w-[30rem] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-hidden flex flex-col max-h-[min(70vh,24rem)]"
                :style="position"
            >
                <div class="px-3 py-2.5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2 shrink-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                            Notifications
                        </span>
                        <span
                            v-if="!loading && unreadCount > 0"
                            class="shrink-0 text-[10px] font-semibold uppercase tracking-wide tabular-nums px-1.5 py-0.5 rounded-full bg-primary/15 text-primary dark:bg-primary/25 dark:text-primary"
                        >
                            {{ unreadCount }} new
                        </span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <span
                            v-if="loading"
                            class="text-xs text-gray-500 dark:text-gray-400 px-1"
                        >Loading…</span>
                        <template v-else-if="unreadCount > 0">
                            <button
                                v-if="selectedUnreadCount > 0"
                                type="button"
                                class="inline-flex items-center justify-center gap-1 h-8 px-2 sm:px-2.5 rounded-md text-xs font-medium text-primary bg-primary/10 dark:bg-primary/20 hover:bg-primary/15 dark:hover:bg-primary/30 border border-primary/25 dark:border-primary/35 transition-colors disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                                :disabled="markingRead"
                                :aria-label="`Mark ${selectedUnreadCount} selected notification${selectedUnreadCount === 1 ? '' : 's'} as read`"
                                :title="`Mark ${selectedUnreadCount} selected as read`"
                                @click="emit('mark-selected-read')"
                            >
                                <SvgIcon
                                    v-if="!markingRead"
                                    name="check"
                                    size="sm"
                                    class="shrink-0"
                                />
                                <SvgIcon
                                    v-else
                                    name="refresh"
                                    size="sm"
                                    class="shrink-0 animate-spin"
                                />
                                <span class="hidden sm:inline">{{ selectedUnreadCount }}</span>
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-1 h-8 px-2 sm:px-2.5 rounded-md text-xs font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700/80 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 transition-colors disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                                :disabled="markingRead"
                                aria-label="Mark all notifications as read"
                                title="Mark all as read"
                                @click="emit('mark-all-read')"
                            >
                                <SvgIcon
                                    v-if="!markingRead"
                                    name="check-circle"
                                    size="sm"
                                    class="shrink-0"
                                />
                                <SvgIcon
                                    v-else
                                    name="refresh"
                                    size="sm"
                                    class="shrink-0 animate-spin"
                                />
                                <span class="hidden sm:inline">All</span>
                            </button>
                        </template>
                    </div>
                </div>
                <div class="overflow-x-auto overflow-y-auto flex-1 min-h-0">
                    <p
                        v-if="!loading && notifications.length === 0"
                        class="px-3 py-8 text-sm text-center text-gray-500 dark:text-gray-400"
                    >
                        No notifications yet.
                    </p>
                    <table
                        v-else
                        class="w-full table-fixed border-collapse text-left"
                    >
                        <colgroup>
                            <col class="w-8" />
                            <col class="w-[4.5rem]" />
                            <col />
                            <col class="w-[5.25rem]" />
                            <col class="w-9" />
                        </colgroup>
                        <thead
                            class="sticky top-0 z-10 border-b border-gray-200 dark:border-gray-600 bg-white/95 dark:bg-gray-800/95"
                        >
                            <tr>
                                <Th
                                    scope="col"
                                    custom-class="!p-0 w-8 bg-transparent dark:bg-transparent"
                                >
                                    <span class="sr-only">Select</span>
                                </Th>
                                <Th
                                    scope="col"
                                    custom-class="!px-1 !py-1.5 !text-[9px] bg-transparent dark:bg-transparent"
                                >
                                    Type
                                </Th>
                                <Th
                                    scope="col"
                                    custom-class="!px-1 !py-1.5 !text-[9px] bg-transparent dark:bg-transparent"
                                >
                                    Message
                                </Th>
                                <Th
                                    scope="col"
                                    custom-class="!px-1 !py-1.5 !text-[9px] bg-transparent dark:bg-transparent"
                                >
                                    Date
                                </Th>
                                <Th
                                    scope="col"
                                    custom-class="!p-0 w-9 bg-transparent dark:bg-transparent"
                                >
                                    <span class="sr-only">Actions</span>
                                </Th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="n in notifications"
                                :key="n.id"
                                :class="n.read_at ? '' : 'bg-blue-50/50 dark:bg-blue-950/20'"
                            >
                                <Td
                                    align="center"
                                    custom-class="align-middle !px-1 !py-2"
                                >
                                    <label
                                        v-if="!n.read_at"
                                        class="inline-flex cursor-pointer"
                                        @click.stop
                                    >
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300 dark:border-gray-600 text-primary focus:outline-none focus:ring-0 focus:ring-offset-0"
                                            :checked="selectedIds.includes(n.id)"
                                            @change="emit('toggle-selected', n.id)"
                                        />
                                    </label>
                                </Td>
                                <Td
                                    custom-class="align-top !px-1 !py-2 min-w-0 !whitespace-normal"
                                >
                                    <span
                                        class="inline-block max-w-full truncate align-middle text-[9px] font-semibold uppercase tracking-wide px-1 py-0.5 rounded leading-tight"
                                        :class="notificationTypeClass(n.type)"
                                        :title="renderNotificationType(n.type)"
                                    >
                                        {{ renderNotificationType(n.type) }}
                                    </span>
                                </Td>
                                <Td
                                    size="xs"
                                    custom-class="align-top !px-1 !py-2 min-w-0 !whitespace-normal break-words leading-snug"
                                >
                                    <p class="text-xs text-gray-900 dark:text-gray-100 break-words leading-snug">
                                        {{ n.content }}
                                    </p>
                                </Td>
                                <Td
                                    color="secondary"
                                    size="xs"
                                    custom-class="align-top !px-1 !py-2 min-w-0 !whitespace-normal !text-[10px] tabular-nums leading-tight break-words"
                                >
                                    <span class="block leading-tight">
                                        <span class="block">{{ formatNotificationDate(n.created_at) }}</span>
                                        <span class="block">{{ formatNotificationTime(n.created_at) }}</span>
                                    </span>
                                </Td>
                       
                       
                         
                                <Td
                                    align="center"
                                    custom-class="align-middle !px-0.5 !py-1.5"
                                >
                                    <button
                                        v-if="!n.read_at"
                                        type="button"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 dark:text-gray-400 hover:text-primary hover:bg-primary/10 dark:hover:bg-primary/20 dark:hover:text-primary transition-colors disabled:opacity-40 disabled:pointer-events-none focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                                        :disabled="markingOneId === n.id || markingRead"
                                        aria-label="Mark as read"
                                        title="Mark as read"
                                        @click.stop="emit('mark-one-read', n)"
                                    >
                                        <SvgIcon
                                            v-if="markingOneId !== n.id"
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
                <div
                    class="px-3 py-2 border-t border-gray-200 dark:border-gray-700 shrink-0 bg-gray-50/80 dark:bg-gray-900/40"
                >
                    <button
                        type="button"
                        class="w-full text-center text-xs font-semibold text-primary hover:text-primary/90 dark:text-primary py-1.5 rounded-md hover:bg-primary/5 dark:hover:bg-primary/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        @click="emit('view-all')"
                    >
                        View all notifications
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
// @ts-ignore
import SvgIcon from '../SvgIcon.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
const formatNotificationDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}

const formatNotificationTime = (dateString) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    })
}

defineProps({
    show: { type: Boolean, required: true },
    position: { type: Object, required: true },
    loading: { type: Boolean, default: false },
    notifications: { type: Array, default: () => [] },
    unreadCount: { type: Number, default: 0 },
    selectedIds: { type: Array, default: () => [] },
    selectedUnreadCount: { type: Number, default: 0 },
    markingRead: { type: Boolean, default: false },
    markingOneId: { type: [Number, String], default: null },
})

const emit = defineEmits({
    close: [],
    'mark-selected-read': [],
    'mark-all-read': [],
    'mark-one-read': [Object],
    'toggle-selected': [Number],
    'view-all': [],
})

const onClickOutside = () => {
    emit('close')
}

const renderNotificationType = (type) => {
    if (!type) {
        return 'Info'
    }
    const s = type.replaceAll('_', ' ')
    return s.charAt(0).toUpperCase() + s.slice(1)
}

const notificationTypeClass = (type) => {
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

const vClickOutside = {
    mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value()
            }
        }
        document.addEventListener('click', el.clickOutsideEvent)
    },
    unmounted(el) {
        if (el.clickOutsideEvent) {
            document.removeEventListener('click', el.clickOutsideEvent)
        }
    },
}
</script>

<style scoped>
.dropdown-enter-active {
    animation: dropdown-in 0.2s ease;
}

.dropdown-leave-active {
    animation: dropdown-out 0.15s ease;
}

@keyframes dropdown-in {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes dropdown-out {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-8px);
    }
}

.text-primary {
    color: var(--color-primary);
}

.bg-primary {
    background-color: var(--color-primary);
}
</style>
