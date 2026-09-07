<template>
    <nav
        :class="[
            'fixed top-0 right-0 z-[1020] h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 transition-all duration-300',
            isSidebarCollapsed && !isMobile ? 'left-20' : 'left-0 lg:left-[280px]',
        ]"
    >
        <div class="h-full px-3 sm:px-4 flex items-center justify-between gap-2 sm:gap-4 min-w-0">
            <!-- Left: Mobile Menu Button + Search -->
            <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                <!-- Mobile Menu Button -->
                <button
                    v-if="isMobile"
                    class="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors lg:hidden shrink-0"
                    @click="emit('toggle-mobile-sidebar')"
                    aria-label="Toggle sidebar"
                >
                    <SvgIcon name="menu" size="lg" />
                </button>

                <!-- Search Bar -->
                <!-- <div class="relative flex-1 min-w-0 max-w-full sm:max-w-xs md:max-w-md hidden sm:block">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <SvgIcon name="search" size="sm" color="var(--color-gray-400)" />
                    </div>
                    <input
                        type="text"
                        v-model="searchQuery"
                        style="outline: none !important;"
                        class="w-full h-10 pl-10 pr-4 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:border-blue-500 !focus:outline-none !focus:ring-0  text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                        placeholder="Search..."
                    />
                </div> -->
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
                <!-- Company Switcher Button -->
                <button
                    class="hidden md:flex items-center gap-1.5 sm:gap-2 h-9 sm:h-10 px-2 sm:px-3 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors border border-gray-200 dark:border-gray-700 shrink-0 min-w-0"
                    @click="openCompanySwitcher"
                    :title="currentCompanyName"
                >
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span class="text-xs sm:text-sm font-medium truncate max-w-[80px] md:max-w-[120px] lg:max-w-[150px]">
                         {{ currentCompanyName }} 
                    </span>

                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                    </svg>
                </button>

                <!-- Search Button (Mobile) -->
                <button
                    class="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors sm:hidden shrink-0"
                    @click="toggleMobileSearch"
                    aria-label="Search"
                >
                    <SvgIcon name="search" size="lg" />
                </button>

                <!-- Notifications -->
                <div class="relative shrink-0">
                    <button
                        ref="notificationsButtonRef"
                        type="button"
                        class="relative flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        :class="showNotifications && 'bg-gray-100 dark:bg-gray-800 ring-1 ring-gray-200 dark:ring-gray-600'"
                        aria-label="Notifications"
                        aria-haspopup="true"
                        :aria-expanded="showNotifications"
                        @click.stop="toggleNotifications"
                    >
                        <SvgIcon name="bell" size="lg" />
                        <span
                            v-if="unreadNotifications > 0"
                            class="absolute top-1 right-1 min-w-[1.125rem] h-[1.125rem] px-0.5 flex items-center justify-center rounded-full bg-primary text-[10px] font-semibold text-white leading-none"
                        >
                            {{ unreadNotifications > 9 ? '9+' : unreadNotifications }}
                        </span>
                    </button>

                    <NavbarNotificationsDropdown
                        :show="showNotifications"
                        :position="notificationsPosition"
                        :loading="loadingNotifications"
                        :notifications="notifications"
                        :unread-count="unreadNotifications"
                        :selected-ids="selectedNotificationIds"
                        :selected-unread-count="selectedUnreadCount"
                        :marking-read="markingNotificationsRead"
                        :marking-one-id="markingNotificationId"
                        @close="closeNotifications"
                        @mark-selected-read="markSelectedNotificationsRead"
                        @mark-all-read="markAllNotificationsRead"
                        @mark-one-read="markOneNotificationRead"
                        @toggle-selected="toggleNotificationSelected"
                        @view-all="openNotificationsModal"
                    />
                </div>

                <NotificationsModal
                    v-model="showNotificationsModal"
                    @notifications-changed="fetchNotifications"
                />

                <!-- Theme Toggle -->
                <button
                    class="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors shrink-0"
                    @click="toggleTheme"
                    :aria-label="isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                >
                    <svg 
                        v-if="isDarkMode" 
                        class="w-5 h-5"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                    <svg 
                        v-else 
                        class="w-5 h-5"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                </button>


                <!-- User Profile Dropdown -->
                <div class="relative shrink-0">
                    <button
                        ref="userMenuButtonRef"
                        class="flex items-center gap-1.5 sm:gap-2 h-9 sm:h-10 px-1.5 sm:px-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        @click.stop="toggleUserMenu"
                        aria-label="User menu"
                    >
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-primary text-white flex items-center justify-center text-xs sm:text-sm font-semibold shrink-0">
                            {{ userInitials }}
                        </div>
                        <SvgIcon name="chevron-down" size="sm" class="hidden sm:block shrink-0" />
                    </button>

                    <!-- User Dropdown Menu - Teleport to body to avoid navbar overflow clipping -->
                    <Teleport to="body">
                        <Transition name="dropdown">
                            <div
                                v-if="showUserMenu"
                                v-click-outside="closeUserMenu"
                                class="fixed z-[1050] w-56 min-w-[14rem] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-hidden"
                                :style="userMenuPosition"
                            >
                            <!-- User Info -->
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ userName }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                    {{ userEmail }}
                                </p>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-2">
                                <router-link
                                    to="/profile"
                                    class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors no-underline"
                                    @click="closeUserMenu"
                                >
                                    <SvgIcon name="user" size="sm" />
                                    <span>Profile</span>
                                </router-link>
                                <router-link
                                    to="/settings"
                                    class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors no-underline"
                                    @click="closeUserMenu"
                                >
                                    <SvgIcon name="settings" size="sm" />
                                    <span>Settings</span>
                                </router-link>
                            </div>

                            <!-- Logout -->
                            <div class="py-2 border-t border-gray-200 dark:border-gray-700">
                                <button
                                    class="flex items-center gap-3 w-full px-4 py-2 text-sm text-danger hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left"
                                    @click="handleLogout"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span>Logout</span>
                                </button>
                            </div>
                        </div>
                        </Transition>
                    </Teleport>
                </div>
            </div>
        </div>

        <!-- Mobile Search Overlay -->
        <!-- <Transition name="fade">
            <div
                v-if="showMobileSearch"
                class="absolute top-full left-0 right-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 p-3 sm:p-4 sm:hidden"
            >
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <SvgIcon name="search" size="sm" color="var(--color-gray-400)" />
                    </div>
                    <input
                        ref="mobileSearchInput"
                        type="text"
                        v-model="searchQuery"
                        class="w-full h-10 pl-10 pr-4 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500"
                        placeholder="Search..."
                        @keydown.esc="closeMobileSearch"
                    />
                </div>
            </div>
        </Transition> -->
    </nav>

    <!-- Company Switcher Modal -->
    <Modal
        v-model="showCompanySwitcher"
        title="Switch Context"
        size="md"
        :show-header="true"
        :show-footer="false"
        :show-close="false"
        @close="closeCompanySwitcher"
    >
        <div class="space-y-4">
            <!-- Workgroup Dropdown -->
            <DynamicDropdown
                label="Workgroup"
                v-model="selectedWorkgroup"
                resource="workgroups"
                display-name="name"
                placeholder="Select a workgroup"
                :required="false"
                @change="handleWorkgroupChange"
                :remove-null-option="true"
            />

            <!-- Company Dropdown -->
            <DynamicDropdown
                label="Company"
                v-model="selectedCompany"
                resource="companies"
                display-name="name"
                placeholder="Select a company"
                :required="false"
                :params="workgroupParams"
                @change="handleCompanyChange"
                :remove-null-option="true"
            />

            <!-- Action Button -->
            <div class="flex justify-end pt-2">
                <Button
                    @click="applySwitchContext"
                    :disabled="!selectedCompany "
                    :loading="switchingCompany"
                    variant="primary"
                    size="md"
                >
                    Switch Company
                </Button>
            </div>
        </div>
    </Modal>
</template>

<script setup >
import { ref, computed, nextTick, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from './ThemeProvider.vue'
// @ts-ignore - SvgIcon is a JS component
import SvgIcon from '../SvgIcon.vue'
import Modal from '../common/Modal.vue'
import NotificationsModal from './NotificationsModal.vue'
import NavbarNotificationsDropdown from './NavbarNotificationsDropdown.vue'
import DynamicDropdown from '../ui/dynamic-dropdown.vue'
import Button from '../ui/button.vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { useChargebackNotificationCount } from '@/composables/useChargebackNotificationCount'

// Props & Emits

const props = defineProps({
    isSidebarCollapsed: false,
    isMobile: false
})

const emit = defineEmits({
    'toggle-mobile-sidebar': []
})

// Router & Stores
const authStore = useAuthStore()
const themeContext = useTheme()
const { isDarkMode, toggleTheme } = themeContext
const { fetchUnreadCount: fetchChargebackUnreadCount } = useChargebackNotificationCount()



// State
const searchQuery = ref('')
const showMobileSearch = ref(false)
const showNotifications = ref(false)
const showNotificationsModal = ref(false)
const showUserMenu = ref(false)
const mobileSearchInput = ref(null)
const notificationsButtonRef = ref(null)
const userMenuButtonRef = ref(null)
const notificationsPosition = ref({ top: '0', right: '0' })
const userMenuPosition = ref({ top: '0', right: '0' })
const showCompanySwitcher = ref(false)
const selectedWorkgroup = ref(null)
const selectedCompany = ref(null)
const switchingCompany = ref(false)
const loadingNotifications = ref(false)
const markingNotificationsRead = ref(false)
const markingNotificationId = ref(null)
const selectedNotificationIds = ref([])

const notifications = ref([])

// Computed
const unreadNotifications = computed(() =>
    notifications.value.filter((n) => !n.read_at).length
)

const selectedUnreadCount = computed(() => {
    const unread = new Set(
        notifications.value.filter((n) => !n.read_at).map((n) => n.id)
    )
    return selectedNotificationIds.value.filter((id) => unread.has(id)).length
})

const userName = computed(() => 
    authStore.user?.username || 'User'
)

const userEmail = computed(() => 
    authStore.user?.email || 'user@example.com'
)

const userInitials = computed(() => {
    const name = userName.value
    const parts = name.split(' ')
    if (parts.length >= 2) {
        return `${parts[0][0]}${parts[1][0]}`.toUpperCase()
    }
    return name.substring(0, 2).toUpperCase()
})

const currentCompanyName = computed(() => 
{
    return authStore.company?.name || 'No Company Selected'
})

const workgroupParams = computed(() => {
    if (selectedWorkgroup.value && selectedWorkgroup.value.id) {
        return { workgroup_id: selectedWorkgroup.value.id }
    }
    return {}
})

// Methods
const toggleMobileSearch = () => {
    showMobileSearch.value = !showMobileSearch.value
    if (showMobileSearch.value) {
        nextTick(() => {
            mobileSearchInput.value?.focus()
        })
    }
}

const closeMobileSearch = () => {
    showMobileSearch.value = false
}

const updateNotificationsPosition = () => {
    const el = notificationsButtonRef.value
    if (el) {
        const rect = el.getBoundingClientRect()
        notificationsPosition.value = {
            top: `${rect.bottom + 8}px`,
            right: `${window.innerWidth - rect.right}px`,
            left: 'auto'
        }
    }
}

const applyNotificationsReadLocally = (ids) => {
    const iso = new Date().toISOString()
    const idSet = new Set(ids)
    for (const n of notifications.value) {
        if (idSet.has(n.id) && !n.read_at) {
            n.read_at = iso
        }
    }
    selectedNotificationIds.value = selectedNotificationIds.value.filter(
        (id) => !idSet.has(id)
    )
    fetchChargebackUnreadCount()
}

const toggleNotificationSelected = (id) => {
    const i = selectedNotificationIds.value.indexOf(id)
    if (i >= 0) {
        selectedNotificationIds.value = selectedNotificationIds.value.filter(
            (x) => x !== id
        )
    } else {
        selectedNotificationIds.value = [...selectedNotificationIds.value, id]
    }
}

const markOneNotificationRead = async (notification) => {
    if (notification.read_at || markingNotificationId.value === notification.id) {
        return
    }
    markingNotificationId.value = notification.id
    try {
        await axios.patch(`/api/notifications/${notification.id}/read`)
        applyNotificationsReadLocally([notification.id])
    } catch (e) {
        console.error('Failed to mark notification read', e)
    } finally {
        markingNotificationId.value = null
    }
}

const markSelectedNotificationsRead = async () => {
    const unreadIds = new Set(
        notifications.value.filter((n) => !n.read_at).map((n) => n.id)
    )
    const ids = selectedNotificationIds.value.filter((id) => unreadIds.has(id))
    if (!ids.length) {
        return
    }
    markingNotificationsRead.value = true
    try {
        await axios.patch('/api/notifications/read', { ids })
        applyNotificationsReadLocally(ids)
    } catch (e) {
        console.error('Failed to mark notifications read', e)
    } finally {
        markingNotificationsRead.value = false
    }
}

const markAllNotificationsRead = async () => {
    const ids = notifications.value.filter((n) => !n.read_at).map((n) => n.id)
    if (!ids.length) {
        return
    }
    markingNotificationsRead.value = true
    try {
        await axios.patch('/api/notifications/read', { ids })
        applyNotificationsReadLocally(ids)
    } catch (e) {
        console.error('Failed to mark notifications read', e)
    } finally {
        markingNotificationsRead.value = false
    }
}

const fetchNotifications = async () => {
    loadingNotifications.value = true
    try {
        const res = await axios.get('/api/notifications')
        notifications.value = res.data?.data ?? []
        selectedNotificationIds.value = selectedNotificationIds.value.filter((id) =>
            notifications.value.some((n) => n.id === id && !n.read_at)
        )
        fetchChargebackUnreadCount()
    } catch (e) {
        console.error('Failed to load notifications', e)
    } finally {
        loadingNotifications.value = false
    }
}

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value
    showUserMenu.value = false
    if (showNotifications.value) {
        nextTick(() => {
            updateNotificationsPosition()
            fetchNotifications()
        })
    }
}

const closeNotifications = () => {
    showNotifications.value = false
    selectedNotificationIds.value = []
}

const openNotificationsModal = () => {
    showNotificationsModal.value = true
    closeNotifications()
}

// const handleNotificationClick = async (notification) => {
//     if (!notification.read_at) {
//         try {
//             await axios.patch(`/api/notifications/${notification.id}/read`)
//             applyNotificationsReadLocally([notification.id])
//         } catch (e) {
//             console.error('Failed to mark notification read', e)
//         }
//     }
//     closeNotifications()
// }

const updateUserMenuPosition = () => {
    const el = userMenuButtonRef.value
    if (el) {
        const rect = el.getBoundingClientRect()
        userMenuPosition.value = {
            top: `${rect.bottom + 8}px`,
            right: `${window.innerWidth - rect.right}px`,
            left: 'auto'
        }
    }
}

const toggleUserMenu = () => {
    showUserMenu.value = !showUserMenu.value
    showNotifications.value = false
    if (showUserMenu.value) {
        nextTick(updateUserMenuPosition)
    }
}

const closeUserMenu = () => {
    showUserMenu.value = false
}

const handleLogout = async () => {
    try {
        // Clear auth store
        authStore.clearUser()
        const res = await axios.get('/logout')
        if (res.status === 200) {
            window.location.reload()
        }
    } catch (error) {
        console.error('Logout failed:', error)
    } finally {
        closeUserMenu()
    }
}

const openCompanySwitcher = async () => {
    showCompanySwitcher.value = true
    // Set current workgroup and company if available
    if (authStore.company) {
        selectedCompany.value = authStore.company
        if (authStore.company.workgroup) {
            selectedWorkgroup.value = authStore.company.workgroup
        }
    }
}

const closeCompanySwitcher = () => {
    showCompanySwitcher.value = false
    // Reset selections
    selectedWorkgroup.value = null
    selectedCompany.value = null
}
const handleWorkgroupChange = (workgroup) => {
    selectedWorkgroup.value = workgroup
    // Reset company selection when workgroup changes
    if (workgroup?.id && selectedCompany.value?.workgroup_id !== workgroup.id) {
        selectedCompany.value = null
    }
}

const handleCompanyChange = (company) => {
    selectedCompany.value = company
}

const router = useRouter()
const applySwitchContext = async () => {
    if (!selectedCompany.value || selectedCompany.value.id === authStore.company?.id) {
        closeCompanySwitcher()

        return
    }

    switchingCompany.value = true
    const result = await authStore.switchCompany(selectedCompany.value.id)
    switchingCompany.value = false
    router.push('/')

    if (result.success) {
        closeCompanySwitcher()
        // Reload the page to refresh all data with new company context
        window.location.reload()
    } else {
        alert(result.message || 'Failed to switch company')
    }
}

const checkCompany = () => {
   setInterval(() => {
    let companyId = localStorage.getItem('company')
    let userId = localStorage.getItem('user')
    if(parseInt(companyId) !== parseInt(authStore.company?.id) && !(authStore.company?.id === undefined || companyId == null)) {
        window.location.reload()
    }
    if(parseInt(userId) !== parseInt(authStore.user?.id) && !(authStore.user?.id === undefined || userId == null)) {
        window.location.reload()
    }
   }, 5000) 
}

watch(showNotifications, (open) => {
    if (open) {
        window.addEventListener('resize', updateNotificationsPosition)
        window.addEventListener('scroll', updateNotificationsPosition, true)
    } else {
        window.removeEventListener('resize', updateNotificationsPosition)
        window.removeEventListener('scroll', updateNotificationsPosition, true)
    }
})

onMounted( async () => {
    await authStore.fetchCurrentCompany()
    if(!authStore.company) {
        openCompanySwitcher()
    }
    checkCompany()
    fetchNotifications()
})


// Click outside directive
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
    }
}
</script>

<style scoped>
/* Dropdown Animation */
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

/* Fade Animation */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Custom styles */
.text-primary {
    color: var(--color-primary);
}

.bg-primary {
    background-color: var(--color-primary);
}

.ring-primary {
    --tw-ring-color: var(--color-primary);
}

.text-danger {
    color: var(--color-danger);
}

.bg-danger {
    background-color: var(--color-danger);
}

/* Scrollbar for notifications */
.max-h-96::-webkit-scrollbar {
    width: 4px;
}

.max-h-96::-webkit-scrollbar-track {
    background: transparent;
}

.max-h-96::-webkit-scrollbar-thumb {
    background-color: rgb(209 213 219);
    border-radius: 9999px;
}

.dark .max-h-96::-webkit-scrollbar-thumb {
    background-color: rgb(75 85 99);
}
</style>

