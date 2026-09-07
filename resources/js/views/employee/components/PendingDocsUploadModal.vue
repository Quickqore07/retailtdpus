<template>
  <Modal
    :model-value="modelValue"
    size="lg"
    :show-footer="true"
    :show-cancel="true"
    :show-confirm="true"
    cancel-text="Cancel"
    confirm-text="Submit"
    :loading="uploadLoading"
    @update:model-value="onModelValueUpdate"
    @close="closeModal"
    @confirm="submitPendingDocsUpload"
  >
    <template #header>
      <h5 class="text-xl font-bold !mb-0">{{ i9Only ? 'Upload I-9' : 'Upload Pending Documents' }}</h5>
    </template>
    <div class="space-y-5">
      <p class="text-sm text-gray-600 dark:text-gray-300 ">
        Employee: <span class="font-medium">{{ employee?.pos_name || '-' }}</span>
      </p>

      <div
        v-if="needI9"
        class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
      >
        <Input
          type="file"
          :label="i9Only ? 'Upload I-9' : 'Upload I-9 (Pending)'"
          placeholder="Select I-9 document"
          icon-left="files"
          :error="errors.i9 || null"
          @change="onFileChange($event, 'i9')"
        />
      </div>
      <div
        v-if="needW4"
        class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
      >
        <Input
          type="file"
          label="Upload W-4 (Pending)"
          placeholder="Select W-4 document"
          icon-left="files"
          :error="errors.w4 || null"
          @change="onFileChange($event, 'w4')"
        />
      </div>

      <div
        v-if="needProfilePicture"
        class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
      >
        <Input
          type="file"
          label="Upload Profile Picture"
          placeholder="Select profile picture"
          icon-left="files"
          :error="errors.profilePicture || null"
          @change="onFileChange($event, 'profilePicture')"
        />
      </div>

      <p
        v-if="!needI9 && !needW4 && !needProfilePicture"
        class="text-sm text-green-600 dark:text-green-400 !mb-0"
      >
        {{ i9Only ? 'I-9 is already uploaded for this employee.' : 'I-9 and W-4 are already completed for this employee.' }}
      </p>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Input from '@/components/ui/input.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { useRoute } from 'vue-router'
import { assignValidatedFile } from '@/utils/documentUpload'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  employee: {
    type: Object,
    default: null,
  },
  /** When true, only prompt for I-9 upload (usable outside pending-i9-w4 routes). */
  i9Only: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'uploaded'])

const message = useMessage()
const uploadLoading = ref(false)
const i9File = ref(null)
const w4File = ref(null)
const profilePictureFile = ref(null)
const errors = ref({
  i9: null,
  w4: null,
})

const route = useRoute()
const isPendingI9W4List = computed(() => route.path.includes('pending-i9-w4'))
const isMissingProfilePictureList = computed(() => route.path.includes('missing-profile-picture'))

const hasDocumentType = (type) =>
  (props.employee?.employee_documents || []).some((document) => document.document_type === type)

const needI9 = computed(
  () =>
    !!props.employee &&
    !hasDocumentType('i9_form') &&
    (props.i9Only || isPendingI9W4List.value)
)
const needW4 = computed(
  () =>
    !!props.employee &&
    !hasDocumentType('w4_form') &&
    !props.i9Only &&
    isPendingI9W4List.value
)
const needProfilePicture = computed(
  () =>
    !!props.employee &&
    !props.employee.profile_picture &&
    !props.i9Only &&
    isMissingProfilePictureList.value
)

const resetModalState = () => {
  i9File.value = null
  w4File.value = null
  profilePictureFile.value = null
  errors.value = { i9: null, w4: null, profilePicture: null }
}

const onModelValueUpdate = (value) => {
  if (!value) {
    resetModalState()
  }
  emit('update:modelValue', value)
}

const closeModal = () => {
  resetModalState()
  emit('update:modelValue', false)
}

const onFileChange = (event, type) => {
  const input = event?.target
  const file = input?.files?.[0] || null
  const assign = (selectedFile) => {
    if (type === 'i9') {
      i9File.value = selectedFile
      errors.value.i9 = null
    }
    if (type === 'w4') {
      w4File.value = selectedFile
      errors.value.w4 = null
    }
    if (type === 'profilePicture') {
      profilePictureFile.value = selectedFile
      errors.value.profilePicture = null
    }
  }

  assignValidatedFile(file, assign, {
    onError: (error) => {
      if (type === 'i9') errors.value.i9 = error
      if (type === 'w4') errors.value.w4 = error
      if (type === 'profilePicture') errors.value.profilePicture = error
    },
    input,
  })
}

const submitPendingDocsUpload = async () => {
  const employee = props.employee
  if (!employee?.id) return

  errors.value = { i9: null, w4: null, profilePicture: null }

  if (props.i9Only && needI9.value && !i9File.value) {
    errors.value.i9 = 'Please select an I-9 document.'
  }

  if (errors.value.i9 || errors.value.w4 || errors.value.profilePicture) return

  uploadLoading.value = true
  try {
    if (needI9.value && i9File.value) {
      const i9Payload = new FormData()
      i9Payload.append('employee_id', employee.id)
      i9Payload.append('document_name', 'i9_form')
      i9Payload.append('document_type', 'i9_form')
      i9Payload.append('file', i9File.value)
      await useRequest('post', 'employee/documents', i9Payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }

    if (needW4.value && w4File.value) {
      const w4Payload = new FormData()
      w4Payload.append('employee_id', employee.id)
      w4Payload.append('document_name', 'w4_form')
      w4Payload.append('document_type', 'w4_form')
      w4Payload.append('file', w4File.value)
      await useRequest('post', 'employee/documents', w4Payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }

    if (needProfilePicture.value && profilePictureFile.value) {
      const profilePicturePayload = new FormData()
      profilePicturePayload.append('employee_id', employee.id)
      profilePicturePayload.append('document_name', 'profile_picture')
      profilePicturePayload.append('document_type', 'profile_picture')
      profilePicturePayload.append('file', profilePictureFile.value)
      await useRequest('post', 'employee/documents', profilePicturePayload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }

    message.success(props.i9Only ? 'I-9 uploaded successfully.' : 'Pending documents uploaded successfully.')
    resetModalState()
    emit('update:modelValue', false)
    emit('uploaded')
  } catch (error) {
    emit('uploaded')

    message.error(error?.response?.data?.message || 'Failed to upload pending document(s).')
  } finally {
    uploadLoading.value = false
  }
}

watch(
  () => props.employee?.id,
  () => {
    resetModalState()
  }
)
</script>
