<template>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
            <slot name="default"></slot>
            <span v-if="required" class="text-red-500 ml-1">*</span>
        </label>
</template>
<script setup>
const props = defineProps({
    required: {
        type: Boolean,
        default: false
    }
})
</script>