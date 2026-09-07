<template>
    <div class="document-table">
        <h6 class="table-title">{{ title }}</h6>
        <table class="doc-table">
            <tbody>
                <tr v-for="(doc, index) in documents" :key="index">
                    <td class="label-col">Document {{ index + 1 }}</td>
                    <td class="value-col">
                        <div class="doc-info">
                            <div class="doc-type">{{ doc.type }}</div>
                            <div v-if="doc.issuingAuthority" class="doc-detail">
                                <span class="detail-label">Issuing Authority:</span>
                                {{ doc.issuingAuthority }}
                            </div>
                            <div v-if="doc.documentNumber" class="doc-detail">
                                <span class="detail-label">Document Number:</span>
                                {{ doc.documentNumber }}
                            </div>
                            <div v-if="doc.expirationDate" class="doc-detail">
                                <span class="detail-label">Expiration Date:</span>
                                {{ formatDate(doc.expirationDate) }}
                            </div>
                        </div>
                    </td>
                </tr>
                <tr v-if="documents.length === 0">
                    <td colspan="2" class="no-data">No documents provided</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { formatDate } from '@/utils/date'

defineProps({
    title: {
        type: String,
        required: true
    },
    documents: {
        type: Array,
        default: () => []
    }
})
</script>

<style scoped>
.document-table {
    margin-bottom: 1.5rem;
}

.table-title {
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.5rem;
}

.doc-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    overflow: hidden;
}

.doc-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.doc-table tr:last-child td {
    border-bottom: none;
}

.label-col {
    width: 150px;
    font-weight: 600;
    color: #374151;
    background: #f9fafb;
}

.value-col {
    background: #fff;
}

.doc-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.doc-type {
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.5rem;
}

.doc-detail {
    font-size: 0.875rem;
    color: #6b7280;
}

.detail-label {
    font-weight: 500;
    color: #374151;
}

.no-data {
    text-align: center;
    color: #9ca3af;
    font-style: italic;
}
</style>
