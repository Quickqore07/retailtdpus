<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="modelValue"
                class="fixed inset-0 z-[1100] overflow-y-auto"
                @click.self="handleBackdropClick"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black/50 dark:bg-black/70 transition-opacity"
                    @click="handleBackdropClick"
                ></div>

                <!-- Modal Container -->
                <div class="flex min-h-screen items-center justify-center p-4">
                    <!-- Modal Content -->
                    <div
                        :class="[
                            'relative bg-white dark:bg-gray-800 rounded-lg shadow-2xl transform transition-all w-full',
                            sizeClasses,
                            contentClass
                        ]"
                        @click.stop
                    >
                        <!-- Header -->
                        <div
                            v-if="showHeader"
                            class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"
                        >
                            <h3
                                class="!text-lg font-semibold text-gray-900 dark:text-gray-100 !mb-0"
                            >
                                <slot name="header">{{ title }}</slot>
                            </h3>
                            <button
                                v-if="showClose"
                                type="button"
                                class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                @click="close"
                                aria-label="Close modal"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div
                            :class="[
                                'px-6 py-4',
                                bodyClass
                            ]"
                        >
                            <slot></slot>
                        </div>

                        <!-- Footer -->
                        <div
                            v-if="showFooter"
                            :class="[
                                'flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700',
                                footerClass
                            ]"
                        >
                            <slot name="footer">
                                <Button
                                    v-if="showCancel"
                                    variant="outline"
                                    @click="close"
                                    :loading="loading"
                                >
                                    {{ cancelText }}
                                </Button>
                                <Button
                                    v-if="showConfirm"
                                    variant="primary"
                                    @click="confirm"
                                    :loading="loading"
                                >
                                    {{ confirmText }}
                                </Button>
                            </slot>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, watch } from 'vue'
import Button from '../ui/button.vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: ''
    },
    size: {
        type: String,
        default: 'md',
    },
    showHeader: {
        type: Boolean,
        default: true
    },
    showFooter: {
        type: Boolean,
        default: false
    },
    showClose: {
        type: Boolean,
        default: true
    },
    showCancel: {
        type: Boolean,
        default: false
    },
    showConfirm: {
        type: Boolean,
        default: false
    },
    cancelText: {
        type: String,
        default: 'Cancel'
    },
    confirmText: {
        type: String,
        default: 'Confirm'
    },
    confirmButtonClass: {
        type: String,
        default: 'bg-primary hover:bg-primary-dark focus:ring-primary'
    },
    closeOnBackdrop: {
        type: Boolean,
        default: false
    },
    contentClass: {
        type: String,
        default: ''
    },
    bodyClass: {
        type: String,
        default: ''
    },
    footerClass: {
        type: String,
        default: ''
    },
    loading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'close', 'confirm'])

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'max-w-xs',
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
        '3xl': 'max-w-3xl',
        '4xl': 'max-w-4xl',
        '5xl': 'max-w-5xl',
        '6xl': 'max-w-6xl',
        '7xl': 'max-w-7xl',
        full: 'max-w-full mx-4'
    }
    return sizes[props.size] || sizes.md
})

const close = () => {
    emit('update:modelValue', false)
    emit('close')
}

const confirm = () => {
    emit('confirm')
}

const handleBackdropClick = () => {
    if (props.closeOnBackdrop) {
        close()
    }
}

// Prevent body scroll when modal is open
watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = ''
    }
})
</script>

<style scoped>
.bg-primary {
    background-color: var(--color-primary);
}

.bg-primary-dark {
    background-color: var(--color-primary-dark);
}

.ring-primary {
    --tw-ring-color: var(--color-primary);
}

.focus\:ring-primary:focus {
    --tw-ring-color: var(--color-primary);
}

/* Modal Animation */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.25s ease;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: all 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95) translateY(-20px);
    opacity: 0;
}

.modal-enter-to .relative,
.modal-leave-from .relative {
    transform: scale(1) translateY(0);
    opacity: 1;
}
</style>

