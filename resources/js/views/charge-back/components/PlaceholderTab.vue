<template>
    <div>
        <header class="mb-6">
            <h1 class="!text-xl sm:!text-2xl font-bold text-gray-900 dark:text-white mb-2">
                {{ title }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 !mb-0">
                {{ description }}
            </p>
        </header>

        <Panel>
            <div class="py-12 text-center">
                <SvgIcon name="forms" size="xl" class="mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                <p class="text-gray-600 dark:text-gray-400 text-sm !mb-0">
                    This section will be connected to the chargebacks API next.
                </p>
            </div>
        </Panel>
    </div>
</template>

<script setup>
import Panel from '@/components/ui/panel.vue'
import SvgIcon from '@/components/SvgIcon.vue'

defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
})
</script>
