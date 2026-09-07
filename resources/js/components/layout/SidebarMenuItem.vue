<template>
    <li class="relative mx-2">
        <!-- Menu Item with Children (Dropdown) -->
        <template v-if="item.children && item.children.length > 0">
            <button :class="[
                'flex items-center w-full p-3 border-none bg-transparent rounded-lg text-gray-600 dark:text-gray-400 text-sm font-medium no-underline cursor-pointer transition-all duration-150 gap-3 text-left overflow-hidden',
                'hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white',
                isChildActive ? 'bg-primary/10 text-primary hover:bg-primary/10 hover:text-primary dark:bg-primary/15 dark:text-primary' : '',
                isCollapsed ? 'justify-center' : ''
            ]" @click="toggleExpand" :title="isCollapsed ? item.label : ''">
                <span class="flex items-center justify-center w-6 h-6 flex-shrink-0">
                    <SvgIcon :name="item.icon" size="md" />
                </span>
                <transition name="fade-slide">
                    <span v-show="!isCollapsed" class="flex-1 whitespace-nowrap overflow-hidden text-ellipsis">
                        {{ item.label }}
                    </span>
                </transition>
                <transition name="fade">
                    <span v-show="!isCollapsed" :class="[
                        'flex items-center justify-center w-5 h-5 flex-shrink-0 transition-transform duration-150',
                        isExpanded ? 'rotate-180' : ''
                    ]">
                        <SvgIcon name="chevron-down" size="sm" />
                    </span>
                </transition>
            </button>

            <!-- Submenu -->
            <transition name="submenu">
                <ul v-show="isExpanded && !isCollapsed" class="list-none py-1 pl-[54px] m-0 overflow-hidden">
                    <li v-for="child in item.children" :key="child.id" class="my-1">
                        <!-- Child with nested children -->
                        <template v-if="child.children && child.children.length > 0">
                            <button :class="[
                                'flex items-center w-full py-2 px-3 border-none bg-transparent rounded-md text-gray-500 dark:text-gray-400 text-sm cursor-pointer transition-all duration-150 gap-2',
                                'hover:text-gray-900 hover:bg-gray-50 dark:hover:text-white dark:hover:bg-gray-800',
                                isNestedChildActive(child) ? 'text-primary bg-primary/5 dark:bg-primary/10' : ''
                            ]" @click="toggleNestedExpand(child.id)">
                                <span :class="[
                                    'w-1.5 h-1.5 rounded-full bg-current flex-shrink-0',
                                    isNestedChildActive(child) ? 'opacity-100' : 'opacity-50'
                                ]"></span>
                                <span class="flex-1 whitespace-nowrap text-left">{{ child.label }}</span>
                                <span :class="[
                                    'flex items-center justify-center w-4 h-4 flex-shrink-0 transition-transform duration-150',
                                    nestedExpanded[child.id] ? 'rotate-180' : ''
                                ]">
                                    <SvgIcon name="chevron-down" size="sm" />
                                </span>
                            </button>

                            <!-- Nested submenu (third level) -->
                            <transition name="submenu">
                                <ul v-show="nestedExpanded[child.id]" class="list-none py-1 pl-6 m-0 overflow-hidden">
                                    <li v-for="grandchild in child.children" :key="grandchild.id" class="my-1">
                                        <router-link v-if="grandchild.path" :to="grandchild.path || '#'" :class="[
                                            'flex items-center gap-2 py-2 px-3 rounded-md text-gray-500 dark:text-gray-400 text-sm no-underline transition-all duration-150',
                                            'hover:text-gray-900 hover:bg-gray-50 dark:hover:text-white dark:hover:bg-gray-800',
                                            activePath === grandchild.path ? 'text-primary bg-primary/10 dark:bg-primary/10 font-medium' : ''
                                        ]" @click="$emit('navigate', grandchild.path)">
                                            <span :class="[
                                                'w-1 h-1 rounded-full bg-current flex-shrink-0',
                                                activePath === grandchild.path ? 'opacity-100' : 'opacity-50'
                                            ]"></span>
                                            <span class="whitespace-normal break-words">{{ grandchild.label }}</span>
                                        </router-link>
                                    </li>
                                </ul>
                            </transition>
                        </template>

                        <!-- Child without nested children -->
                         
                        <!-- <button v-if="child.newWindow" :class="[
                            'flex items-center w-full p-3 border-none bg-transparent rounded-lg text-gray-600 dark:text-gray-400 text-sm font-medium no-underline cursor-pointer transition-all duration-150 gap-3 text-left overflow-hidden',
                            'hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white',
                            activePath === child.path ? 'bg-primary/10 text-primary hover:bg-primary/10 hover:text-primary dark:bg-primary/15 dark:text-primary' : '',
                            isCollapsed ? 'justify-center' : ''
                        ]" :title="isCollapsed ? child.label : ''" @click="handleClick(child)">
                            <span class="flex items-center justify-center w-6 h-6 flex-shrink-0">
                            </span>
                            <transition name="fade-slide">
                                <span v-show="!isCollapsed" class="flex-1 whitespace-nowrap overflow-hidden text-ellipsis">
                                    {{ child.label }}
                                </span>
                            </transition>
                        </button> -->
                        <router-link v-else-if="child.path" :to="child.path || '#'" :class="[
                            'flex items-center gap-3 py-2 px-3 rounded-md text-gray-500 dark:text-gray-400 text-sm no-underline transition-all duration-150',
                            'hover:text-gray-900 hover:bg-gray-50 dark:hover:text-white dark:hover:bg-gray-800',
                            activePath === child.path ? 'text-primary bg-primary/10 dark:bg-primary/10' : ''
                        ]" @click="handleClick(child)">
                            <span :class="[
                                'w-1.5 h-1.5 rounded-full bg-current flex-shrink-0',
                                activePath === child.path ? 'opacity-100' : 'opacity-50'
                            ]"></span>
                            <span class="whitespace-nowrap">{{ child.label }}</span>
                        </router-link>
                    </li>
                </ul>
            </transition>

            <!-- Collapsed Tooltip/Popup -->
            <transition name="popup">
                <div v-if="isCollapsed && isHovered"
                    class="absolute left-[calc(100%+8px)] top-0 min-w-[200px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-[1000] overflow-hidden"
                    @mouseenter="isHovered = true" @mouseleave="isHovered = false">
                    <div
                        class="py-3 px-4 text-sm font-semibold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700">
                        {{ item.label }}
                    </div>
                    <ul class="list-none p-2 m-0">
                        <li v-for="child in item.children" :key="child.id" class="mb-1">
                            <!-- Child with nested children -->
                            <template v-if="child.children && child.children.length > 0">
                                <div
                                    class="text-xs font-semibold text-gray-500 dark:text-gray-400 px-3 py-1 uppercase tracking-wide">
                                    {{ child.label }}
                                </div>
                                <ul class="list-none pl-3 mt-1">
                                    <li v-for="grandchild in child.children" :key="grandchild.id" class="mb-1">
                                        <router-link v-if="grandchild.path" :to="grandchild.path || '#'" :class="[
                                            'block py-2 px-3 rounded-md text-gray-600 dark:text-gray-300 text-sm no-underline transition-all duration-150',
                                            'hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white',
                                            activePath === grandchild.path ? 'bg-primary/10 text-primary dark:bg-primary/15' : ''
                                        ]" @click="$emit('navigate', grandchild.path)">
                                            {{ grandchild.label }}
                                        </router-link>
                                    </li>
                                </ul>
                            </template>

                            <!-- Child without nested children -->
                            <router-link v-else-if="child.path" :to="child.path || '#'" :class="[
                                'block py-2 px-3 rounded-md text-gray-600 dark:text-gray-300 text-sm no-underline transition-all duration-150',
                                'hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white',
                                activePath === child.path ? 'bg-primary/10 text-primary dark:bg-primary/15' : ''
                            ]" @click="$emit('navigate', child.path)">
                                {{ child.label }}
                            </router-link>
                        </li>
                    </ul>
                </div>
            </transition>
        </template>

        <!-- Menu Item without Children (Direct Link) -->
        <template v-else>
            <router-link v-if="item.path" :to="item.path || '#'" :class="[
                'relative flex items-center w-full p-3 border-none bg-transparent rounded-lg text-gray-600 dark:text-gray-400 text-sm font-medium no-underline cursor-pointer transition-all duration-150 gap-3 text-left overflow-hidden',
                'hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white',
                activePath === item.path ? 'bg-primary/10 text-primary hover:bg-primary/10 hover:text-primary dark:bg-primary/15 dark:text-primary' : '',
                isCollapsed ? 'justify-center' : ''
            ]" :title="isCollapsed ? item.label : ''" @click="$emit('navigate', item.path)">
                <span class="relative flex items-center justify-center w-6 h-6 flex-shrink-0">
                    <SvgIcon :name="item.icon" size="md" />
                </span>
                <transition name="fade-slide">
                    <span v-show="!isCollapsed" class="flex-1 whitespace-nowrap overflow-hidden text-ellipsis">
                        {{ item.label }}
                    </span>
                </transition>
                <span
                    v-if="badgeCount > 0"
                    :class="[
                        'inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-semibold tabular-nums leading-none shrink-0',
                        isCollapsed ? 'absolute -top-1 -right-1 min-w-[1.125rem] h-[1.125rem] px-1' : '',
                        'bg-red-500 text-white'
                    ]"
                    :title="`${badgeCount} unread notification${badgeCount === 1 ? '' : 's'}`"
                >
                    {{ badgeCount > 99 ? '99+' : badgeCount }}
                </span>
            </router-link>

            <!-- Collapsed Tooltip -->
            <transition name="tooltip">
                <div v-if="isCollapsed && isHovered"
                    class="absolute left-[calc(100%+8px)] top-1/2 -translate-y-1/2 py-2 px-3 bg-gray-900 dark:bg-gray-700 text-white text-sm rounded-md whitespace-nowrap z-[1070] shadow-md tooltip-arrow">
                    {{ item.label }}
                </div>
            </transition>
        </template>
    </li>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Button from '../ui/button.vue'

// Props
const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    isCollapsed: {
        type: Boolean,
        required: true
    },
    activePath: {
        type: String,
        required: true
    },
    badgeCount: {
        type: Number,
        default: 0
    }
})


const emit = defineEmits(['navigate'])

// State
const isExpanded = ref(false)
const isHovered = ref(false)
const nestedExpanded = ref({})

// Computed
const isChildActive = computed(() => {
    if (!props.item.children) return false
    return props.item.children.some(child => {
        // Check if direct child is active
        if (child.path === props.activePath) return true
        // Check if any nested child is active
        if (child.children && child.children.length > 0) {
            return child.children.some(grandchild => grandchild.path === props.activePath)
        }
        return false
    })
})

const handleClick = (child  ) => {
    if (child.newWindow) {
        window.open(child.path, '_blank')
    } else {
        emit('navigate', child.path)
    }
}

// Methods
const isNestedChildActive = (child) => {
    if (child.path === props.activePath) return true
    if (child.children && child.children.length > 0) {
        return child.children.some(grandchild => grandchild.path === props.activePath)
    }
    return false
}

const toggleNestedExpand = (childId) => {
    nestedExpanded.value[childId] = !nestedExpanded.value[childId]
}

// Watch for active child to auto-expand
watch(() => props.activePath, () => {
    if (isChildActive.value && !props.isCollapsed) {
        isExpanded.value = true

        // Auto-expand nested children if a grandchild is active
        if (props.item.children) {
            props.item.children.forEach(child => {
                if (child.children && child.children.length > 0) {
                    const hasActiveGrandchild = child.children.some(
                        grandchild => grandchild.path === props.activePath
                    )
                    if (hasActiveGrandchild) {
                        nestedExpanded.value[child.id] = true
                    }
                }
            })
        }
    }
}, { immediate: true })

// Watch collapse state to close submenus
watch(() => props.isCollapsed, (collapsed) => {
    if (collapsed) {
        // Keep expanded state in memory but visually hide
    }
})

const toggleExpand = () => {
    if (!props.isCollapsed) {
        isExpanded.value = !isExpanded.value
    }
}
</script>

<style scoped>
/* Tooltip arrow */
.tooltip-arrow::before {
    content: '';
    position: absolute;
    right: 100%;
    top: 50%;
    transform: translateY(-50%);
    border: 6px solid transparent;
    border-right-color: rgb(17 24 39);
}

.dark .tooltip-arrow::before {
    border-right-color: rgb(55 65 81);
}

/* Vue Transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(-8px);
}

.submenu-enter-active,
.submenu-leave-active {
    transition: max-height 0.25s ease, opacity 0.2s ease;
    max-height: 500px;
}

.submenu-enter-from,
.submenu-leave-to {
    max-height: 0;
    opacity: 0;
}

.popup-enter-active,
.popup-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.popup-enter-from,
.popup-leave-to {
    opacity: 0;
    transform: translateX(-8px);
}

.tooltip-enter-active,
.tooltip-leave-active {
    transition: opacity 0.1s ease, transform 0.1s ease;
}

.tooltip-enter-from,
.tooltip-leave-to {
    opacity: 0;
    transform: translateX(-4px) translateY(-50%);
}
</style>
