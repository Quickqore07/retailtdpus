<template>
    <div :class="containerClasses">
        <div class="flex flex-col items-center justify-center py-12 px-4">
            <!-- Icon -->
            <div :class="iconWrapperClasses">
                <SvgIcon 
                    :name="icon" 
                    :size="iconSize"
                    :class="iconColorClass"
                />
            </div>

            <!-- Title -->
            <h5 :class="titleClasses">
                {{ title }}
            </h5>

            <!-- Message -->
            <p v-if="message" :class="messageClasses">
                {{ message }}
            </p>

            <!-- Action Button (Optional) -->
            <slot name="action">
                <Button
                    v-if="showAction"
                    :icon-left="actionIcon"
                    :variant="actionVariant"
                    :size="actionSize"
                    @click="$emit('action')"
                    class="mt-4"
                >
                    {{ actionText }}
                </Button>
            </slot>

            <!-- Custom Content Slot -->
            <div v-if="$slots.default" class="mt-4">
                <slot></slot>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Button from '@/components/ui/button.vue'

const props = defineProps({
    // Icon props
    icon: {
        type: String,
        default: 'search'
    },
    iconSize: {
        type: String,
        default: '2xl',
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl'].includes(value)
    },
    iconColor: {
        type: String,
        default: 'gray',
        validator: (value) => ['gray', 'blue', 'green', 'yellow', 'red', 'purple', 'indigo'].includes(value)
    },

    // Text props
    title: {
        type: String,
        default: 'No Data Found'
    },
    message: {
        type: String,
        default: null
    },

    // Size variants
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },

    // Action button props
    showAction: {
        type: Boolean,
        default: false
    },
    actionText: {
        type: String,
        default: 'Add New'
    },
    actionIcon: {
        type: String,
        default: 'plus'
    },
    actionVariant: {
        type: String,
        default: 'primary'
    },
    actionSize: {
        type: String,
        default: 'sm'
    },

    // Container props
    fullHeight: {
        type: Boolean,
        default: false
    },
    bordered: {
        type: Boolean,
        default: false
    },
    transparent: {
        type: Boolean,
        default: false
    }
})

defineEmits(['action'])

const containerClasses = computed(() => {
    const classes = []
    
    if (props.fullHeight) {
        classes.push('min-h-[400px] flex items-center justify-center')
    }
    
    if (props.bordered) {
        classes.push('border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg')
    }
    
    if (!props.transparent) {
        classes.push('bg-gray-50 dark:bg-gray-800/50')
    }
    
    return classes.join(' ')
})

const iconWrapperClasses = computed(() => {
    const sizeMap = {
        sm: 'w-12 h-12',
        md: 'w-16 h-16',
        lg: 'w-24 h-24'
    }
    
    return `flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 mb-4 ${sizeMap[props.size]}`
})

const iconColorClass = computed(() => {
    const colorMap = {
        gray: 'text-gray-400 dark:text-gray-500',
        blue: 'text-blue-400 dark:text-blue-500',
        green: 'text-green-400 dark:text-green-500',
        yellow: 'text-yellow-400 dark:text-yellow-500',
        red: 'text-red-400 dark:text-red-500',
        purple: 'text-purple-400 dark:text-purple-500',
        indigo: 'text-indigo-400 dark:text-indigo-500'
    }
    return colorMap[props.iconColor] || colorMap.gray
})

const titleClasses = computed(() => {
    const sizeMap = {
        sm: 'text-base',
        md: 'text-lg',
        lg: 'text-xl'
    }
    
    return `font-semibold text-gray-900 dark:text-gray-100 mb-2 ${sizeMap[props.size]}`
})

const messageClasses = computed(() => {
    const sizeMap = {
        sm: 'text-xs',
        md: 'text-sm',
        lg: 'text-base'
    }
    
    return `text-gray-600 dark:text-gray-400 text-center max-w-md ${sizeMap[props.size]}`
})
</script>

<style scoped>
/* Additional styles if needed */
</style>
