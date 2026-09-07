<template>
  <div
    v-if="canShowFilters"
    class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
  >
    <div v-if="can('charge-back', 'regional-director')">
      <DynamicDropdown
        v-model="regionalDirector"
        resource="users?role=Regional Director"
        display-name="name"
        placeholder="Select Regional Director"
        icon-left="user"
        label="Regional Director"
        @change="onRegionalDirectorChange"
      />
    </div>
    <div v-if="can('charge-back', 'area-manager')">
      <DynamicDropdown
        v-model="areaManager"
        resource="users?role=Area Manager"
        display-name="name"
        placeholder="Select Area Manager"
        icon-left="user"
        label="Area Manager"
        @change="onAreaManagerChange"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { usePermission } from '@/composables/usePermission'

const emit = defineEmits(['change'])

const { can } = usePermission()
const regionalDirector = ref(null)
const areaManager = ref(null)

const canShowFilters = computed(() => {
  return can('charge-back', 'regional-director') || can('charge-back', 'area-manager')
})

const selectedUserId = computed(() => {
  return regionalDirector.value?.id || areaManager.value?.id || null
})

const emitChange = () => {
  emit('change', selectedUserId.value)
}

const onRegionalDirectorChange = () => {
  areaManager.value = null
  emitChange()
}

const onAreaManagerChange = () => {
  regionalDirector.value = null
  emitChange()
}

defineExpose({
  selectedUserId,
})
</script>
