<template>
    <ThemeProvider>
        <AuthLoader />
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900" v-if="!authStore.loading">
            <!-- Sidebar -->
            <Sidebar 
                ref="sidebarRef"
                @collapse-change="handleSidebarCollapse" 
            />

            <!-- Navbar -->
            <Navbar 
                :is-sidebar-collapsed="isSidebarCollapsed"
                :is-mobile="isMobile"
                @toggle-mobile-sidebar="toggleMobileSidebar"
            />

            <!-- Main Content -->
            <main :class="[
                'min-h-screen p-1 md:p-4 transition-all duration-300',
                isSidebarCollapsed ? 'lg:ml-20' : 'lg:ml-[280px]',
                'ml-0 md:pt-20 pt-16'
            ]">
                <RouterView />
            </main>
        </div>
    </ThemeProvider>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from './stores/auth'
import ThemeProvider from './components/layout/ThemeProvider.vue'
import AuthLoader from './components/layout/AuthLoader.vue'
import Sidebar from './components/layout/sidebar.vue'
import Navbar from './components/layout/navbar.vue'

const authStore = useAuthStore()

const isSidebarCollapsed = ref(false)
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)
const sidebarRef = ref(null)

const isMobile = computed(() => windowWidth.value < 1024)

const handleSidebarCollapse = (collapsed) => {
    isSidebarCollapsed.value = collapsed
}

const toggleMobileSidebar = () => {
    // Call the sidebar's open method
    if (sidebarRef.value?.openMobileSidebar) {
        sidebarRef.value.openMobileSidebar()
    }
}

const handleResize = () => {
    windowWidth.value = window.innerWidth
}

onMounted(async () => {
    const savedCollapsed = localStorage.getItem('sidebar-collapsed')
    if (savedCollapsed !== null) {
        isSidebarCollapsed.value = savedCollapsed === 'true'
    }
    
    // Fetch user data on app initialization (on refresh)
    await authStore.fetchUser()
    
    // Add resize listener
    window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
})
</script>
