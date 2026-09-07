<template>
  <Modal
    v-model="visible"
    size="3xl"
    :show-footer="false"
    :show-header="true"
    body-class="!p-0"
    @close="close"
  >
    <template #header>
      <div v-if="item" class="flex w-full items-center gap-3 pr-2">
        <div
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-sm font-bold text-primary dark:bg-emerald-400/15 dark:text-emerald-400"
        >
          {{ initials }}
        </div>
        <div class="min-w-0 flex-1">
          <h5 class="truncate text-lg font-bold !mb-0 text-gray-900 dark:text-gray-100">
            {{ displayName }}
          </h5>
          <p class="!mb-0 truncate text-xs text-gray-500 dark:text-gray-400">
            {{ employee?.employee_id || 'N/A' }}
            <span v-if="storeLabel"> · {{ storeLabel }}</span>
          </p>
        </div>
        <span
          class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/30 dark:text-green-300"
        >
          <span class="inline-block h-2 w-2 rounded-full bg-green-500" />
          Authorised
        </span>
      </div>
    </template>

    <div v-if="item" class="overflow-auto px-5 py-4 space-y-4">
      <div
        v-if="detailLoading"
        class="flex items-center justify-center py-16 text-sm text-gray-500 dark:text-gray-400"
      >
        Loading employee details...
      </div>

      <template v-else>
        <div
          class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300"
        >
          This employee is authorised. Details are read-only and cannot be edited.
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
          <div class="grid grid-cols-[140px_1fr] gap-x-4 gap-y-2 text-sm">
            <div class="text-gray-500 dark:text-gray-400">Address</div>
            <div class="text-gray-900 dark:text-gray-100">{{ addressLine }}</div>
            <div class="text-gray-500 dark:text-gray-400">Date of Birth</div>
            <div class="text-gray-900 dark:text-gray-100">{{ formatDisplayDate(employee?.dob) }}</div>
            <div class="text-gray-500 dark:text-gray-400">SSN</div>
            <div class="text-gray-900 dark:text-gray-100">{{ maskSsn(employee?.ssn) }}</div>
            <div class="text-gray-500 dark:text-gray-400">Email</div>
            <div class="text-gray-900 dark:text-gray-100">{{ employee?.email || '—' }}</div>
            <div class="text-gray-500 dark:text-gray-400">Phone</div>
            <div class="text-gray-900 dark:text-gray-100">{{ employee?.phone || '—' }}</div>
            <div class="text-gray-500 dark:text-gray-400">Date of Joining</div>
            <div class="text-gray-900 dark:text-gray-100">{{ formatDisplayDate(employee?.hire_date) }}</div>
          </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
          <h6 class="!mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">I-9 Status</h6>
          <div class="grid grid-cols-[140px_1fr] gap-x-4 gap-y-2 text-sm">
            <div class="text-gray-500 dark:text-gray-400">Attestation</div>
            <div class="text-gray-900 dark:text-gray-100">{{ i9ChoiceLabel }}</div>
            <template v-if="localItem?.uscis_number && ['lpr', 'alien'].includes(localItem?.i9_choice)">
              <div class="text-gray-500 dark:text-gray-400">USCIS / ID</div>
              <div class="text-gray-900 dark:text-gray-100">{{ formatUscisDisplay(localItem.uscis_number) }}</div>
            </template>
            <template v-if="localItem?.work_authorization_exp_date">
              <div class="text-gray-500 dark:text-gray-400">Work Auth Exp.</div>
              <div class="text-gray-900 dark:text-gray-100">{{ formatDisplayDate(localItem.work_authorization_exp_date) }}</div>
            </template>
          </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
          <h6 class="!mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Documents</h6>
          <div class="space-y-3">
            <div
              v-for="doc in displayDocuments"
              :key="doc.list"
              class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/60"
            >
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                  <p class="!mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    List {{ doc.list }}
                  </p>
                  <p class="!mb-0 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ doc.typeLabel || '—' }}
                  </p>
                  <p v-if="doc.fileName" class="!mb-0 mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ doc.fileName }}
                  </p>
                </div>
                <a
                  v-if="doc.url"
                  :href="doc.url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                >
                  View Document
                </a>
              </div>
            </div>
            <p v-if="!displayDocuments.length" class="!mb-0 text-sm text-gray-500 dark:text-gray-400">
              No documents on file.
            </p>
          </div>
        </div>

        <div
          v-if="localItem?.review_notes || localItem?.reviewer?.name"
          class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
        >
          <h6 class="!mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Review</h6>
          <p v-if="localItem?.review_notes" class="!mb-0 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
            {{ localItem.review_notes }}
          </p>
          <p
            v-if="localItem?.reviewer?.name"
            class="!mb-0 mt-2 text-xs text-gray-500 dark:text-gray-400"
          >
            Reviewed by {{ localItem.reviewer.name }}
          </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
          <h6 class="!mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Authorization</h6>
          <div class="space-y-2 text-sm">
            <p
              v-if="localItem?.authorizer?.name"
              class="!mb-0 text-gray-900 dark:text-gray-100"
            >
              Authorised by <span class="font-semibold">{{ localItem.authorizer.name }}</span>
            </p>
            <p
              v-if="localItem?.approved_at"
              class="!mb-0 text-gray-900 dark:text-gray-100"
            >
              Approved on <span class="font-semibold">{{ formatDateTime(localItem.approved_at) }}</span>
            </p>
            <a
              v-if="localItem?.authorization_doc_url"
              :href="localItem.authorization_doc_url"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1 text-blue-600 hover:underline dark:text-blue-400"
            >
              View authorization document
            </a>
            <template v-if="localItem?.tnc_doc_type || localItem?.tnc_document_url">
              <p class="!mb-0 text-gray-500 dark:text-gray-400">TNC Document</p>
              <p class="!mb-0 text-gray-900 dark:text-gray-100">{{ tncDocTypeLabel || '—' }}</p>
              <a
                v-if="localItem?.tnc_document_url"
                :href="localItem.tnc_document_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-blue-600 hover:underline dark:text-blue-400"
              >
                View TNC document
              </a>
            </template>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Button variant="outline-secondary" size="sm" @click="close">
            Close
          </Button>
        </div>
      </template>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/ui/button.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatDate, formatDateTime } from '@/utils/date'
import i9Docs from '@/views/onboarding-process/docs.json'

const getDocTypeLabel = (group, key) => {
  if (!key) return ''
  return i9Docs[group]?.[key]?.value || key
}

const tncDocTypeOptionGroups = [
  { prefix: 'list_a', group: 'list_a_doc_title_1' },
  { prefix: 'list_b', group: 'list_b_doc_title' },
  { prefix: 'list_c', group: 'list_c_doc_title' },
]

const parseTncDocType = (value) => {
  if (!value || !value.includes('|')) return { prefix: null, key: value }
  const [prefix, key] = value.split('|')
  return { prefix, key }
}

const getTncDocTypeLabel = (value) => {
  const { prefix, key } = parseTncDocType(value)
  if (!key) return ''
  const group = tncDocTypeOptionGroups.find((item) => item.prefix === prefix)?.group
  return group ? getDocTypeLabel(group, key) : value
}

const statusOptions = [
  { id: 'citizen', number: 1, label: 'A citizen of the United States' },
  { id: 'national', number: 2, label: 'A noncitizen national of the United States' },
  { id: 'lpr', number: 3, label: 'A lawful permanent resident' },
  { id: 'alien', number: 4, label: 'An alien authorized to work' },
]

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  item: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

const message = useMessage()
const detailLoading = ref(false)
const localItem = ref(null)

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const employee = computed(() => localItem.value?.employee || props.item?.employee || null)

const isListAOnly = computed(() => ['citizen', 'national'].includes(localItem.value?.i9_choice))

const i9ChoiceLabel = computed(() => {
  const choice = localItem.value?.i9_choice
  const option = statusOptions.find((item) => item.id === choice)
  return option ? `${option.number}. ${option.label}` : '—'
})

const tncDocTypeLabel = computed(() => getTncDocTypeLabel(localItem.value?.tnc_doc_type))

const displayDocuments = computed(() => {
  const item = localItem.value
  if (!item) return []

  const docs = []

  if (isListAOnly.value) {
    if (item.list_a_doc_type || item.list_a_doc_id) {
      docs.push({
        list: 'A',
        typeLabel: getDocTypeLabel('list_a_doc_title_1', item.list_a_doc_type),
        url: item.list_a_url,
        fileName: item.list_a_document?.document_name || (item.list_a_doc_id ? 'Document on file' : null),
      })
    }
    return docs
  }

  if (item.list_b_doc_type || item.list_b_doc_id) {
    docs.push({
      list: 'B',
      typeLabel: getDocTypeLabel('list_b_doc_title', item.list_b_doc_type),
      url: item.list_b_url,
      fileName: item.list_b_document?.document_name || (item.list_b_doc_id ? 'Document on file' : null),
    })
  }

  if (item.list_c_doc_type || item.list_c_doc_id) {
    docs.push({
      list: 'C',
      typeLabel: getDocTypeLabel('list_c_doc_title', item.list_c_doc_type),
      url: item.list_c_url,
      fileName: item.list_c_document?.document_name || (item.list_c_doc_id ? 'Document on file' : null),
    })
  }

  return docs
})

const initials = computed(() => {
  const first = employee.value?.first_name?.[0] || employee.value?.pos_name?.[0] || '?'
  const last = employee.value?.last_name?.[0] || ''
  return `${first}${last}`.toUpperCase()
})

const displayName = computed(() => {
  const e = employee.value
  if (!e) return 'Employee'
  if (e.last_name || e.first_name) {
    const name = [e.last_name, e.first_name].filter(Boolean).join(', ')
    return e.middle_name ? `${name} ${e.middle_name}` : name
  }
  return e.pos_name || 'Employee'
})

const storeLabel = computed(() => {
  const company = localItem.value?.company || props.item?.company || employee.value?.company
  if (!company) return ''
  const parts = [company.store_number, company.name].filter(Boolean)
  return parts.join(' - ')
})

const addressLine = computed(() => {
  const e = employee.value
  if (!e) return '—'
  const street = [e.street, e.apt_number ? `Apt ${e.apt_number}` : null].filter(Boolean).join(', ')
  const cityLine = [e.city, e.state, e.zip].filter(Boolean).join(' ')
  return [street, cityLine].filter(Boolean).join(', ') || '—'
})

const formatDisplayDate = (value) => {
  if (!value) return '—'
  return formatDate(value) || value
}

const maskSsn = (ssn) => {
  if (!ssn) return '—'
  const digits = String(ssn).replace(/\D/g, '')
  if (digits.length < 4) return ssn
  return `***-**-${digits.slice(-4)}`
}

const formatUscisDisplay = (value) => {
  if (!value) return '—'
  if (value.startsWith('I94:')) return `Form I-94: ${value.replace(/^I94:/, '')}`
  if (value.startsWith('FP:')) {
    const parts = value.replace(/^FP:/, '').split('|')
    return `Foreign Passport: ${parts[0] || ''}${parts[1] ? ` (${parts[1]})` : ''}`
  }
  return value
}

const hydrateDetail = async () => {
  localItem.value = props.item ? { ...props.item } : null
  if (!props.item?.id) return

  detailLoading.value = true
  try {
    const response = await useRequest('get', `onboarding/employee-confirmation/${props.item.id}`)
    localItem.value = response?.model || response?.data || response
  } catch (err) {
    message.error(err?.response?.data?.message || 'Failed to load employee confirmation details.')
  } finally {
    detailLoading.value = false
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) hydrateDetail()
  }
)

watch(
  () => props.item?.id,
  () => {
    if (props.modelValue) hydrateDetail()
  }
)

const close = () => {
  visible.value = false
  localItem.value = null
}
</script>
