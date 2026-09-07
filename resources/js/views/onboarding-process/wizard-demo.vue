<template>
    <div class="container mx-auto py-8">
        <div class="mb-6">
            <Button variant="primary" @click="showWizard = true">
                Open Staff Member Wizard
            </Button>
        </div>

        <!-- Wizard Modal/Container -->
        <div v-if="showWizard" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
            <div class="w-full max-w-4xl">
                <Step4I9Form @close="showWizard = false" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import Button from '@/components/ui/button.vue'
import Step4I9Form from './steps/Step4I9Form.vue'

const showWizard = ref(false)
</script>

<style scoped>
/* Ensure proper scroll behavior for modal */
.fixed.inset-0 {
    overflow-y: auto;
}
</style>
