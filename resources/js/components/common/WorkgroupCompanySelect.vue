<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <DynamicDropdown
            :model-value="workgroup"
            label="Workgroup"
            resource="workgroups"
            display-name="name"
            placeholder="Select workgroup"
            :required="required"
            :error="workgroupError"
            @update:model-value="onWorkgroupChange"
        />

        <DynamicDropdown
            :model-value="company"
            label="Company"
            resource="companies"
            display-name="name"
            placeholder="Select company"
            :required="required"
            :disabled="!workgroup"
            :params="companyParams"
            :error="companyError"
            @update:model-value="onCompanyChange"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'

const props = defineProps({
    workgroup: {
        type: Object,
        default: null,
    },
    company: {
        type: Object,
        default: null,
    },
    required: {
        type: Boolean,
        default: true,
    },
    workgroupError: {
        type: String,
        default: null,
    },
    companyError: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['update:workgroup', 'update:company'])

const companyParams = computed(() => {
    if (!props.workgroup?.id) {
        return {}
    }

    return { workgroup_id: props.workgroup.id }
})

const onWorkgroupChange = (value) => {
    emit('update:workgroup', value)
    emit('update:company', null)
}

const onCompanyChange = (value) => {
    emit('update:company', value)
}
</script>
