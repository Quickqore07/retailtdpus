<template>
    <Teleport to="body">
        <div class="toast-container">
            <TransitionGroup name="toast" tag="div">
                <div v-for="toast in toasts" :key="toast.id" :class="getToastClasses(toast)" role="alert">
                    <!-- Icon Circle -->
                    <div :class="getIconWrapperClasses(toast)">
                        <SvgIcon v-if="toast.type === 'success'" name="check" size="sm" class="text-white" />
                        <SvgIcon v-else-if="toast.type === 'error'" name="x" size="sm" class="text-white" />
                        <SvgIcon v-else-if="toast.type === 'warning'" name="alert" size="sm" class="text-white" />
                        <SvgIcon v-else-if="toast.type === 'info'" name="info" size="sm" class="text-white" />
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <p v-if="toast.title" class="font-semibold text-gray-900 dark:text-white text-sm">
                            {{ toast.title }}
                        </p>
                        <p class="text-sm text-gray-700 dark:text-gray-200" :class="{ 'mt-0.5': toast.title }">
                            {{ toast.message }}
                        </p>
                    </div>

                    <!-- Close Button -->
                    <button 
                        @click="removeToast(toast.id)" 
                        type="button"
                        class="flex-shrink-0 ml-2 inline-flex items-center justify-center w-6 h-6 rounded-full text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition-all duration-150"
                    >
                        <span class="sr-only">Close</span>
                        <SvgIcon name="x" size="xs" />
                    </button>

                    <!-- Progress Bar (if duration > 0) -->
                    <div v-if="toast.duration > 0" class="toast-progress-bar" :style="{ animationDuration: `${toast.duration}ms` }"></div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { ref } from 'vue'
import SvgIcon from '../SvgIcon.vue'

const toasts = ref([])
let toastId = 0

const addToast = (options) => {
    const id = ++toastId
    const toast = {
        id,
        type: options.type || 'info',
        title: options.title || '',
        message: options.message || options,
        duration: options.duration !== undefined ? options.duration : 5000
    }

    toasts.value.push(toast)

    // Auto remove after duration
    if (toast.duration > 0) {
        setTimeout(() => {
            removeToast(id)
        }, toast.duration)
    }

    return id
}

const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
        toasts.value.splice(index, 1)
    }
}

const getToastClasses = (toast) => {
    const baseClasses = 'toast-item relative flex items-center gap-3 p-4 rounded-xl shadow-xl backdrop-blur-md border bg-white/95 dark:bg-gray-800/95 overflow-hidden'
    return baseClasses
}

const getIconWrapperClasses = (toast) => {
    const baseClasses = 'flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full'
    
    const typeClasses = {
        success: 'bg-green-500 dark:bg-green-600',
        error: 'bg-red-500 dark:bg-red-600',
        warning: 'bg-yellow-500 dark:bg-yellow-600',
        info: 'bg-blue-500 dark:bg-blue-600'
    }

    return `${baseClasses} ${typeClasses[toast.type] || typeClasses.info}`
}

// Expose methods
defineExpose({
    addToast,
    removeToast
})
</script>

<style scoped>
.toast-container {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-width: 24rem;
    width: calc(100% - 2rem);
    pointer-events: none;
}

.toast-item {
    pointer-events: auto;
    max-width: 100%;
    border: 1px solid #e5e7eb;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.toast-item:hover {
    transform: translateX(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Dark mode border */
.dark .toast-item {
    border-color: #374151;
}

/* Progress Bar */
.toast-progress-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    width: 100%;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    transform-origin: left;
    animation: progress linear forwards;
}

@keyframes progress {
    from {
        transform: scaleX(1);
    }
    to {
        transform: scaleX(0);
    }
}

/* Toast animations */
.toast-enter-active {
    animation: toast-in 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.toast-leave-active {
    animation: toast-out 0.3s ease-in-out;
}

@keyframes toast-in {
    0% {
        opacity: 0;
        transform: translateX(100%) scale(0.8);
    }
    50% {
        transform: translateX(-10px) scale(1.02);
    }
    100% {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
}

@keyframes toast-out {
    0% {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translateX(100%) scale(0.8);
    }
}

/* Mobile responsiveness */
@media (max-width: 640px) {
    .toast-container {
        top: 0.5rem;
        right: 0.5rem;
        left: 0.5rem;
        width: auto;
        max-width: none;
    }
}
</style>

