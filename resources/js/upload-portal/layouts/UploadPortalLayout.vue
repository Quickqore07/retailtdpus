<template>
  <div v-if="userLoading" class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
    <Spinner size="lg" text="Loading..." />
  </div>
  <div v-else class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Classic Header -->
    <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm sticky top-0 z-50 transition-colors duration-200">
      <div class=" mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo Section -->
          <div class="flex items-center gap-3">
            <svg class="w-8 h-8 text-gray-700 dark:text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 21V8l-5-5v5a2 2 0 002 2h3z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h5 class="text-base font-semibold text-gray-900 dark:text-white !mb-0  ">Upload Portal</h5>
          </div>

          <!-- User Actions -->
          <div class="flex items-center gap-6">
            <div class="flex flex-col items-end">
              <span class="text-sm font-medium text-gray-900 dark:text-white !mb-0">{{ user?.name }}</span>
            </div>
            
            <button 
              @click="logout" 
              class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md border border-gray-300 dark:border-gray-600 transition-colors duration-150"
            >
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Logout
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Container -->
    <div class=" mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Navigation -->
        <aside class="lg:col-span-1">
          <nav class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm transition-all duration-200 overflow-hidden">
            <div class="p-3">
              <div class="space-y-1">
                <router-link 
                  to="/upload-portal" 
                  class="group text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-150 flex items-center gap-3 px-3 py-2.5 rounded-lg relative"
                  exact-active-class="!bg-blue-50 dark:!bg-blue-500/10 !text-blue-600 dark:!text-blue-400 hover:!bg-blue-50 dark:hover:!bg-blue-500/10 shadow-sm border-l-2 border-blue-600 dark:border-blue-400 -ml-[1px]"
                >
                  <svg-icon name="home" />
                  <span>Home</span>
                </router-link>
                <router-link 
                  to="/upload-portal/folders" 
                  class="group text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-150 flex items-center gap-3 px-3 py-2.5 rounded-lg relative"
                  :class="[matchFolderPath
                        ? '!bg-blue-50 dark:!bg-blue-500/10 !text-blue-600 dark:!text-blue-400 hover:!bg-blue-50 dark:hover:!bg-blue-500/10 shadow-sm border-l-2 border-blue-600 dark:border-blue-400 -ml-[1px]'
                        : ''
                    ]"
                >
                  <svg-icon name="folder" />
                  <span>My Folders</span>
                </router-link>

                <router-link 
                  to="/upload-portal/documents" 
                  class="group text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-150 flex items-center gap-3 px-3 py-2.5 rounded-lg relative"
                  active-class="!bg-blue-50 dark:!bg-blue-500/10 !text-blue-600 dark:!text-blue-400 hover:!bg-blue-50 dark:hover:!bg-blue-500/10 shadow-sm border-l-2 border-blue-600 dark:border-blue-400 -ml-[1px]"
                >
                  <svg-icon name="files" />
                  <span>Documents</span>
                </router-link>

                <router-link 
                  :to="`/upload-portal/documents/${invoiceFolder?.id}`" 
                  class="group text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-150 flex items-center gap-3 px-3 py-2.5 rounded-lg relative"
                  active-class="!bg-blue-50 dark:!bg-blue-500/10 !text-blue-600 dark:!text-blue-400 hover:!bg-blue-50 dark:hover:!bg-blue-500/10 shadow-sm border-l-2 border-blue-600 dark:border-blue-400 -ml-[1px]"
                >
                  <svg-icon name="files" />
                  <span>AP Invoices</span>
                </router-link>

                <div class="rounded-lg" v-if="can('upload-portal-customer', 'index')">
                  <button
                    type="button"
                    @click="arNavOpen = !arNavOpen"
                    class="w-full text-left group text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-150 flex items-center gap-3 px-3 py-2.5 rounded-lg relative"
                    :class="arInvoiceSectionActive
                      ? '!bg-blue-50 dark:!bg-blue-500/10 !text-blue-600 dark:!text-blue-400 hover:!bg-blue-50 dark:hover:!bg-blue-500/10 shadow-sm border-l-2 border-blue-600 dark:border-blue-400 -ml-[1px]'
                      : ''"
                  >
                    <svg-icon name="files" />
                    <span class="flex-1">AR Invoices</span>
                    <svg
                      class="w-4 h-4 shrink-0 text-gray-400 transition-transform duration-150"
                      :class="{ 'rotate-180': arNavOpen }"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      aria-hidden="true"
                    >
                      <path d="M6 9l6 6 6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </button>
                  <div
                    v-show="arNavOpen"
                    class="mt-0.5 ml-2 pl-3 border-l-2 border-gray-200 dark:border-gray-600 space-y-0.5 py-1"
                  >
                      <template v-for="item in arSubNav" :key="item.id || item.label">
                        <!-- Regular menu item without children -->
                        <router-link
                          v-if="can(item.id, 'index') && !item.children"
                          :to="item.to"
                          class="block text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-150 px-3 py-2 rounded-md"
                          active-class="!text-blue-600 dark:!text-blue-400 !bg-blue-50/80 dark:!bg-blue-500/10"
                        >
                          {{ item.label }}
                        </router-link>

                        <!-- Menu item with children (sub-routes) -->
                        <div v-if="item.children" class="space-y-0.5">
                          <button
                            type="button"
                            @click="toggleSubMenu(item.label)"
                            class="w-full text-left flex items-center justify-between text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-150 px-3 py-2 rounded-md"
                            :class="isSubMenuActive(item) ? '!text-blue-600 dark:!text-blue-400 !bg-blue-50/80 dark:!bg-blue-500/10' : ''"
                          >
                            <span>{{ item.label }}</span>
                            <svg
                              class="w-3 h-3 shrink-0 transition-transform duration-150"
                              :class="{ 'rotate-180': openSubMenus[item.label] }"
                              viewBox="0 0 24 24"
                              fill="none"
                              stroke="currentColor"
                            >
                              <path d="M6 9l6 6 6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                          </button>
                          <div
                            v-show="openSubMenus[item.label]"
                            class="ml-3 pl-3 border-l-2 border-gray-200 dark:border-gray-600 space-y-0.5 py-1"
                          >
                            <router-link
                              v-for="child in item.children"
                              :key="child.id"
                              v-show="can(child.id, child.action || 'index')"
                              :to="child.to"
                              class="block text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-150 px-3 py-2 rounded-md"
                              active-class="!text-blue-600 dark:!text-blue-400 !bg-blue-50/80 dark:!bg-blue-500/10"
                            >
                              {{ child.label }}
                            </router-link>
                          </div>
                        </div>
                      </template>

                  </div>
                </div>
              </div>
            </div>
          </nav>
        </aside>

        <!-- Main Content -->
        <main class="lg:col-span-3">
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 min-h-[600px] transition-colors duration-200">
            <router-view />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import SvgIcon from '@/components/SvgIcon.vue'
import Spinner from '@/components/ui/spinner.vue'
import { useUser } from '../composables/useUser'

export default {
  name: 'UploadPortalLayout',
  components: {
    SvgIcon,
    Spinner,
  },
  setup() {
    const router = useRouter()
    const { user, userLoading, loadUser, invoiceFolder, can } = useUser()

    const userTypeLabel = computed(() => {
      return user.value?.user_type === 'upload' ? 'Upload User' : 'Staff User'
    })

    const userTypeClass = computed(() => {
      return user.value?.user_type === 'upload' 
        ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' 
        : 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'
    })

    const logout = () => {
      window.location.href = '/upload-portal/logout'  
    }

    const matchFolderPath = computed(() => {
      let active = false
      Array.from(['/upload-portal/folders','/upload-portal/documents/']).forEach(path => {
        if(router.currentRoute.value.path.includes(path) && router.currentRoute.value.path !== `/upload-portal/documents/${invoiceFolder?.value?.id}`) {
          active = true
        }
      })
      return active
    })

    const arSubNav = [
      { to: '/upload-portal/ar-invoice/customers', label: 'Customers',id:'upload-portal-customer' },
      { to: '/upload-portal/ar-invoice/invoices', label: 'Invoices',id:'upload-portal-invoice' },
      { to: '/upload-portal/ar-invoice/payments', label: 'Payments',id:'upload-portal-customer-payment' },
      {  label: 'Reports', children: [
          { to: '/upload-portal/ar-invoice/reports/customer-balance', label: 'Customer Balance',id:'upload-portal-ar-customer-balance-report' },
          { to: '/upload-portal/ar-invoice/reports/aging', label: 'Aging Report',id:'upload-portal-ar-aging-report' },
          { to: '/upload-portal/ar-invoice/reports/statement', label: 'Statement', id: 'upload-portal-customer', action: 'view-statement' },
        ] 
      },
      { to: '/upload-portal/ar-settings/email-templates', label: 'Email templates',id:'upload-portal-ar-email-template' },
      { to: '/upload-portal/ar-settings/settings', label: 'Settings',id:'upload-portal-ar-settings' },
    ]

    const arInvoiceSectionActive = computed(() =>
      router.currentRoute.value.path.startsWith('/upload-portal/ar-invoice')
    )

    const arSettingsSectionActive = computed(() =>
      router.currentRoute.value.path.startsWith('/upload-portal/ar-settings')
    )

    const arNavOpen = ref(false)
    const arSettingsNavOpen = ref(false)
    const openSubMenus = ref({})

    const toggleSubMenu = (label) => {
      openSubMenus.value[label] = !openSubMenus.value[label]
    }

    const isSubMenuActive = (item) => {
      if (!item.children) return false
      const currentPath = router.currentRoute.value.path
      return item.children.some(child => currentPath === child.to)
    }

    watch(
      () => router.currentRoute.value.path,
      (path) => {
        if (path.startsWith('/upload-portal/ar-invoice')) {
          arNavOpen.value = true
        }
        if (path.startsWith('/upload-portal/ar-settings')) {
          arSettingsNavOpen.value = true
        }

        // Auto-open sub-menus when navigating to a child route
        arSubNav.forEach(item => {
          if (item.children) {
            const isActive = item.children.some(child => path === child.to)
            if (isActive) {
              openSubMenus.value[item.label] = true
            }
          }
        })
      },
      { immediate: true }
    )

    onMounted(() => {
      loadUser()
    })

    return {
      user,
      userLoading,
      userTypeLabel,
      userTypeClass,
      logout,
      matchFolderPath,
      invoiceFolder,
      arSubNav,
      arInvoiceSectionActive,
      arNavOpen,
      arSettingsSectionActive,
      arSettingsNavOpen,
      openSubMenus,
      toggleSubMenu,
      isSubMenuActive,
      can,
    }
  }
}
</script>

