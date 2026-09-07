<template>
    <div>
        <Panel :divider="true">
            <template #header>
                <div class="flex items-center justify-between">
                    <h5 class="text-xl font-bold !mb-0">Employee Documents</h5>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="router.back()"
                            icon-left="arrow-left"
                        >
                            Back to Employee
                        </Button>
                    </div>
                </div>
            </template>
            <div v-if="loading" class="py-12 flex justify-center">
                <Spinner size="md" text="Loading documents..." centered />
            </div>
            <div v-else-if="error" class="p-4 text-red-600">
                {{ error }}
            </div>
            <div >
                <form
                v-if="can('employee', 'documents-upload') && !loading"
                    class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900/20"
                    @submit.prevent="createDocument"
                >
                    <h6 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Add Document</h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <Input
                                v-model.trim="newDocumentName"
                                label="Document Name"
                                placeholder="e.g. Passport, Contract, Tax Form"
                                :error="uploadErrors.document_name ? uploadErrors.document_name[0] : null"
                                icon-left="files"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <DynamicDropdown
                                label="Document Type"
                                v-model="newDocumentType"
                                :custom-options="documentTypeOptions"
                                display-name="label"
                                :required="true"
                                :error="uploadErrors.document_type ? uploadErrors.document_type[0] : null"
                                icon-left="files"
                            />
                        </div>
                        <div>
                            <Input
                                ref="fileInputRef"
                                type="file"
                                label="File"
                                placeholder="Select a file"
                                :error="uploadErrors.file ? uploadErrors.file[0] : null"
                                icon-left="files"
                                @change="onFileChange"
                            />
                        </div>
                        <div class="flex items-end">
                            <Button
                                type="submit"
                                variant="primary"
                                icon-left="plus"
                                :loading="isUploading"
                                :disabled="isUploading"
                            >
                                Add Document
                            </Button>
                        </div>
                    </div>
                </form>
                <table class="w-full border-collapse ">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">#</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Document Name</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Type</th>
                            <!-- <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Created By</th> -->
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Created At</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Updated At</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(doc, index) in documents" :key="doc.id">
                        <tr
                            class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                        >
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ index + 1 }}</td>
                            <td
                                class="py-3 px-4 font-medium text-gray-900 dark:text-gray-100"
                                :class="isImageDocument(doc) && doc.document_path_url && can('employee', 'documents-show') ? 'cursor-pointer text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300' : ''"
                                :title="isImageDocument(doc) && doc.document_path_url ? 'Show preview below' : undefined"
                                @click="toggleImagePreviewRow(doc)"
                            >
                                {{ renderDocumentName(doc.document_name) }}
                            </td>
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                {{ formatDocumentType(doc.document_type) }}
                            </td>
                            <!-- <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                {{ doc.created_by?.name ?? '-' }}
                            </td> -->
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                {{ formatDate(doc.created_at) }}
                            </td>
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                {{ formatDate(doc.updated_at) }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap items-center gap-3">
                                    <a
                                        v-if="doc.document_path_url && can('employee', 'documents-show')"
                                        :href="documentUrl(doc.document_path_url)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                                    >
                                        View / Download
                                    </a>
                                    <span v-else class="text-gray-400 italic">No file</span>
                                    <Button
                                        v-if="can('employee', 'documents-delete')"
                                        type="button"
                                        variant="outline-danger"
                                        size="sm"
                                        icon-left="trash"
                                        :loading="deletingId === doc.id"
                                        :disabled="deletingId !== null"
                                        @click="deleteDocument(doc)"
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-if="expandedPreviewId === doc.id && isImageDocument(doc) && doc.document_path_url && can('employee', 'documents-show')"
                            class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/40"
                        >
                            <td colspan="5" class="py-4 px-4">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    Preview — {{ renderDocumentName(doc.document_name) }}
                                </p>
                                <img
                                    :src="documentUrl(doc.document_path_url)"
                                    :alt="renderDocumentName(doc.document_name) || 'Document preview'"
                                    class="max-h-[min(70vh,36rem)] max-w-full rounded border border-gray-200 dark:border-gray-600 object-contain"
                                />
                            </td>
                        </tr>
                        </template>
                        <tr v-if="documents.length === 0">
                            <td colspan="5" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                No documents found for this employee.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Panel>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Spinner from '@/components/ui/spinner.vue'
import { formatDate } from '@/utils/date'
import Input from '@/components/ui/input.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { usePermission } from '@/composables/usePermission'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import { assignValidatedFile } from '@/utils/documentUpload'

const documentTypeOptions = [
    { id: 'i9_form', label: 'I-9 Form' },
    { id: 'i9_form_unsigned', label: 'I-9 Form Unsigned' },
    { id: 'w4_form', label: 'W-4 Form' },
    { id: 'profile_picture', label: 'Profile Picture' },
    { id: 'list_a', label: 'List A' },
    { id: 'list_b', label: 'List B' },
    { id: 'list_c', label: 'List C' },
    { id: 'authorization', label: 'Authorization' },
    { id: 'other', label: 'Other' },
]
const route = useRoute()
const resource = 'employee/documents'
const message = useMessage()
const { can } = usePermission()
const router = useRouter()
const documents = ref([])
const loading = ref(true)
const error = ref(null)
const newDocumentName = ref('')
const newDocumentType = ref(null)
const newDocumentFile = ref(null)
const isUploading = ref(false)
const fileInputRef = ref(null)
const uploadErrors = ref({})
const deletingId = ref(null)
const expandedPreviewId = ref(null)

const imagePathPattern = /\.(jpe?g|png|gif|webp|bmp|svg)(\?.*)?$/i

function isImageDocument(doc) {
    if (!doc) return false
    const dn = String(doc.document_name || '').toLowerCase()
    if (dn === 'profile_picture') return true
    const raw = String(doc.document_path || doc.document_path_url || '')
    const pathOnly = raw.split('?')[0].toLowerCase()
    return imagePathPattern.test(pathOnly)
}

function toggleImagePreviewRow(doc) {
    if (!isImageDocument(doc) || !doc.document_path_url || !can('employee', 'documents-show')) return
    expandedPreviewId.value = expandedPreviewId.value === doc.id ? null : doc.id
}

function documentUrl(path) {
    if (!path) return null
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path
    }
    return `/storage/${path}`
}

function renderDocumentName(name) {
    if (!name) return null
    let transformedName = name.replaceAll('_', ' ')
    return transformedName.charAt(0).toUpperCase() + transformedName.slice(1)
}

function formatDocumentType(type) {
    if (!type) return '—'
    const found = documentTypeOptions.find((o) => o.id === type)
    return found ? found.label : type.replaceAll('_', ' ')
}

async function fetchDocuments() {
    loading.value = true
    error.value = null
    try {
        const res = await useRequest('get', `${resource}/${route.params.id}`)
        documents.value = res?.collection ?? []
    } catch (e) {
        error.value = e?.response?.data?.message || 'Failed to load documents'
        documents.value = []
    } finally {
        loading.value = false
    }
}

function onFileChange(event) {
    const input = event.target
    assignValidatedFile(input?.files?.[0] || null, (file) => {
        newDocumentFile.value = file
    }, {
        onError: (error) => message.error(error),
        input,
    })
}


async function deleteDocument(doc) {
    if (!doc?.id) return
    if (!confirm(`Delete "${renderDocumentName(doc.document_name) || 'this document'}"? This cannot be undone.`)) {
        return
    }
    deletingId.value = doc.id
    try {
        await useRequest('delete', `${resource}/${doc.id}`)
        message.success('Document deleted.')
        await fetchDocuments()
    } catch (e) {
        message.error(e?.response?.data?.message || 'Failed to delete document')
    } finally {
        deletingId.value = null
    }
}

async function createDocument() {
    uploadErrors.value = {}

    if (!newDocumentName.value) {
        message.error('Document name is required.')
        return
    }
    if (!newDocumentFile.value) {
        message.error('Please select a file.')
        return
    }

    isUploading.value = true
    try {
        const payload = new FormData()
        payload.append('employee_id', route.params.id)
        payload.append('document_name', newDocumentName.value)
        payload.append('document_type', newDocumentType.value.id)
        payload.append('file', newDocumentFile.value)

        await useRequest('post', resource, payload, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })

        message.success('Document added successfully.')
        newDocumentName.value = ''
        newDocumentType.value = null
        newDocumentFile.value = null
        if (fileInputRef.value) fileInputRef.value.value = ''
        await fetchDocuments()
    } catch (e) {
        uploadErrors.value = e?.response?.data?.errors || {}
        message.error(e?.response?.data?.message || 'Failed to add document')
    } finally {
        isUploading.value = false
    }
}

onMounted(fetchDocuments)

defineExpose({
    fetchDocuments,
})
</script>
  
  
  
  
  