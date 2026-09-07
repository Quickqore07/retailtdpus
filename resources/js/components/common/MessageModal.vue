<template>  
<Modal v-model="isOpen" :title="title" size="lg">
      <div class="max-h-[300px] overflow-y-auto flex flex-wrap gap-2 inline-flex items-center gap-1.5  px-3 py-2 text-sm bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-yellow-700 dark:text-yellow-300  ">
        <div
          v-for="(message, index) in messages"
          :key="message"
          class="flex items-center gap-2  pt-2"
          :class="index === 0 ? '' : 'border-t border-gray-200 dark:border-gray-800'"
        >
        <div class="text-orange-700">
          <SvgIcon name="exclamation-circle" size="md" class="text-red-700" />
        </div>
          <span>{{ message }}</span>
        </div>
      </div>
    </Modal>
</template>
<script setup>
import Modal from './Modal.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Messages'
  },
  messages: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:modelValue', 'close'])

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})
</script>