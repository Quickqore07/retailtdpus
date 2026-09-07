<template>
  <div v-if="show" class="ledger-show">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="text-2xl font-bold !mb-0">Ledger Details</h5>
          <div class="flex items-center gap-2">
            <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs"
              to="/settings/ledgers">
            </Button>
            <Button icon-left="edit" icon-size="sm" variant="primary" size="xs" v-if="access.includes('update')"
              :to="`/settings/ledgers/${model.id}/edit`">
            </Button>
            <Button icon-left="trash" icon-size="sm" variant="danger" size="xs" @click="handleDelete" v-if="access.includes('delete')">
            </Button>
          </div>
        </div>
      </template>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" >
        <Label label="Account Type" :value="model.account_type" />
        <Label label="Code" :value="model.code" />
        <Label label="Name" :value="model.name" />
        <template v-if="model.account_type === 'Bank' && access.includes('bank_details')">
          <Label label="Account No." :value="model.account_no" />
          <Label label="Bank Name" :value="model.bank_name" />
          <Label label="Transition Code" :value="model.transition_code" />
          <Label label="Routing" :value="model.routing" />
          <Label label="Starting Check Number" :value="model.starting_check_number" />
          <Label label="Default Bank" :value="model.default_bank ? 'Yes' : 'No'" />
          <Label label="Latest Check Number" :value="model.latest_check_number" />
          <Label label="Bank Address" :value="model.bank_address" class="md:col-span-2 lg:col-span-3" />
        </template>
        <Label label="Created At" :value="formatDate(model.created_at)" />
        <Label label="Updated At" :value="formatDate(model.updated_at)" />
      </div>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading ledger details..." centered />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDateTime as formatDate } from '@/utils/date'

const route = useRoute()
const resource = route.meta?.resource || 'settings/ledgers'

const { model, show, setData, removeDB, access } = useShowable(resource, 'ledger')

const handleDelete = async () => {
  const id = model.value?.id
  if (id) {
    await removeDB(resource, id)
  }
}

defineExpose({
  setData
})
</script>

<style scoped>
@media (max-width: 640px) {
  .ledger-show {
    padding: 1rem;
  }
}
</style>
