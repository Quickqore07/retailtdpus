<template>
  <div class="relative z-[1030]">
    <!-- Mobile Overlay -->
    <div
      v-if="isMobileOpen"
      class="fixed inset-0 bg-black/50 z-[1029] animate-fade-in"
      @click="closeMobileSidebar"
    ></div>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed top-0 left-0 h-screen bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 flex flex-col z-[1030] overflow-hidden transition-all duration-300 ease-in-out',
        isCollapsed && !isMobile ? 'w-20' : 'w-[280px]',
        isMobile ? '-translate-x-full' : '',
        isMobileOpen ? 'translate-x-0' : '',
      ]"
    >
      <!-- Logo Section -->
      <div
        class="flex items-center justify-between px-4 py-1 border-b border-gray-100 dark:border-gray-800 min-h-[64px]"
        :class="isCollapsed ? 'justify-center' : ''"
      >
        <router-link
          to="/"
          class="flex items-center gap-3 no-underline overflow-hidden"
        >
          <div class="w-10 h-10 flex-shrink-0 text-primary" v-if="!isCollapsed">
            <img
              src="/logo.webp"
              alt="TDPUS"
              class="w-full h-full object-contain"
            />
            <!-- <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                            <rect width="32" height="32" rx="8" fill="currentColor"/>
                            <path d="M8 10h4v12H8V10zm6 4h4v8h-4v-8zm6-2h4v10h-4V12z" fill="white"/>
                        </svg> -->
          </div>
          <transition name="fade">
            <span
              v-show="!isCollapsed || isMobile"
              class="text-xl font-bold text-gray-900 dark:text-white whitespace-nowrap"
            >
              TDPUS
            </span>
          </transition>
        </router-link>

        <!-- Collapse Toggle (Desktop) -->
        <button
          v-if="!isMobile"
          class="flex items-center justify-center w-8 h-8 border-none bg-transparent rounded-md text-gray-500 cursor-pointer transition-all duration-150 flex-shrink-0 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-300"
          @click="toggleCollapse"
          :title="isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        >
          <SvgIcon
            :name="isCollapsed ? 'more-horizontal' : 'chevron-left'"
            size="sm"
          />
        </button>

        <!-- Close Button (Mobile) -->
        <button
          v-if="isMobile && isMobileOpen"
          class="flex items-center justify-center w-8 h-8 border-none bg-transparent rounded-md text-gray-500 cursor-pointer transition-all duration-150 flex-shrink-0 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-300"
          @click="closeMobileSidebar"
        >
          <SvgIcon name="x" size="md" />
        </button>
      </div>

      <!-- Navigation -->
      <nav
        class="flex-1 flex flex-col justify-between overflow-y-auto overflow-x-hidden py-4 scrollbar-thin"
      >
        <div>
          <!-- Menu Section -->
          <div class="mb-6" v-if="filteredMenuItems.length > 0">
            <transition name="fade">
              <span
                v-show="!isCollapsed || isMobile"
                class="block px-4 pb-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider whitespace-nowrap"
              >
                MENU
              </span>
            </transition>
            <ul class="list-none !p-0 m-0">
              <SidebarMenuItem
                v-for="item in filteredMenuItems"
                :key="item.id"
                :item="item"
                :is-collapsed="isCollapsed && !isMobile"
                :active-path="activePath"
                :badge-count="item.id === 'charge-back' ? chargebackUnreadCount : 0"
                @navigate="handleNavigation"
              />
            </ul>
          </div>

          <!-- Others Section -->
          <div class="mb-6" v-if="filteredOtherItems?.length">
            <transition name="fade">
              <span
                v-show="!isCollapsed || isMobile"
                class="block px-4 pb-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider whitespace-nowrap"
              >
                OTHERS
              </span>
            </transition>

            <ul class="list-none !p-0 m-0">
              <SidebarMenuItem
                v-for="item in filteredOtherItems"
                :key="item.id"
                :item="item"
                :is-collapsed="isCollapsed && !isMobile"
                :active-path="activePath"
                @navigate="handleNavigation"
              />
            </ul>
          </div>
        </div>
        <div>v1.1.1</div>
      </nav>
    </aside>

    <!-- Mobile Toggle Button -->
    <button
      v-if="isMobile && !isMobileOpen"
      class="fixed top-2 left-4 w-11 h-11 flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 cursor-pointer shadow-md z-[1028] transition-all duration-150 hover:bg-gray-50 dark:hover:bg-gray-700 lg:hidden"
      @click="openMobileSidebar"
    >
      <SvgIcon name="menu" size="lg" />
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import SidebarMenuItem from "./SidebarMenuItem.vue";
import sidebarMenuData from "@/data/sidebar-menu.json";
import { usePermission } from "@/composables/usePermission";
import { useChargebackNotificationCount } from "@/composables/useChargebackNotificationCount";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();

// Props & Emits
const emit = defineEmits(["collapse-change"]);

// Router
const route = useRoute();
const router = useRouter();

// Permission
const { canAccess } = usePermission();
const { unreadCount: chargebackUnreadCount, fetchUnreadCount: fetchChargebackUnreadCount } =
  useChargebackNotificationCount();

// State
const isCollapsed = ref(false);
const isMobileOpen = ref(false);
const windowWidth = ref(
  typeof window !== "undefined" ? window.innerWidth : 1024
);

// Computed
const isMobile = computed(() => windowWidth.value < 1024);
const activePath = computed(() => route.path);

// Menu Data (imported from JSON)
const menuItems = sidebarMenuData.menuItems || [];
const otherItems = sidebarMenuData.otherItems || [];

const isDC = computed(() => authStore.isDC || false);
const fundRequirementAccess = computed(
  () => authStore.user?.fundRequirementAccess || false
);



const removePayrollReports = (items) => {
  if (!isDC.value) {
    return items.map((item) => {
      if (
        item.id === "settings" &&
        item.children?.length &&
        !fundRequirementAccess.value
      ) {
        return {
          ...item,
          children: item.children.filter(
            (child) => child.id !== "fund-requirement"
          ),
        };
      }
      if (
        (item.id !== "data-entry" &&
          item.id !== "reports" &&
          item.id !== "onboarding") ||
        !item.children?.length
      ) {
        return item;
      }

      if (item.id === "onboarding") {
        return {
          ...item,
          children: item.children.filter((child) => !child.dcOnly),
        };
      }

      if (item.id === "reports") {
        return {
          ...item,
          children: item.children.map((child) => {
            if (child.id !== "payroll-reports" || !child.children?.length) {
              return child;
            }

            return {
              ...child,
              children: child.children.filter(
                (report) => report.id !== "payroll-journal-report"
              ),
            };
          }),
        };
      }

      return {
        ...item,
        children: item.children.filter(
          (child) => child.id !== "payroll-journal"
        ),
      };
    });
  }

  return items.map((item) => {
    if (
      item.id === "settings" &&
      item.children?.length &&
      !fundRequirementAccess.value
    ) {
      return {
        ...item,
        children: item.children.filter(
          (child) => child.id !== "fund-requirement"
        ),
      };
    }
    if (item.id !== "reports" || !item.children?.length) {
      return item;
    }

    return {
      ...item,
      children: item.children.map((child) => {
        if (
          (child.id !== "payroll-reports" && child.id !== "hr-reports") ||
          !child.children?.length
        ) {
          return child;
        }

        return {
          ...child,
          children: child.children.filter(
            (report) =>
              report.id !== "network-check-report" &&
              report.id !== "network-instant-report" &&
              report.id !== "paychex-report" &&
              report.id !== "network-payroll-review-report"
          ),
        };
      }),
    };
  });
};
/**
 * Filter menu items based on user permissions
 * Recursively filters children as well
 */
const filterMenuByPermission = (items) => {
  return items.filter((item) => {
    // If item has children, filter them recursively
    if (item.children && item.children.length > 0) {
      item.children = filterMenuByPermission(item.children);
      // Show parent if it has any visible children
      return item.children.length > 0;
    }

    // For items without children, check permission based on id
    // The menu item id should match the resource name (e.g., 'users' -> 'user', 'roles' -> 'role')
    return canAccess(item.id);
  });
};

// Filtered menu items based on permissions
const filteredMenuItems = computed(() => {
  const clonedMenuItems = JSON.parse(JSON.stringify(menuItems));
  const dcAdjustedMenuItems = removePayrollReports(clonedMenuItems);
  return filterMenuByPermission(dcAdjustedMenuItems);
});

const filteredOtherItems = computed(() => {
  return filterMenuByPermission(JSON.parse(JSON.stringify(otherItems)));
});

// Methods
const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value;
  emit("collapse-change", isCollapsed.value);
  localStorage.setItem("sidebar-collapsed", String(isCollapsed.value));
};

const openMobileSidebar = () => {
  isMobileOpen.value = true;
  document.body.style.overflow = "hidden";
};

const closeMobileSidebar = () => {
  isMobileOpen.value = false;
  document.body.style.overflow = "";
};

const handleNavigation = (path) => {
  router.push(path);
  if (isMobile.value) {
    closeMobileSidebar();
  }
};

const handleResize = () => {
  windowWidth.value = window.innerWidth;
  if (!isMobile.value && isMobileOpen.value) {
    closeMobileSidebar();
  }
};

// Lifecycle
onMounted(() => {
  const savedCollapsed = localStorage.getItem("sidebar-collapsed");
  if (savedCollapsed !== null) {
    isCollapsed.value = savedCollapsed === "true";
  }

  fetchChargebackUnreadCount();
  window.addEventListener("resize", handleResize);
});

onUnmounted(() => {
  window.removeEventListener("resize", handleResize);
  document.body.style.overflow = "";
});

// Watch route changes to close mobile sidebar
watch(
  () => route.path,
  () => {
    if (isMobile.value && isMobileOpen.value) {
      closeMobileSidebar();
    }
  }
);

// Expose methods for parent component access
defineExpose({
  openMobileSidebar,
  closeMobileSidebar,
});
</script>

<style scoped>
/* Custom scrollbar */
.scrollbar-thin::-webkit-scrollbar {
  width: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background-color: rgb(209 213 219);
  border-radius: 9999px;
}

.dark .scrollbar-thin::-webkit-scrollbar-thumb {
  background-color: rgb(75 85 99);
}

/* Animations */
@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.animate-fade-in {
  animation: fade-in 0.2s ease;
}

/* Vue Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.text-primary {
  color: var(--color-primary);
}
</style>
