<template>
    <Teleport to="body">
        <Transition name="auth-loader">
            <div
                v-show="authStore.loading"
                class="auth-loader-overlay fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/60 dark:bg-black/70 backdrop-blur-sm"
                aria-live="polite"
                aria-busy="true"
                role="status"
            >
                <div class="auth-loader-content flex flex-col items-center gap-4 rounded-2xl bg-white dark:bg-gray-800 px-10 py-8 shadow-2xl ring-1 ring-gray-200/50 dark:ring-gray-700/50">
                    <div
                        class="h-12 w-12 animate-spin rounded-full border-[3px] border-gray-200 dark:border-gray-600 border-t-blue-600 dark:border-t-blue-500"
                        aria-hidden="true"
                    />
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Loading...
                    </p>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
</script>

<style scoped>
.auth-loader-overlay {
    min-height: 100vh;
    min-height: 100dvh;
}

.auth-loader-enter-active,
.auth-loader-leave-active {
    transition: opacity 0.2s ease;
}

.auth-loader-enter-from,
.auth-loader-leave-to {
    opacity: 0;
}

.auth-loader-enter-active .auth-loader-content,
.auth-loader-leave-active .auth-loader-content {
    transition: transform 0.2s ease;
}

.auth-loader-enter-from .auth-loader-content,
.auth-loader-leave-to .auth-loader-content {
    transform: scale(0.95);
}
</style>
