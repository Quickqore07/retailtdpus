<template>
  <div>
    <div class="mb-6">
      <h4 class="text-3xl font-bold text-gray-900 dark:text-white !mb-1">Email templates</h4>
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
        Subject and body support merge fields such as
        <code v-pre class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">{{invoice_number}}</code>
        — use the buttons below the fields to insert them.
      </p>
    </div>

    <div
      v-if="!canView"
      class="text-center py-16 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
    >
      <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
        You do not have permission to manage AR email templates.
      </p>
    </div>

    <template v-else>
      <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
          <div class="flex flex-col sm:flex-row flex-wrap gap-3 flex-1">
            <div class="w-full sm:w-40">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Type</label>
              <select
                v-model="filterTemplateType"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-2"
                @change="applyFilters"
              >
                <option value="">All types</option>
                <option value="invoice">Invoice</option>
                <option value="statement">Statement</option>
              </select>
            </div>
            <div class="w-full sm:min-w-[200px] sm:flex-1 sm:max-w-md">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Subject</label>
              <Input
                v-model="filterSubject"
                placeholder="Filter by subject…"
                @input="debounceSubjectFilter"
              />
            </div>
          </div>
          <Button
            v-if="canAdd"
            type="button"
            variant="primary"
            size="sm"
            icon-left="plus"
            icon-size="md"
            class="shrink-0"
            @click="openCreate"
          >
            Add template
          </Button>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0 !mt-3">
          {{ pagination.total }} template{{ pagination.total === 1 ? '' : 's' }}
          <span v-if="pagination.total > 0 && (filterTemplateType || filterSubject.trim())" class="text-gray-500 dark:text-gray-500">
            (filtered)
          </span>
        </p>
      </div>

      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <Spinner size="lg" text="Loading templates..." />
      </div>

      <template v-else>
        <div v-if="templates.length > 0">
          <div
            class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
          >
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/30">
                  <tr>
                    <Th align="left" custom-class="py-3 px-3">Name</Th>
                    <Th align="left" custom-class="py-3 px-3">Type</Th>
                    <Th align="center" custom-class="py-3 px-3">Default</Th>
                    <Th align="left" custom-class="py-3 px-3">Subject</Th>
                    <Th align="right" custom-class="py-3 px-3">Actions</Th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr
                    v-for="row in templates"
                    :key="row.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors duration-150"
                  >
                    <Td custom-class="py-3 px-3 font-medium text-gray-900 dark:text-white">{{ row.name }}</Td>
                    <Td custom-class="py-3 px-3 capitalize">{{ row.template_type }}</Td>
                    <Td align="center" custom-class="py-3 px-3">
                      <span v-if="row.is_default" class="text-green-600 dark:text-green-400 font-medium">Yes</span>
                      <span v-else class="text-gray-400">—</span>
                    </Td>
                    <Td custom-class="py-3 px-3 max-w-xs truncate" :title="row.subject || ''">
                      {{ row.subject || '—' }}
                    </Td>
                    <Td align="right" custom-class="py-3 px-3">
                      <div class="flex justify-end gap-2 flex-wrap">
                        <Button
                          v-if="canEdit"
                          type="button"
                          variant="outline-secondary"
                          size="sm"
                          @click="openEdit(row)"
                          icon-left="edit"
                        >
                        </Button>
                        <Button
                          v-if="canDelete"
                          type="button"
                          variant="danger"
                          size="sm"
                          :disabled="deletingId === row.id"
                          @click="confirmDelete(row)"
                          icon-left="trash"
                        >
                        </Button>
                      </div>
                    </Td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="mt-4">
            <Pagination
              :collection="pagination"
              :loading="loading"
              :limit="perPage"
              @page-change="onPageChange"
            />
          </div>
        </div>

        <div
          v-else
          class="text-center py-16 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
        >
          <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
            <template v-if="filterTemplateType || filterSubject.trim()">
              No templates match your filters.
            </template>
            <template v-else>
              No email templates yet. Add one to get started.
            </template>
          </p>
        </div>
      </template>
    </template>

    <Modal
      v-model="showModal"
      :title="modalTitle"
      size="4xl"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Close"
      :confirm-text="saving ? 'Saving…' : 'Save'"
      :loading="saving"
      :close-on-backdrop="false"
      @confirm="saveForm"
      @close="closeModal"
    >
      <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1 pl-1">
        <Input v-model="form.name" label="Template name" required placeholder="e.g. Standard invoice email" />
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">Template type</label>
          <select
            v-model="form.template_type"
            class="w-full sm:max-w-md rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="invoice">Invoice</option>
            <option value="statement">Statement</option>
          </select>
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 cursor-pointer">
          <input
            v-model="form.is_default"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
          />
          Default for this type (only one default per type)
        </label>
        <div>
          <Input
            v-model="form.subject"
            label="Subject"
            maxlength="500"
            :spellcheck="false"
            placeholder="{{customer_name}}"
          />
          <PlaceholderChips class="mt-2" :items="modalPlaceholders" @insert="(t) => insertToken('subject', t)" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">Body</label>
          <textarea
            ref="bodyTextareaRef"
            v-model="form.body"
            rows="10"
            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-2 font-mono  "
            placeholder="Dear {{customer_name}}, …"
            :spellcheck="false"
          />
          <PlaceholderChips class="mt-2" :items="modalPlaceholders" @insert="(t) => insertToken('body', t)" />
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import axios from '../../../plugins/axios'
import Input from '@/components/ui/input.vue'
import Button from '@/components/ui/button.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import Spinner from '@/components/ui/spinner.vue'
import Modal from '@/components/common/Modal.vue'
import Pagination from '@/components/ui/pagination.vue'
import PlaceholderChips from '@/upload-portal/components/ar-settings/PlaceholderChips.vue'
import { useUser } from '../../../composables/useUser'
import { useMessage } from '@/composables/useMessage'

const { can } = useUser()
const message = useMessage()

const canView = computed(() => can('upload-portal-ar-email-template', 'index'))
const canAdd = computed(() => can('upload-portal-ar-email-template', 'add'))
const canEdit = computed(() => can('upload-portal-ar-email-template', 'edit'))
const canDelete = computed(() => can('upload-portal-ar-email-template', 'delete'))

const loading = ref(false)
const saving = ref(false)
const deletingId = ref(null)
const templates = ref([])
const filterTemplateType = ref('')
const filterSubject = ref('')
const perPage = ref(25)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
  per_page: 25,
  has_prev: false,
  has_next: false,
})

const buildPagination = (meta) => ({
  current_page: meta.current_page,
  last_page: meta.last_page,
  from: meta.from,
  to: meta.to,
  total: meta.total,
  per_page: meta.per_page,
  has_prev: meta.current_page > 1,
  has_next: meta.current_page < meta.last_page,
})
const defaultPlaceholdersByType = () => ({
  invoice: [
    { key: 'invoice_number', token: '{{invoice_number}}', label: 'Invoice number' },
    { key: 'customer_name', token: '{{customer_name}}', label: 'Customer name' },
    { key: 'due_days', token: '{{due_days}}', label: 'Due days (signed vs today)' },
    { key: 'due_date', token: '{{due_date}}', label: 'Due date' },
    { key: 'invoice_date', token: '{{invoice_date}}', label: 'Invoice date' },
    { key: 'invoice_amount', token: '{{invoice_amount}}', label: 'Invoice amount' },
  ],
  statement: [
    { key: 'customer_name', token: '{{customer_name}}', label: 'Customer name' },
    { key: 'statement_start', token: '{{statement_start}}', label: 'Statement start date' },
    { key: 'statement_end', token: '{{statement_end}}', label: 'Statement end date' },
    { key: 'opening_balance', token: '{{opening_balance}}', label: 'Opening balance' },
    { key: 'closing_balance', token: '{{closing_balance}}', label: 'Closing balance' },
  ],
})

const placeholdersByType = ref(defaultPlaceholdersByType())

const modalPlaceholders = computed(() => {
  const t = form.template_type === 'statement' ? 'statement' : 'invoice'
  return placeholdersByType.value[t] ?? placeholdersByType.value.invoice
})

const showModal = ref(false)
const editingId = ref(null)
const subjectInputRef = ref(null)
const bodyTextareaRef = ref(null)

const modalTitle = computed(() => (editingId.value ? 'Edit email template' : 'New email template'))

const form = reactive({
  name: '',
  template_type: 'invoice',
  is_default: false,
  subject: '',
  body: '',
})

const resetForm = () => {
  form.name = ''
  form.template_type = 'invoice'
  form.is_default = false
  form.subject = ''
  form.body = ''
  editingId.value = null
}

const listParams = (page) => {
  const params = {
    page,
    per_page: perPage.value,
  }
  if (filterTemplateType.value) {
    params.template_type = filterTemplateType.value
  }
  const subj = filterSubject.value.trim()
  if (subj) {
    params.subject = subj
  }
  return params
}

const loadTemplates = async (page = 1) => {
  if (!canView.value) return
  loading.value = true
  try {
    const { data } = await axios.get('/upload-portal/api/ar-email-templates', {
      params: listParams(page),
    })
    if (data.success) {
      const payload = data.templates
      const rows = payload?.data ?? payload ?? []
      templates.value = Array.isArray(rows) ? rows : []
      if (payload && typeof payload.current_page === 'number') {
        pagination.value = buildPagination(payload)
        perPage.value = payload.per_page || perPage.value
      } else {
        pagination.value = {
          current_page: 1,
          last_page: 1,
          from: templates.value.length ? 1 : 0,
          to: templates.value.length,
          total: templates.value.length,
          per_page: perPage.value,
          has_prev: false,
          has_next: false,
        }
      }
      if (data.placeholders && typeof data.placeholders === 'object' && !Array.isArray(data.placeholders)) {
        const inv = data.placeholders.invoice
        const stmt = data.placeholders.statement
        if (Array.isArray(inv) && Array.isArray(stmt)) {
          placeholdersByType.value = { invoice: inv, statement: stmt }
        }
      } else if (Array.isArray(data.placeholders) && data.placeholders.length) {
        placeholdersByType.value = {
          invoice: data.placeholders,
          statement: defaultPlaceholdersByType().statement,
        }
      }
      if (templates.value.length === 0 && pagination.value.current_page > 1 && pagination.value.total > 0) {
        await loadTemplates(pagination.value.current_page - 1)
        return
      }
    } else {
      templates.value = []
    }
  } catch (e) {
    templates.value = []
    message.error(e.response?.data?.message || 'Failed to load email templates')
  } finally {
    loading.value = false
  }
}

let subjectFilterTimer = null
const debounceSubjectFilter = () => {
  clearTimeout(subjectFilterTimer)
  subjectFilterTimer = setTimeout(() => applyFilters(), 400)
}

const applyFilters = () => {
  loadTemplates(1)
}

const onPageChange = (page, perPageOverride) => {
  if (perPageOverride != null && Number(perPageOverride) !== perPage.value) {
    perPage.value = Number(perPageOverride)
  }
  loadTemplates(page)
}

const openCreate = () => {
  resetForm()
  showModal.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  form.name = row.name || ''
  form.template_type = row.template_type || 'invoice'
  form.is_default = !!row.is_default
  form.subject = row.subject || ''
  form.body = row.body || ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const insertToken = (field, token) => {
  const el = field === 'subject' ? subjectInputRef.value : bodyTextareaRef.value
  if (!el) {
    if (field === 'subject') {
      form.subject = (form.subject || '') + token
    } else {
      form.body = (form.body || '') + token
    }
    return
  }
  const start = typeof el.selectionStart === 'number' ? el.selectionStart : (form[field] || '').length
  const end = typeof el.selectionEnd === 'number' ? el.selectionEnd : start
  const current = field === 'subject' ? (form.subject || '') : (form.body || '')
  const next = current.slice(0, start) + token + current.slice(end)
  if (field === 'subject') {
    form.subject = next
  } else {
    form.body = next
  }
  const pos = start + token.length
  nextTick(() => {
    el.focus()
    el.setSelectionRange(pos, pos)
  })
}

const saveForm = async () => {
  if (saving.value) return
  if (!form.name?.trim()) {
    message.error('Template name is required')
    return
  }
  saving.value = true
  const reloadPage = editingId.value ? pagination.value.current_page : 1
  try {
    const payload = {
      name: form.name.trim(),
      template_type: form.template_type,
      is_default: !!form.is_default,
      subject: form.subject || null,
      body: form.body || null,
    }
    if (editingId.value) {
      await axios.put(`/upload-portal/api/ar-email-templates/${editingId.value}`, payload)
      message.success('Template updated')
    } else {
      await axios.post('/upload-portal/api/ar-email-templates', payload)
      message.success('Template created')
    }
    showModal.value = false
    resetForm()
    await loadTemplates(reloadPage)
  } catch (e) {
    message.error(e.response?.data?.message || (e.response?.data?.errors ? 'Validation failed' : 'Save failed'))
  } finally {
    saving.value = false
  }
}

const confirmDelete = async (row) => {
  if (!window.confirm(`Delete template "${row.name}"?`)) return
  deletingId.value = row.id
  try {
    await axios.delete(`/upload-portal/api/ar-email-templates/${row.id}`)
    message.success('Template deleted')
    await loadTemplates(pagination.value.current_page)
  } catch (e) {
    message.error(e.response?.data?.message || 'Delete failed')
  } finally {
    deletingId.value = null
  }
}

onMounted(() => {
  loadTemplates()
})
</script>
