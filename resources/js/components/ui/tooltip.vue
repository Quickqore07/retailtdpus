<template>
    <div 
        class="tooltip-wrapper inline-block relative"
        @mouseenter="showTooltip"
        @mouseleave="hideTooltip"
        @focus="showTooltip"
        @blur="hideTooltip"
    >
        <!-- Trigger Element (slot) -->
        <slot></slot>

        <!-- Tooltip Content -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isVisible && (content || $slots.tooltip)"
                    ref="tooltipEl"
                    :class="tooltipClasses"
                    :style="tooltipStyle"
                    role="tooltip"
                    @mouseenter="cancelHide"
                    @mouseleave="hideTooltip"
                >
                    <!-- Tooltip Arrow -->
                    <div :class="arrowClasses" :style="arrowStyle"></div>
                    
                    <!-- Tooltip Content -->
                    <div class="relative z-10">
                        <slot name="tooltip">
                            <span v-html="content"></span>
                        </slot>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, onBeforeUnmount } from 'vue'

const props = defineProps({
    content: {
        type: String,
        default: ''
    },
    placement: {
        type: String,
        default: 'top',
        validator: (value) => ['top', 'bottom', 'left', 'right', 'top-start', 'top-end', 'bottom-start', 'bottom-end', 'left-start', 'left-end', 'right-start', 'right-end'].includes(value)
    },
    theme: {
        type: String,
        default: 'dark',
        validator: (value) => ['dark', 'light', 'primary', 'success', 'warning', 'danger'].includes(value)
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
    },
    delay: {
        type: Number,
        default: 200
    },
    hideDelay: {
        type: Number,
        default: 100
    },
    disabled: {
        type: Boolean,
        default: false
    },
    maxWidth: {
        type: String,
        default: '320px'
    },
    arrow: {
        type: Boolean,
        default: true
    },
    offset: {
        type: Number,
        default: 8
    }
})

const isVisible = ref(false)
const tooltipEl = ref(null)
const triggerEl = ref(null)
const tooltipStyle = ref({})
const arrowStyle = ref({})
let showTimeout = null
let hideTimeout = null

const tooltipClasses = computed(() => {
    const baseClasses = [
        'tooltip-content',
        'absolute',
        'z-50',
        'px-3 py-2',
        'rounded-lg',
        'text-sm',
        'shadow-lg',
        'pointer-events-auto',
        'whitespace-normal',
        'break-words'
    ]

    // Theme classes
    const themeClasses = {
        dark: 'bg-gray-900 text-white dark:bg-gray-800',
        light: 'bg-white text-gray-900 dark:bg-gray-100 dark:text-gray-900 border border-gray-200 dark:border-gray-300',
        primary: 'bg-blue-600 text-white dark:bg-blue-500',
        success: 'bg-green-600 text-white dark:bg-green-500',
        warning: 'bg-yellow-500 text-white dark:bg-yellow-400',
        danger: 'bg-red-600 text-white dark:bg-red-500'
    }

    // Size classes
    const sizeClasses = {
        xs: 'text-xs px-2 py-1',
        sm: 'text-sm px-2.5 py-1.5',
        md: 'text-sm px-3 py-2',
        lg: 'text-base px-4 py-3'
    }

    return [
        ...baseClasses,
        themeClasses[props.theme],
        sizeClasses[props.size]
    ]
})

const arrowClasses = computed(() => {
    const baseClasses = ['tooltip-arrow', 'absolute', 'w-2 h-2', 'rotate-45']
    
    // Theme classes for arrow
    const themeClasses = {
        dark: 'bg-gray-900 dark:bg-gray-800',
        light: 'bg-white dark:bg-gray-100 border-gray-200 dark:border-gray-300',
        primary: 'bg-blue-600 dark:bg-blue-500',
        success: 'bg-green-600 dark:bg-green-500',
        warning: 'bg-yellow-500 dark:bg-yellow-400',
        danger: 'bg-red-600 dark:bg-red-500'
    }

    // Add border for light theme
    if (props.theme === 'light') {
        baseClasses.push('border')
    }

    return [
        ...baseClasses,
        themeClasses[props.theme]
    ]
})

const showTooltip = (event) => {
    if (props.disabled) return
    
    clearTimeout(hideTimeout)
    showTimeout = setTimeout(() => {
        isVisible.value = true
        triggerEl.value = event.currentTarget
        nextTick(() => {
            calculatePosition()
        })
    }, props.delay)
}

const hideTooltip = () => {
    clearTimeout(showTimeout)
    hideTimeout = setTimeout(() => {
        isVisible.value = false
    }, props.hideDelay)
}

const cancelHide = () => {
    clearTimeout(hideTimeout)
}

const calculatePosition = () => {
    if (!tooltipEl.value || !triggerEl.value) return

    const trigger = triggerEl.value.getBoundingClientRect()
    const tooltip = tooltipEl.value.getBoundingClientRect()
    const scrollX = window.scrollX || window.pageXOffset
    const scrollY = window.scrollY || window.pageYOffset

    let top = 0
    let left = 0
    let arrowTop = ''
    let arrowLeft = ''
    let arrowRight = ''
    let arrowBottom = ''

    const offset = props.offset
    const arrowSize = props.arrow ? 8 : 0

    // Calculate position based on placement
    switch (props.placement) {
        case 'top':
            top = trigger.top + scrollY - tooltip.height - offset
            left = trigger.left + scrollX + (trigger.width / 2) - (tooltip.width / 2)
            arrowTop = 'auto'
            arrowBottom = '-4px'
            arrowLeft = '50%'
            arrowRight = 'auto'
            break
        case 'top-start':
            top = trigger.top + scrollY - tooltip.height - offset
            left = trigger.left + scrollX
            arrowBottom = '-4px'
            arrowLeft = '12px'
            break
        case 'top-end':
            top = trigger.top + scrollY - tooltip.height - offset
            left = trigger.right + scrollX - tooltip.width
            arrowBottom = '-4px'
            arrowRight = '12px'
            break
        case 'bottom':
            top = trigger.bottom + scrollY + offset
            left = trigger.left + scrollX + (trigger.width / 2) - (tooltip.width / 2)
            arrowTop = '-4px'
            arrowLeft = '50%'
            break
        case 'bottom-start':
            top = trigger.bottom + scrollY + offset
            left = trigger.left + scrollX
            arrowTop = '-4px'
            arrowLeft = '12px'
            break
        case 'bottom-end':
            top = trigger.bottom + scrollY + offset
            left = trigger.right + scrollX - tooltip.width
            arrowTop = '-4px'
            arrowRight = '12px'
            break
        case 'left':
            top = trigger.top + scrollY + (trigger.height / 2) - (tooltip.height / 2)
            left = trigger.left + scrollX - tooltip.width - offset
            arrowTop = '50%'
            arrowRight = '-4px'
            break
        case 'left-start':
            top = trigger.top + scrollY
            left = trigger.left + scrollX - tooltip.width - offset
            arrowTop = '12px'
            arrowRight = '-4px'
            break
        case 'left-end':
            top = trigger.bottom + scrollY - tooltip.height
            left = trigger.left + scrollX - tooltip.width - offset
            arrowBottom = '12px'
            arrowRight = '-4px'
            break
        case 'right':
            top = trigger.top + scrollY + (trigger.height / 2) - (tooltip.height / 2)
            left = trigger.right + scrollX + offset
            arrowTop = '50%'
            arrowLeft = '-4px'
            break
        case 'right-start':
            top = trigger.top + scrollY
            left = trigger.right + scrollX + offset
            arrowTop = '12px'
            arrowLeft = '-4px'
            break
        case 'right-end':
            top = trigger.bottom + scrollY - tooltip.height
            left = trigger.right + scrollX + offset
            arrowBottom = '12px'
            arrowLeft = '-4px'
            break
    }

    // Prevent tooltip from going off-screen
    const padding = 8
    const viewportWidth = window.innerWidth
    const viewportHeight = window.innerHeight

    if (left < padding) {
        left = padding
    } else if (left + tooltip.width > viewportWidth - padding) {
        left = viewportWidth - tooltip.width - padding
    }

    if (top < padding + scrollY) {
        top = padding + scrollY
    } else if (top + tooltip.height > viewportHeight + scrollY - padding) {
        top = viewportHeight + scrollY - tooltip.height - padding
    }

    tooltipStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        maxWidth: props.maxWidth,
        transform: arrowLeft === '50%' ? 'translateX(-50%)' : 'none'
    }

    if (props.arrow) {
        arrowStyle.value = {
            top: arrowTop,
            bottom: arrowBottom,
            left: arrowLeft,
            right: arrowRight,
            transform: arrowLeft === '50%' ? 'translateX(-50%) rotate(45deg)' : 
                      arrowTop === '50%' ? 'translateY(-50%) rotate(45deg)' : 'rotate(45deg)'
        }
    }
}

onBeforeUnmount(() => {
    clearTimeout(showTimeout)
    clearTimeout(hideTimeout)
})
</script>

<style scoped>
.tooltip-wrapper {
    display: inline-block;
}

.tooltip-content {
    will-change: transform, opacity;
}

.tooltip-arrow {
    pointer-events: none;
}

/* Fix for light theme arrow border */
.tooltip-arrow.border {
    border-top: 0;
    border-right: 0;
}
</style>
