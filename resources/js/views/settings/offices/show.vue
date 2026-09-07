<template>
    <div v-if="show" class="office-show">
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="text-2xl font-bold !mb-0">Office Details</h5>
                    <div class="flex items-center gap-2">
                        <Button icon-left="arrow-left" icon-size="sm" variant="secondary" size="xs"
                            to="/settings/offices">
                        </Button>
                        <Button icon-left="edit" icon-size="sm" variant="primary" size="xs"
                            v-if="access.includes('update')"
                            :to="`/settings/offices/${model.id}/edit`">
                        </Button>
                        <Button icon-left="trash" icon-size="sm" variant="danger" size="xs"
                            @click="handleDelete" v-if="access.includes('delete')">
                        </Button>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Label label="Office Name" :value="model.name" />
                <Label label="Latitude" :value="model.latitude" />
                <Label label="Longitude" :value="model.longitude" />
                <Label label="Authorized Radius" :value="`${model.authorized_radius} m`" />
                <Label label="Timezone" :value="model.timezone || '—'" />

                <Label label="Status">
                    <span
                        :class="[
                            'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full',
                            model.active
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                        ]"
                    >
                        <SvgIcon
                            :name="model.active ? 'check' : 'x'"
                            size="xs"
                            class="mr-1"
                        />
                        {{ model.active ? 'Active' : 'Inactive' }}
                    </span>
                </Label>

                <Label label="Created At" :value="formatDate(model.created_at)" />
                <Label label="Updated At" :value="formatDate(model.updated_at)" />
            </div>
        </Panel>
    </div>

    <div v-else class="flex items-center justify-center min-h-[400px]">
        <Spinner size="md" text="Loading office details..." centered />
    </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useShowable } from '@/composables/useShowable'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Label from '@/components/ui/label.vue'
import Spinner from '@/components/ui/spinner.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { formatDateTime as formatDate } from '@/utils/date'

const route = useRoute()
const resource = route.meta?.resource || 'offices'

const { model, show, setData, removeDB, access } = useShowable(resource, 'office')

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
