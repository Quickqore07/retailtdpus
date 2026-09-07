<template>
  <div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-start mb-8">
      <div>
        <h4 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 !mb-1">
          {{ pageTitle }}
        </h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 !mb-0">
          {{ pageDescription }}
        </p>
      </div>
      <Button
        v-if="can('upload-portal', 'add')"
        @click="openAddModal"
        variant="primary"
        size="sm"
        icon-left="plus"
        icon-size="md"
      >
        Add Document
      </Button>
    </div>

    <!-- Filters Section -->
    <div
      class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 mb-6"
    >
      <div
        class="grid grid-cols-1 md:grid-cols-2 gap-4"
        :class="
          isAllDocumentsView
            ? 'lg:grid-cols-5'
            : isInvoiceFolderView
            ? 'lg:grid-cols-6'
            : 'lg:grid-cols-4'
        "
      >
        <!-- Folder Filter (Only for All Documents view) -->
        <DynamicDropdown
          v-if="isAllDocumentsView"
          v-model="filters.folder"
          label="Filter by Folder"
          :custom-options="allFolders"
          display-name="name"
          placeholder="All Folders"
          :required="false"
          @change="applyFilters"
        />

        <!-- Company Filter -->
        <DynamicDropdown
          v-model="filters.company"
          label="Filter by Company"
          :custom-options="allCompanies"
          display-name="name"
          placeholder="All Companies"
          :required="false"
          @change="applyFilters"
        />

        <!-- Name Filter -->
        <Input
          v-model="filters.name"
          label="Filter by Name"
          placeholder="Search by name..."
          @input="debounceFilter"
        />

        <!-- Date From Filter -->
        <Input
          v-model="filters.dateFrom"
          label="Date From"
          type="date"
          @change="applyFilters"
        />

        <!-- Date To Filter -->
        <Input
          v-model="filters.dateTo"
          label="Date To"
          type="date"
          @change="applyFilters"
        />

        <!-- Due Date Filter (Only for Invoice folders) -->
        <Input
          v-if="isInvoiceFolderView"
          v-model="filters.dueDateFrom"
          label="Due Date From"
          type="date"
          @change="applyFilters"
        />
        <Input
          v-if="isInvoiceFolderView"
          v-model="filters.dueDateTo"
          label="Due Date To"
          type="date"
          @change="applyFilters"
        />
      </div>

      <!-- Clear Filters Button -->
      <div class="flex justify-end mt-4">
        <Button @click="clearFilters" variant="outline-secondary" size="sm">
          Clear Filters
        </Button>
      </div>
    </div>

    <!-- Loading State -->

    <!-- Invoice Status Tabs -->
    <div
      v-if="isInvoiceFolderView"
      class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-3 mb-4"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
          <Button
            v-for="tab in invoiceStatusTabs"
            :key="tab.value"
            @click="setInvoiceStatusTab(tab.value)"
            :variant="
              activeInvoiceStatus === tab.value
                ? 'primary'
                : 'outline-secondary'
            "
            size="sm"
          >
            {{ tab.label }}
          </Button>
        </div>
        <Button
          v-if="
            canSelectInvoicesForPayment && can('upload-portal', 'create-check')
          "
          @click="openPaySelectedInvoices"
          :disabled="selectedInvoiceIds.length === 0"
          variant="primary"
          size="sm"
        >
          Pay Selected ({{ selectedInvoiceIds.length }})
        </Button>
      </div>
    </div>
    <div
      v-if="isCheckFolderView && !loading && documents.length > 0"
      class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-3 mb-4 flex flex-wrap items-center justify-end gap-3"
    >
      <Button
        @click="printSelectedChecksPdf"
        :disabled="!selectedCheckIds?.length"
        :loading="printingChecksPdf"
        variant="primary"
        size="sm"
        icon-left="print"
        icon-size="md"
      >
        Print Selected PDF ({{ selectedCheckIds.length }})
      </Button>
    </div>
    <div v-if="loading" class="flex flex-col items-center justify-center py-20">
      <Spinner size="lg" text="Loading documents..." />
    </div>
    <!-- Documents Table -->
    <div
      v-else-if="documents.length > 0"
      class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
    >
      <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
        <table class="w-full">
          <thead class="border-b border-gray-200 dark:border-gray-700">
            <tr>
              <Th
                v-if="showDocumentBulkSelectColumn"
                align="left"
                custom-class="py-4"
              >
                <input
                  v-if="
                    canSelectInvoicesForPayment &&
                    can('upload-portal', 'create-check')
                  "
                  type="checkbox"
                  :checked="allCurrentInvoicesSelected"
                  :disabled="documents.length === 0"
                  @change="toggleSelectAllInvoices"
                />
                <input
                  v-else-if="canSelectChecksForPrint"
                  type="checkbox"
                  :checked="allCurrentChecksSelected"
                  :disabled="!documents.some((d) => isPdfDocument(d))"
                  @change="toggleSelectAllChecks"
                />
              </Th>
              <Th align="left" custom-class="py-4">
                <button
                  @click="handleSort('name')"
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Document
                  <svg-icon
                    v-if="sort.sort_by === 'name'"
                    :name="
                      sort.sort_direction === 'asc'
                        ? 'chevron-up'
                        : 'chevron-down'
                    "
                    size="xs"
                  />
                </button>
              </Th>
              <Th v-if="isAllDocumentsView" align="left" custom-class="py-4">
                <button
                  @click="handleSort('folder_id')"
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Folder
                  <svg-icon
                    v-if="sort.sort_by === 'folder_id'"
                    :name="
                      sort.sort_direction === 'asc'
                        ? 'chevron-up'
                        : 'chevron-down'
                    "
                    size="xs"
                  />
                </button>
              </Th>
              <Th align="left" custom-class="py-4">
                <button
                  @click="handleSort('company_id')"
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Company
                  <svg-icon
                    v-if="sort.sort_by === 'company_id'"
                    :name="
                      sort.sort_direction === 'asc'
                        ? 'chevron-up'
                        : 'chevron-down'
                    "
                    size="xs"
                  />
                </button>
              </Th>
              <Th
                align="left"
                custom-class="py-4"
                v-if="!currentFolder?.is_invoice"
              >
                <button
                  @click="handleSort('file_type')"
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Type
                  <svg-icon
                    v-if="sort.sort_by === 'file_type'"
                    :name="
                      sort.sort_direction === 'asc'
                        ? 'chevron-up'
                        : 'chevron-down'
                    "
                    size="xs"
                  />
                </button>
              </Th>

              <Th align="left" custom-class="py-4" v-else>
                <button
                  @click="handleSort('invoice_date')"
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Invoice Date
                  <svg-icon
                    v-if="sort.sort_by === 'invoice_date'"
                    :name="
                      sort.sort_direction === 'asc'
                        ? 'chevron-up'
                        : 'chevron-down'
                    "
                    size="xs"
                  />
                </button>
              </Th>
              <Th
                align="left"
                custom-class="py-4"
                v-if="!currentFolder?.is_invoice"
              >
                <button
                  @click="handleSort('file_size')"
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Size
                  <svg-icon
                    v-if="sort.sort_by === 'file_size'"
                    :name="
                      sort.sort_direction === 'asc'
                        ? 'chevron-up'
                        : 'chevron-down'
                    "
                    size="xs"
                  />
                </button>
              </Th>
              <Th align="left" custom-class="py-4" v-else>
                <button
                  @click="handleSort('due_date')"
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Due Date
                  <svg-icon
                    v-if="sort.sort_by === 'due_date'"
                    :name="
                      sort.sort_direction === 'asc'
                        ? 'chevron-up'
                        : 'chevron-down'
                    "
                    size="xs"
                  />
                </button>
              </Th>
              <Th
                align="left"
                custom-class="py-4"
                v-if="currentFolder?.is_invoice"
              >
                <button
                  @click="handleSort('overdue_days')"
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Overdue Days
                  <svg-icon
                    v-if="sort.sort_by === 'overdue_days'"
                    :name="
                      sort.sort_direction === 'asc'
                        ? 'chevron-up'
                        : 'chevron-down'
                    "
                    size="xs"
                  />
                </button>
              </Th>
              <Th align="left" custom-class="py-4">
                <button
                  @click="handleSort('created_at')"
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Date
                  <svg-icon
                    v-if="sort.sort_by === 'created_at'"
                    :name="
                      sort.sort_direction === 'asc'
                        ? 'chevron-up'
                        : 'chevron-down'
                    "
                    size="xs"
                  />
                </button>
              </Th>
              <Th align="left" custom-class="py-4">
                <button
                  @click="handleSort('created_by')"
                  class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Uploaded By
                  <svg-icon
                    v-if="sort.sort_by === 'created_by'"
                    :name="
                      sort.sort_direction === 'asc'
                        ? 'chevron-up'
                        : 'chevron-down'
                    "
                    size="xs"
                  />
                </button>
              </Th>
              <Th align="right" custom-class="py-4"> Actions </Th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="document in documents"
              :key="document.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors duration-150"
              :class="{
                'opacity-50 pointer-events-none': deletingId === document.id,
              }"
            >
              <Td v-if="showDocumentBulkSelectColumn" custom-class="py-4">
                <input
                  v-if="
                    canSelectInvoicesForPayment &&
                    can('upload-portal', 'create-check')
                  "
                  type="checkbox"
                  :checked="selectedInvoiceIds.includes(document.id)"
                  @change="toggleInvoiceSelection(document)"
                  :disabled="!isInvoiceDocument(document)"
                />
                <input
                  v-else-if="isPdfDocument(document)"
                  type="checkbox"
                  :checked="selectedCheckIds.includes(document.id)"
                  @change="toggleCheckSelection(document)"
                  :disabled="!isPdfDocument(document)"
                />
              </Td>
              <Td custom-class="py-4">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 rounded-lg bg-gradient-to-br flex items-center justify-center text-white flex-shrink-0"
                    :class="getColorForMimeType(document.file_type)"
                  >
                    <svg-icon
                      :name="getIconForMimeType(document.file_type)"
                      size="sm"
                    />
                  </div>
                  <div class="min-w-0 flex items-center gap-1">
                    <h6 class="truncate !mb-0 max-w-[200px] text-ellipsis">
                      {{ document.name }}
                    </h6>
                    <small
                      v-if="
                        currentFolder?.is_invoice &&
                        document.ap_invoice?.invoice_type == 'auto'
                      "
                      >(Auto)</small
                    >
                  </div>
                </div>
              </Td>
              <Td v-if="isAllDocumentsView" custom-class="py-4">
                <span
                  class="text-sm text-amber-600 dark:text-amber-400 font-medium truncate"
                >
                  {{ document.folder?.name || "N/A" }}
                </span>
              </Td>
              <Td custom-class="py-4">
                <span
                  class="text-sm text-indigo-600 dark:text-indigo-400 font-medium"
                >
                  {{ document.company?.name || "N/A" }}
                </span>
              </Td>
              <Td custom-class="py-4" v-if="!currentFolder?.is_invoice">
                <span class="text-xs font-mono">{{
                  formatMimeType(document.file_type)
                }}</span>
              </Td>

              <Td custom-class="py-4" v-else
                >{{ formatDate(document.ap_invoice?.date) }}   
              </Td>
              <Td custom-class="py-4" v-if="!currentFolder?.is_invoice">
                {{ formatFileSize(document.file_size) }}
              </Td>
              <Td custom-class="py-4" v-else>
                {{ formatDate(document.ap_invoice?.due_date) }}
              </Td>
              <Td custom-class="py-4" v-if="currentFolder?.is_invoice">
                <span class="text-red-500">
                  {{
                    renderDueDays(document.ap_invoice) > 0
                      ? renderDueDays(document.ap_invoice)
                      : "-"
                  }}
                </span>
              </Td>
              <Td custom-class="py-4">
                {{ formatDate(document.created_at) }}
              </Td>
              <Td custom-class="py-4">
                {{ document.created_by?.name || "N/A" }}
              </Td>
              <Td align="right" custom-class="py-4">
                <div class="flex items-center justify-end">
                  <IconMenuDropdown title="Actions">
                    <template #default="{ close }">
                      <button
                        v-if="
                          checkInvoiceFolder(document) &&
                          document.invoice_status === 'draft' &&
                          can('upload-portal', 'approve')
                        "
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-emerald-600 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50 dark:text-emerald-400 dark:hover:bg-emerald-900/20"
                        role="menuitem"
                        :disabled="approvingId === document.id"
                        @click="
                          openApproveModal(document);
                          close();
                        "
                      >
                        <Spinner v-if="approvingId === document.id" size="sm" />
                        <SvgIcon v-else name="check-circle" size="sm" />
                        {{
                          approvingId === document.id
                            ? "Approving..."
                            : "Approve"
                        }}
                      </button>
                      <button
                        v-if="
                          isInvoiceDocument(document) &&
                          document.ap_invoice?.invoice_type == 'check' &&
                          can('upload-portal', 'view')
                        "
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        @click="
                          openInvoiceViewModal(document);
                          close();
                        "
                      >
                        <SvgIcon name="eye" size="sm" />
                        View Invoice
                      </button>
                      <button
                        v-if="
                          isCheckDocument(document) &&
                          can('upload-portal', 'view')
                        "
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        @click="
                          openCheckPaymentViewModal(document);
                          close();
                        "
                      >
                        <SvgIcon name="eye" size="sm" />
                        View Check
                      </button>
                      <button
                        v-if="
                          checkInvoiceFolder(document) &&
                          document.ap_invoice?.status === 'approved' &&
                          can('upload-portal', 'create-check')
                        "
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        :disabled="movingToPaidId === document.id"
                        @click="
                          moveInvoiceToPaid(document);
                          close();
                        "
                      >
                        <Spinner
                          v-if="movingToPaidId === document.id"
                          size="sm"
                        />
                        <SvgIcon v-else name="dollar" size="sm" />
                        {{
                          movingToPaidId === document.id
                            ? "Moving..."
                            : "Move to Paid"
                        }}
                      </button>
                      <button
                        v-if="document.editable && (
                          (document.ap_invoice?.status == 'draft' &&
                            can('upload-portal', 'edit')) ||
                          (document.ap_invoice?.status == 'approved' &&
                            can('upload-portal', 'edit-after-approval')) ||
                          document.ap_invoice?.invoice_type == 'auto')
                        "
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        @click="
                          openEditModal(document);
                          close();
                        "
                      >
                        <SvgIcon name="edit" size="sm" />
                        Edit
                      </button>
                      <button
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        role="menuitem"
                        @click="
                          downloadDocument(document);
                          close();
                        "
                      >
                        <SvgIcon name="download" size="sm" />
                        Download
                      </button>
                      <button
                        v-if="can('upload-portal', 'delete') && document.editable"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:text-red-400 dark:hover:bg-red-900/20"
                        role="menuitem"
                        :disabled="deletingId === document.id"
                        @click="
                          deleteDocument(document);
                          close();
                        "
                      >
                        <Spinner v-if="deletingId === document.id" size="sm" />
                        <SvgIcon v-else name="trash" size="sm" />
                        {{
                          deletingId === document.id ? "Deleting..." : "Delete"
                        }}
                      </button>
                    </template>
                  </IconMenuDropdown>
                </div>
              </Td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="documents.length > 0" class="mt-6">
      <Pagination
        :collection="pagination"
        :loading="loading"
        @page-change="onPageChange"
      />
    </div>

    <!-- Message -->
    <div
      v-else-if="message && message.includes('You do not have permission')"
      class="text-center py-20"
    >
      <p class="text-sm text-gray-600 dark:text-gray-400">
        You do not have permission to access this page.
      </p>
    </div>
    <!-- Empty State -->
    <div v-else class="text-center py-20">
      <svg
        class="w-20 h-20 text-gray-300 dark:text-gray-600 mx-auto mb-6"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
      <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-3">
        No documents yet
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400">
        Upload your first document to get started
      </p>
    </div>

    <!-- Upload Modal -->
    <Modal
      v-if="showModal"
      :model-value="showModal"
      @update:model-value="closeModal"
      title="Add Document"
    >
      <div>
        <!-- Modal Body -->
        <form @submit.prevent="handleSubmit" class="space-y-5">
          <!-- Folder Dropdown (Only for All Documents view) -->
          <DynamicDropdown
            v-if="isAllDocumentsView"
            v-model="form.folder"
            label="Folder"
            :custom-options="allFolders"
            display-name="name"
            placeholder="Select folder"
            :required="true"
            @change="onFolderChange"
          />

          <!-- Workgroup Dropdown -->
          <DynamicDropdown
            v-model="form.workgroup"
            label="Workgroup"
            :custom-options="allWorkgroups"
            display-name="name"
            placeholder="Select workgroup"
            :required="true"
            @change="onWorkgroupChange"
          />

          <!-- Company Dropdown -->
          <DynamicDropdown
            v-model="form.company"
            label="Company"
            :custom-options="companies"
            display-name="name"
            placeholder="Select company"
            :required="true"
            :disabled="!form.workgroup || loadingCompanies"
          />

          <!-- Document Name -->
          <Input
            v-model="form.name"
            label="Document Name"
            placeholder="Enter document name"
            :required="true"
          />

          <!-- File Input -->
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5"
            >
              File <span class="text-red-500">*</span>
            </label>
            <input
              type="file"
              @change="onFileChange"
              required
              class="block w-full text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300"
            />
            <p
              v-if="form.file"
              class="text-xs text-gray-600 dark:text-gray-400 !mt-2"
            >
              Selected: {{ form.file.name }}
            </p>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="flex items-center justify-end gap-3 py-4 border-t border-gray-200 dark:border-gray-700 mt-3"
        >
          <Button
            type="button"
            @click="closeModal"
            variant="outline-secondary"
            size="md"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            @click="handleSubmit"
            :disabled="uploading"
            variant="primary"
            size="md"
          >
            {{ uploading ? "Uploading..." : "Upload Document" }}
          </Button>
        </div>
      </div>
    </Modal>

    <!-- Edit Modal -->
    <Modal
      v-if="showEditModal"
      :model-value="showEditModal"
      @update:model-value="closeEditModal"
      title="Edit Document"
    >
      <div>
        <!-- Modal Body -->
        <form @submit.prevent="handleEdit" class="space-y-5">
          <!-- Document Name -->
          <Input
            v-model="editForm.name"
            label="Document Name"
            placeholder="Enter document name"
            :required="true"
          />

          <!-- Company Dropdown -->
          <DynamicDropdown
            v-model="editForm.company"
            label="Company"
            :custom-options="allCompanies"
            display-name="name"
            :disabled="isCheckDocument(editForm)"
            placeholder="Select company"
            :required="true"
          />

          <!-- Info Note -->
          <div
            class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3"
          >
            <p class="text-xs text-blue-700 dark:text-blue-300 !mb-0">
              Note: You can only edit the document name and company. The
              uploaded file cannot be changed.
            </p>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="flex items-center justify-end gap-3 py-4 border-t border-gray-200 dark:border-gray-700 mt-3"
        >
          <Button
            type="button"
            @click="closeEditModal"
            variant="outline-secondary"
            size="md"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            @click="handleEdit"
            :disabled="updating"
            variant="primary"
            size="md"
          >
            {{ updating ? "Updating..." : "Update Document" }}
          </Button>
        </div>
      </div>
    </Modal>

    <!-- Invoice Modal -->
    <InvoiceModal
      v-model="showInvoiceModal"
      :folder-id="folderId"
      :is-all-documents-view="isAllDocumentsView"
      :all-folders="allFolders"
      :document="invoiceEditDocument"
      @success="onInvoiceUploadSuccess"
    />

    <InvoiceViewModal
      v-model="showInvoiceViewModal"
      :document="invoiceViewDocument"
    />

    <CheckPaymentCreateModal
      v-model="showCheckPaymentCreateModal"
      :folder-id="checkPaymentFolderId || folderId"
      :prefill-data="checkPaymentPrefillData"
      @success="onInvoiceUploadSuccess"
    />

    <CheckPaymentEditModal
      v-model="showCheckPaymentEditModal"
      :document="checkPaymentEditDocument"
      @success="onInvoiceUploadSuccess"
    />

    <CheckPaymentViewModal
      v-model="showCheckPaymentViewModal"
      :document="checkPaymentViewDocument"
    />

    <!-- Approval Acknowledgment Modal -->
    <Modal
      v-if="showApproveModal"
      :model-value="showApproveModal"
      @update:model-value="closeApproveModal"
      title="Invoice Approval Acknowledgment"
    >
      <div>
        <div class="space-y-4">
          <div
            class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3"
          >
            <p class="text-sm text-amber-800 dark:text-amber-200 !mb-0">
              You are about to approve invoice
              <span class="font-semibold">"{{ approveDocument?.name }}"</span>.
              This action cannot be undone.
            </p>
          </div>

          <div v-if="approveDocument?.ap_invoice?.invoice_type === 'check'">
            <Input
              label="Original Amount"
              v-model="approveForm.original_amount"
              placeholder="Enter the approved amount"
              :required="true"
            />
            <Input
              label="Approved Amount"
              v-model="approveForm.approved_amount"
              placeholder="Enter the approved amount"
              :required="true"
            />
          </div>

          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5"
            >
              Approval Remarks <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="approveForm.remarks"
              rows="4"
              placeholder="Enter your remarks for approving this invoice..."
              class="block w-full text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500"
            ></textarea>
          </div>

          <label class="flex items-start gap-2 cursor-pointer select-none">
            <input
              type="checkbox"
              v-model="approveForm.acknowledged"
              class="mt-1"
            />
            <span class="text-sm text-gray-700 dark:text-gray-200">
              I have reviewed this invoice in full and certify that all charges
              are accurate, properly supported, and approved for payment.
            </span>
          </label>
        </div>

        <div
          class="flex items-center justify-end gap-3 py-4 border-t border-gray-200 dark:border-gray-700 mt-4"
        >
          <Button
            type="button"
            @click="closeApproveModal"
            variant="outline-secondary"
            size="md"
            :disabled="approvingId === approveDocument?.id"
          >
            Cancel
          </Button>
          <Button
            type="button"
            @click="submitApproveInvoice"
            :disabled="
              !canSubmitApproval || approvingId === approveDocument?.id
            "
            variant="primary"
            size="md"
          >
            {{
              approvingId === approveDocument?.id ? "Approving..." : "Approve"
            }}
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "../../plugins/axios";
import Input from "@/components/ui/input.vue";
import DynamicDropdown from "@/components/ui/dynamic-dropdown.vue";
import SvgIcon from "@/components/SvgIcon.vue";
import Th from "@/components/ui/th.vue";
import Td from "@/components/ui/td.vue";
import Button from "@/components/ui/button.vue";
import IconMenuDropdown from "@/components/ui/IconMenuDropdown.vue";
import Modal from "@/components/common/Modal.vue";
import InvoiceModal from "../../components/InvoiceModal.vue";
import InvoiceViewModal from "../../components/InvoiceViewModal.vue";
import CheckPaymentCreateModal from "../../components/CheckPaymentCreateModal.vue";
import CheckPaymentEditModal from "../../components/CheckPaymentEditModal.vue";
import CheckPaymentViewModal from "../../components/CheckPaymentViewModal.vue";
import Pagination from "@/components/ui/pagination.vue";
import Spinner from "@/components/ui/spinner.vue";
import {
  getIconForMimeType,
  getColorForMimeType,
} from "@/utils/mimeTypeIcons.js";
import { assignValidatedFile } from "@/utils/documentUpload";
import { useUser } from "../../composables/useUser";
import { useMessage } from "@/composables/useMessage";
import { formatDate } from "@/utils/date";
export default {
  name: "Documents",
  components: {
    Input,
    DynamicDropdown,
    SvgIcon,
    Th,
    Td,
    Button,
    IconMenuDropdown,
    Modal,
    InvoiceModal,
    InvoiceViewModal,
    CheckPaymentCreateModal,
    CheckPaymentEditModal,
    CheckPaymentViewModal,
    Pagination,
    Spinner,
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const { can } = useUser();
    const messageAlert = useMessage();
    const folderId = ref(route.params.folderId);
    const folderName = ref("Documents");
    const documents = ref([]);
    const loading = ref(false);
    const showModal = ref(false);
    const showInvoiceModal = ref(false);
    const showInvoiceViewModal = ref(false);
    const showCheckPaymentCreateModal = ref(false);
    const showCheckPaymentEditModal = ref(false);
    const showCheckPaymentViewModal = ref(false);
    const invoiceEditDocument = ref(null);
    const invoiceViewDocument = ref(null);
    const checkPaymentEditDocument = ref(null);
    const checkPaymentViewDocument = ref(null);
    const selectedInvoiceIds = ref([]);
    const selectedCheckIds = ref([]);
    const printingChecksPdf = ref(false);
    const checkPaymentFolderId = ref(null);
    const checkPaymentPrefillData = ref(null);
    const showEditModal = ref(false);
    const uploading = ref(false);
    const updating = ref(false);
    const deletingId = ref(null);
    const approvingId = ref(null);
    const movingToPaidId = ref(null);
    const message = ref("");
    const showApproveModal = ref(false);
    const approveDocument = ref(null);
    const approveForm = ref({
      acknowledged: false,
      remarks: "",
      approved_amount: 0,
      original_amount: 0,
    });
    const canSubmitApproval = computed(
      () =>
        approveForm.value.acknowledged &&
        approveForm.value.remarks.trim().length > 0
    );

    const allWorkgroups = ref([]);
    const companies = ref([]);
    const allCompanies = ref([]);
    const allFolders = ref([]);
    const loadingCompanies = ref(false);
    const currentFolder = ref(null);
    const activeInvoiceStatus = ref("draft");

    const isAllDocumentsView = computed(() =>
      route.params.folderId ? false : true
    );
    const isInvoiceFolderView = computed(
      () => !isAllDocumentsView.value && currentFolder.value?.is_invoice == 1
    );
    const canSelectInvoicesForPayment = computed(
      () =>
        isInvoiceFolderView.value && activeInvoiceStatus.value === "approved"
    );
    const onFolderChange = () => {
      folderId.value = form.value.folder.id;
    };
    const allCurrentInvoicesSelected = computed(
      () =>
        canSelectInvoicesForPayment.value &&
        documents.value.length > 0 &&
        documents.value.every((document) =>
          selectedInvoiceIds.value.includes(document.id)
        )
    );

    const isCheckFolderView = computed(() => {
      if (
        !isAllDocumentsView.value &&
        Number(currentFolder.value?.is_check) === 1
      ) {
        return true;
      }
      if (
        isAllDocumentsView.value &&
        filters.value.folder &&
        Number(filters.value.folder.is_check) === 1
      ) {
        return true;
      }
      return false;
    });
    const canSelectChecksForPrint = computed(() => isCheckFolderView.value);
    const showDocumentBulkSelectColumn = computed(
      () =>
        (canSelectInvoicesForPayment.value &&
          can("upload-portal", "create-check")) ||
        canSelectChecksForPrint.value
    );
    const allCurrentChecksSelected = computed(() => {
      if (!canSelectChecksForPrint.value) return false;
      const checkRows = documents.value.filter(
        (d) => d?.file_type == "application/pdf"
      );
      return (
        checkRows.length > 0 &&
        checkRows.every((d) => selectedCheckIds.value.includes(d.id))
      );
    });

    const checkInvoiceFolder = (document) => {
      return document?.folder?.is_invoice == 1;
    };
    const invoiceStatusTabs = [
      { label: "Draft", value: "draft" },
      { label: "Approved", value: "approved" },
      { label: "Paid", value: "paid" },
    ];

    const pageTitle = computed(() =>
      isAllDocumentsView.value ? "All Documents" : folderName.value
    );

    const pageDescription = computed(() =>
      isAllDocumentsView.value
        ? "Upload and manage all your documents"
        : "Upload and manage your documents"
    );

    const filters = ref({
      folder: null,
      company: null,
      name: "",
      dateFrom: "",
      dateTo: "",
      dueDateFrom: "",
      dueDateTo: "",
    });

    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 25,
      total: 0,
      from: 0,
      to: 0,
      has_prev: false,
      has_next: false,
    });

    const sort = ref({
      sort_by: "created_at",
      sort_direction: "desc",
    });

    let filterDebounce = null;

    const form = ref({
      folder: null,
      workgroup: null,
      company: null,
      name: "",
      file: null,
    });

    const editForm = ref({
      id: null,
      name: "",
      company: null,
      is_check: false,
    });

    const loadDocuments = async (page = 1, perPage = 25, isSort = false) => {
      if (!isSort) {
        loading.value = true;
      }
      try {
        const params = {
          page,
          per_page: perPage,
        };

        filters.value.folder?.id &&
          (params.folder_id = filters.value.folder.id);
        filters.value.company?.id &&
          (params.company_id = filters.value.company.id);
        filters.value.name && (params.name = filters.value.name);
        filters.value.dateFrom && (params.date_from = filters.value.dateFrom);
        filters.value.dateTo && (params.date_to = filters.value.dateTo);
        filters.value.dueDateFrom &&
          (params.due_date_from = filters.value.dueDateFrom);
        filters.value.dueDateTo &&
          (params.due_date_to = filters.value.dueDateTo);
        isInvoiceFolderView.value &&
          (params.status = activeInvoiceStatus.value);
        sort.value.sort_by && (params.sort_by = sort.value.sort_by);
        sort.value.sort_direction &&
          (params.sort_direction = sort.value.sort_direction);

        let endpoint = isAllDocumentsView.value
          ? "/upload-portal/api/documents"
          : `/upload-portal/api/folders/${folderId.value}/documents`;

        const response = await axios.get(endpoint, { params });
        if (response.data.success) {
          documents.value = response.data.documents || [];
          if (canSelectInvoicesForPayment.value) {
            const currentIds = new Set(documents.value.map((item) => item.id));
            selectedInvoiceIds.value = selectedInvoiceIds.value.filter((id) =>
              currentIds.has(id)
            );
          } else {
            selectedInvoiceIds.value = [];
          }
          if (canSelectChecksForPrint.value) {
            const currentIds = new Set(documents.value.map((item) => item.id));
            selectedCheckIds.value = selectedCheckIds.value.filter((id) =>
              currentIds.has(id)
            );
          } else {
            selectedCheckIds.value = [];
          }

          if (!isAllDocumentsView.value) {
            folderName.value = response.data.folder?.name || "Documents";
          }

          if (response.data.pagination) {
            pagination.value = response.data.pagination;
          }
        }
      } catch (error) {
        message.value =
          error.response?.data?.message ||
          "Error loading documents. Please try again.";
        console.error("Error loading documents:", error);
      } finally {
        loading.value = false;
      }
    };

    const loadFolders = async () => {
      try {
        const response = await axios.get("/upload-portal/api/folders");
        if (response.data.success) {
          allFolders.value = response.data.folders || [];
        }
      } catch (error) {
        console.error("Error loading folders:", error);
      }
    };

    const loadWorkgroups = async () => {
      try {
        const endpoint = "/api/search/document-workgroups";

        const params = isAllDocumentsView.value
          ? { query: "", column: "name", all: true }
          : { query: "", column: "name" };

        const response = await axios.get(endpoint, { params });

        allWorkgroups.value = response.data?.collection || [];
      } catch (error) {
        console.error("Error loading workgroups:", error);
      }
    };

    const loadAllCompanies = async () => {
      try {
        const response = await axios.get("/upload-portal/api/all-companies");

        allCompanies.value = response.data?.companies || [];
      } catch (error) {
        console.error("Error loading companies:", error);
      }
    };

    const loadCompaniesByWorkgroup = async (workgroup) => {
      if (!workgroup || !workgroup.id) {
        companies.value = [];
        return;
      }

      loadingCompanies.value = true;
      try {
        const resource = "upload-companies";
        const response = await axios.get(`/api/search/${resource}`, {
          params: {
            query: "",
            column: "name",
            workgroup_id: workgroup.id,
          },
        });

        if (response.data) {
          companies.value = (
            response.data.collection ||
            response.data ||
            []
          ).map((company) => ({
            ...company,
            type: workgroup.type,
          }));
        }
      } catch (error) {
        console.error("Error loading companies:", error);
        companies.value = [];
      } finally {
        loadingCompanies.value = false;
      }
    };

    const onWorkgroupChange = () => {
      form.value.company = null;
      companies.value = [];
      if (form.value.workgroup) {
        loadCompaniesByWorkgroup(form.value.workgroup);
      }
    };

    const onFileChange = (event) => {
      const input = event.target;
      assignValidatedFile(input?.files?.[0] || null, (file) => {
        form.value.file = file;
      }, {
        onError: (error) => messageAlert.error(error),
        input,
      });
    };

    const handleSubmit = async () => {
      if (isAllDocumentsView.value && !form.value.folder) {
        alert("Please fill in all required fields");
        return;
      }

      if (
        !form.value.workgroup ||
        !form.value.company ||
        !form.value.name ||
        !form.value.file
      ) {
        alert("Please fill in all required fields");
        return;
      }

      uploading.value = true;
      try {
        const formData = new FormData();

        const folderIdToUse = isAllDocumentsView.value
          ? form.value.folder.id
          : folderId.value;
        formData.append("folder_id", folderIdToUse);
        formData.append("name", form.value.name);
        formData.append("file", form.value.file);
        formData.append("company_id", form.value.company.id);

        const response = await axios.post(
          "/upload-portal/api/documents",
          formData,
          {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          }
        );

        if (response.data.success) {
          closeModal();
          loadDocuments();
        }
      } catch (error) {
        console.error("Error uploading document:", error);
        alert("Error uploading document. Please try again.");
      } finally {
        uploading.value = false;
      }
    };

    const closeModal = () => {
      showModal.value = false;
      form.value = {
        folder: null,
        workgroup: null,
        company: null,
        name: "",
        file: null,
      };
      companies.value = [];
    };

    const formatFileSize = (bytes) => {
      if (!bytes || bytes === 0) return "N/A";
      const units = ["B", "KB", "MB", "GB"];
      let size = bytes;
      let unitIndex = 0;
      while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
      }
      return `${size.toFixed(2)} ${units[unitIndex]}`;
    };

    const formatMimeType = (mimeType) => {
      if (!mimeType) return "N/A";
      const typeMap = {
        "application/pdf": "PDF",
        "application/msword": "DOC",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
          "DOCX",
        "application/vnd.ms-excel": "XLS",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
          "XLSX",
        "image/jpeg": "IMAGE",
        "image/jpg": "IMAGE",
        "image/png": "IMAGE",
        "image/gif": "IMAGE",
        "text/plain": "TXT",
        "application/zip": "ZIP",
        "application/x-rar-compressed": "RAR",
      };
      return (
        typeMap[mimeType] || mimeType.split("/")[1]?.toUpperCase() || "FILE"
      );
    };

    const downloadDocument = async (document) => {
      try {
        const response = await axios.get(
          `/upload-portal/api/documents/${document.id}/download`
        );
        if (response.data.success) {
          window.open(response.data.url, "_blank");
        }
      } catch (error) {
        console.error("Error downloading document:", error);
        alert("Error downloading document. Please try again.");
      }
    };

    const applyFilters = () => {
      pagination.value.current_page = 1;
      loadDocuments(1, pagination.value.per_page);
    };

    const clearFilters = () => {
      filters.value = {
        folder: null,
        company: null,
        name: "",
        dateFrom: "",
        dateTo: "",
        dueDateFrom: "",
        dueDateTo: "",
      };
      pagination.value.current_page = 1;
      loadDocuments(1, pagination.value.per_page);
    };

    const debounceFilter = () => {
      if (filterDebounce) {
        clearTimeout(filterDebounce);
      }
      filterDebounce = setTimeout(() => {
        applyFilters();
      }, 500);
    };

    const onPageChange = (page, perPage) => {
      loadDocuments(page, perPage);
    };

    const handleSort = (column) => {
      if (sort.value.sort_by === column) {
        sort.value.sort_direction =
          sort.value.sort_direction === "asc" ? "desc" : "asc";
      } else {
        sort.value.sort_by = column;
        sort.value.sort_direction = "asc";
      }
      pagination.value.current_page = 1;
      loadDocuments(1, pagination.value.per_page, true);
    };

    const setInvoiceStatusTab = (status) => {
      if (activeInvoiceStatus.value === status) return;
      activeInvoiceStatus.value = status;
      selectedInvoiceIds.value = [];
      pagination.value.current_page = 1;
      loadDocuments(1, pagination.value.per_page);
    };

    const getDocumentVendorId = (document) =>
      Number(
        document?.ap_invoice?.qq_vendor_id ||
          document?.apInvoice?.qq_vendor_id ||
          0
      );

    const getDocumentBillId = (document) =>
      Number(document?.ap_invoice?.id || document?.apInvoice?.id || 0);

    const getDocumentBillAmount = (document) => {
      const amount =
        document?.ap_invoice?.amount || document?.apInvoice?.amount || 0;
      return Number(amount || 0);
    };

    const toggleInvoiceSelection = (document) => {
      const id = document.id;
      if (selectedInvoiceIds.value.includes(id)) {
        selectedInvoiceIds.value = selectedInvoiceIds.value.filter(
          (item) => item !== id
        );
        return;
      }
      selectedInvoiceIds.value.push(id);
    };

    const toggleSelectAllInvoices = () => {
      if (allCurrentInvoicesSelected.value) {
        selectedInvoiceIds.value = [];
        return;
      }
      selectedInvoiceIds.value = documents.value.map((document) => document.id);
    };

    const toggleCheckSelection = (document) => {
      const id = document.id;
      if (selectedCheckIds.value.includes(id)) {
        selectedCheckIds.value = selectedCheckIds.value.filter(
          (item) => item !== id
        );
        return;
      }
      selectedCheckIds.value.push(id);
    };

    const toggleSelectAllChecks = () => {
      const checkRowIds = documents.value
        .filter((d) => d?.file_type == "application/pdf")
        .map((d) => d.id);
      if (!checkRowIds.length) return;
      if (checkRowIds.every((id) => selectedCheckIds.value.includes(id))) {
        const idSet = new Set(checkRowIds);
        selectedCheckIds.value = selectedCheckIds.value.filter(
          (item) => !idSet.has(item)
        );
        return;
      }
      selectedCheckIds.value = [
        ...new Set([...selectedCheckIds.value, ...checkRowIds]),
      ];
    };

    const printSelectedChecksPdf = async () => {
      if (!selectedCheckIds.value.length) {
        messageAlert.error("Please select at least one check");
        return;
      }
      printingChecksPdf.value = true;
      try {
        const response = await axios.post(
          "/upload-portal/api/documents/print-checks-pdf",
          { document_ids: selectedCheckIds.value },
          { responseType: "blob" }
        );
        const blob = response.data;
        if (
          blob instanceof Blob &&
          blob.type &&
          blob.type.includes("application/json")
        ) {
          const text = await blob.text();
          const json = JSON.parse(text);
          messageAlert.error(json.message || "Could not generate PDF");
          return;
        }
        const url = window.URL.createObjectURL(
          blob instanceof Blob ? blob : new Blob([blob])
        );
        const link = document.createElement("a");
        link.href = url;
        link.download = `checks-${new Date().toISOString().slice(0, 10)}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        messageAlert.success("PDF download started");
        selectedCheckIds.value = [];
      } catch (error) {
        console.error("Error printing checks PDF:", error);
        const msg = error.response?.data?.message;
        if (msg) {
          messageAlert.error(msg);
        } else if (error.response?.data instanceof Blob) {
          try {
            const text = await error.response.data.text();
            const json = JSON.parse(text);
            messageAlert.error(json.message || "Could not generate PDF");
          } catch {
            messageAlert.error("Could not generate PDF");
          }
        } else {
          messageAlert.error("Could not generate PDF. Please try again.");
        }
      } finally {
        printingChecksPdf.value = false;
      }
    };

    const resolveCheckPaymentFolderId = async () => {
      if (checkPaymentFolderId.value) return checkPaymentFolderId.value;
      if (!allFolders.value.length) {
        await loadFolders();
      }
      const checkFolder =
        allFolders.value.find((folder) => Number(folder.is_check) === 1) ||
        null;
      checkPaymentFolderId.value = checkFolder?.id || null;
      return checkPaymentFolderId.value;
    };

    const openPaySelectedInvoices = async () => {
      const selectedDocuments = documents.value.filter((document) =>
        selectedInvoiceIds.value.includes(document.id)
      );
      if (!selectedDocuments.length) {
        messageAlert.error("Please select at least one invoice");
        return;
      }

      const companyIds = new Set(
        selectedDocuments.map((document) => Number(document.company_id))
      );
      const vendorIds = new Set(
        selectedDocuments
          .map((document) => getDocumentVendorId(document))
          .filter((id) => id > 0)
      );
      if (companyIds.size !== 1 || vendorIds.size !== 1) {
        messageAlert.error(
          "Please select invoices with same company and vendor"
        );
        return;
      }

      const billIds = selectedDocuments
        .map((document) => getDocumentBillId(document))
        .filter((id) => id > 0);
      if (!billIds.length) {
        messageAlert.error("Selected invoices are missing bill details");
        return;
      }

      const paymentFolderId = await resolveCheckPaymentFolderId();
      if (!paymentFolderId) {
        messageAlert.error("Check payment folder is not configured");
        return;
      }

      const billAmounts = {};
      selectedDocuments.forEach((document) => {
        const billId = getDocumentBillId(document);
        if (billId > 0) {
          billAmounts[billId] = getDocumentBillAmount(document);
        }
      });
      const selectedCompanyId = Number(selectedDocuments[0].company_id);
      const companyMeta =
        allCompanies.value.find(
          (company) => Number(company.id) === selectedCompanyId
        ) || null;

      checkPaymentPrefillData.value = {
        company: selectedDocuments[0].company,
        company_id: selectedDocuments[0].company_id,
        workgroup_id:
          selectedDocuments[0].company?.workgroup_id ||
          companyMeta?.workgroup_id ||
          null,
        workgroup_name: selectedDocuments[0].company?.workgroup?.name || null,
        vendor_id: Array.from(vendorIds)[0],
        bill_ids: billIds,
        bill_amounts: billAmounts,
      };

      showCheckPaymentCreateModal.value = true;
    };

    const openApproveModal = (document) => {
      approveDocument.value = document;
      approveForm.value = {
        acknowledged: false,
        remarks: "",
        approved_amount: document?.ap_invoice?.approved_amount || 0,
        original_amount: document?.ap_invoice?.amount || 0,
      };
      showApproveModal.value = true;
    };

    const closeApproveModal = () => {
      if (approvingId.value === approveDocument.value?.id) return;
      showApproveModal.value = false;
      approveDocument.value = null;
      approveForm.value = {
        acknowledged: false,
        remarks: "",
        approved_amount: 0,
        original_amount: 0,
      };
    };

    const submitApproveInvoice = async () => {
      if (!approveDocument.value) return;
      if (!canSubmitApproval.value) {
        messageAlert.error("Please acknowledge and provide approval remarks");
        return;
      }
      if (
        approveForm.value.approved_amount >
        approveDocument.value.ap_invoice.amount
      ) {
        messageAlert.error(
          "Approved amount is greater than the invoice amount"
        );
        return;
      }

      // console.log(approveForm.value)
      // return
      const document = approveDocument.value;
      approvingId.value = document.id;
      try {
        const response = await axios.post(
          `/upload-portal/api/ap-invoices/${document.id}/approve`,
          {
            acknowledged: approveForm.value.acknowledged,
            approval_remarks: approveForm.value.remarks.trim(),
            approved_amount: approveForm.value.approved_amount,
          }
        );
        if (response.data.success) {
          showApproveModal.value = false;
          approveDocument.value = null;
          approveForm.value = {
            acknowledged: false,
            remarks: "",
            approved_amount: 0,
            original_amount: 0,
          };
          loadDocuments(
            pagination.value.current_page,
            pagination.value.per_page
          );
          messageAlert.success("Invoice approved successfully");
        }
      } catch (error) {
        messageAlert.error(
          error.response?.data?.message ||
            "Error approving invoice. Please try again."
        );
      } finally {
        approvingId.value = null;
      }
    };

    const moveInvoiceToPaid = async (document) => {
      if (!document?.id) return;

      movingToPaidId.value = document.id;
      try {
        const response = await axios.post(
          `/upload-portal/api/ap-invoices/${document.id}/mark-paid`
        );
        if (response.data.success) {
          loadDocuments(
            pagination.value.current_page,
            pagination.value.per_page
          );
          messageAlert.success(
            response.data.message || "Invoice moved to paid"
          );
        }
      } catch (error) {
        messageAlert.error(
          error.response?.data?.message ||
            "Error updating invoice status. Please try again."
        );
      } finally {
        movingToPaidId.value = null;
      }
    };

    const openEditModal = (document) => {
      if (isInvoiceDocument(document)) {
        invoiceEditDocument.value = document;
        showInvoiceModal.value = true;
        return;
      }

      editForm.value = {
        id: document.id,
        name: document.name,
        company: document.company
          ? {
              id: document.company.id,
              name:
                document.company.name ||
                document.company.store_number + " - " + document.company.name,
            }
          : null,
        is_check: document.is_check == 1,
      };
      showEditModal.value = true;
    };

    const closeEditModal = () => {
      showEditModal.value = false;
      editForm.value = {
        id: null,
        name: "",
        company: null,
        is_check: false,
      };
    };

    const handleEdit = async () => {
      if (!editForm.value.name || !editForm.value.company) {
        alert("Please fill in all required fields");
        return;
      }

      updating.value = true;
      try {
        const response = await axios.put(
          `/upload-portal/api/documents/${editForm.value.id}`,
          {
            name: editForm.value.name,
            company_id: editForm.value.company.id,
            is_check: editForm.value.is_check,
          }
        );

        if (response.data.success) {
          closeEditModal();
          loadDocuments(
            pagination.value.current_page,
            pagination.value.per_page
          );
          alert("Document updated successfully");
        }
      } catch (error) {
        console.error("Error updating document:", error);
        const errorMessage =
          error.response?.data?.message ||
          "Error updating document. Please try again.";
        alert(errorMessage);
      } finally {
        updating.value = false;
      }
    };

    const deleteDocument = async (document) => {
      if (!confirm(`Are you sure you want to delete "${document.name}"?`)) {
        return;
      }

      deletingId.value = document.id;
      try {
        const response = await axios.delete(
          `/upload-portal/api/documents/${document.id}`
        );

        if (response.data.success) {
          loadDocuments(
            pagination.value.current_page,
            pagination.value.per_page
          );
          alert("Document deleted successfully");
        }
      } catch (error) {
        console.error("Error deleting document:", error);
        const errorMessage =
          error.response?.data?.message ||
          "Error deleting document. Please try again.";
        alert(errorMessage);
      } finally {
        deletingId.value = null;
      }
    };

    const loadCurrentFolder = async () => {
      const response = await axios.get(
        `/upload-portal/api/folder/${folderId.value}`
      );
      if (response.data.success) {
        currentFolder.value = response.data.folder || null;
      }
    };

    const openAddModal = () => {
      // Check if current folder is an invoice folder
      const isInvoiceFolder = currentFolder.value?.is_invoice == 1;

      if (isInvoiceFolder) {
        invoiceEditDocument.value = null;
        showInvoiceModal.value = true;
      } else {
        showModal.value = true;
      }
    };

    const isInvoiceDocument = (document) => {
      return document?.folder?.is_invoice == 1 && document?.ap_invoice;
    };

    const isCheckDocument = (document) => {
      return document?.folder?.is_check == 1;
    };
    const isPdfDocument = (document) => {
      return document?.file_type == "application/pdf";
    };

    const onInvoiceUploadSuccess = () => {
      invoiceEditDocument.value = null;
      checkPaymentPrefillData.value = null;
      selectedInvoiceIds.value = [];
      selectedCheckIds.value = [];
      loadDocuments(pagination.value.current_page, pagination.value.per_page);
    };

    const openInvoiceViewModal = (document) => {
      invoiceViewDocument.value = document;
      showInvoiceViewModal.value = true;
    };

    const openCheckPaymentViewModal = (document) => {
      checkPaymentViewDocument.value = document;
      showCheckPaymentViewModal.value = true;
    };

    watch(
      () => route.params.folderId,
      async (newFolderId) => {
        if (newFolderId) {
          folderId.value = newFolderId;
          activeInvoiceStatus.value = "draft";
          await loadCurrentFolder();
        } else {
          currentFolder.value = null;
          activeInvoiceStatus.value = "draft";
          loadFolders();
        }
        loadDocuments();
      }
    );

    watch(
      () => showModal.value,
      (newValue) => {
        if (newValue && allWorkgroups.value.length === 0) {
          loadWorkgroups();
        }
      }
    );

    watch(
      () => showInvoiceModal.value,
      (newValue) => {
        if (!newValue) {
          invoiceEditDocument.value = null;
        }
      }
    );

    watch(
      () => showInvoiceViewModal.value,
      (newValue) => {
        if (!newValue) {
          invoiceViewDocument.value = null;
        }
      }
    );

    watch(
      () => showCheckPaymentEditModal.value,
      (newValue) => {
        if (!newValue) {
          checkPaymentEditDocument.value = null;
        }
      }
    );

    watch(
      () => showCheckPaymentCreateModal.value,
      (newValue) => {
        if (!newValue) {
          checkPaymentPrefillData.value = null;
        }
      }
    );

    watch(
      () => showCheckPaymentViewModal.value,
      (newValue) => {
        if (!newValue) {
          checkPaymentViewDocument.value = null;
        }
      }
    );

    const renderDueDays = (invoice) => {
      const due_date = invoice?.due_date;
      if (!due_date || !invoice || invoice.status == "paid") return 0;
      const invoiceDate = new Date();
      const dueDate = new Date(due_date);
      const diffTime = invoiceDate - dueDate;
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      return diffDays;
    };

    watch(
      () => form.value.folder,
      (newFolder) => {
        // If user selects an invoice folder in the regular modal, switch to invoice modal
        if (newFolder && newFolder.is_invoice == 1 && showModal.value) {
          // Store current form data
          const currentWorkgroup = form.value.workgroup;
          const currentCompany = form.value.company;
          const currentName = form.value.name;
          const currentFile = form.value.file;

          // Close regular modal
          closeModal();

          // Open invoice modal after a brief delay
          setTimeout(() => {
            showInvoiceModal.value = true;
          }, 100);
        }
      }
    );

    onMounted(() => {
      loadAllCompanies();
      if (isAllDocumentsView.value) {
        loadFolders();
        loadDocuments();
      } else {
        loadCurrentFolder().then(() => {
          loadDocuments();
        });
      }
    });

    return {
      isAllDocumentsView,
      pageTitle,
      pageDescription,
      isInvoiceFolderView,
      invoiceStatusTabs,
      activeInvoiceStatus,
      canSelectInvoicesForPayment,
      allCurrentInvoicesSelected,
      isCheckFolderView,
      canSelectChecksForPrint,
      showDocumentBulkSelectColumn,
      allCurrentChecksSelected,
      documents,
      loading,
      showModal,
      showInvoiceModal,
      showInvoiceViewModal,
      showCheckPaymentCreateModal,
      showCheckPaymentEditModal,
      showCheckPaymentViewModal,
      invoiceEditDocument,
      invoiceViewDocument,
      checkPaymentEditDocument,
      checkPaymentViewDocument,
      selectedInvoiceIds,
      selectedCheckIds,
      printingChecksPdf,
      checkPaymentFolderId,
      checkPaymentPrefillData,
      showEditModal,
      uploading,
      updating,
      deletingId,
      approvingId,
      movingToPaidId,
      allWorkgroups,
      companies,
      allCompanies,
      allFolders,
      loadingCompanies,
      form,
      editForm,
      filters,
      pagination,
      sort,
      folderId,
      onWorkgroupChange,
      onFileChange,
      handleSubmit,
      closeModal,
      formatDate,
      formatFileSize,
      formatMimeType,
      downloadDocument,
      applyFilters,
      clearFilters,
      debounceFilter,
      onPageChange,
      handleSort,
      setInvoiceStatusTab,
      toggleInvoiceSelection,
      toggleSelectAllInvoices,
      toggleCheckSelection,
      toggleSelectAllChecks,
      printSelectedChecksPdf,
      openPaySelectedInvoices,
      openEditModal,
      closeEditModal,
      handleEdit,
      deleteDocument,
      openAddModal,
      onInvoiceUploadSuccess,
      openInvoiceViewModal,
      openCheckPaymentViewModal,
      can,
      getIconForMimeType,
      getColorForMimeType,
      message,
      isInvoiceDocument,
      isCheckDocument,
      isPdfDocument,
      showApproveModal,
      approveDocument,
      approveForm,
      canSubmitApproval,
      openApproveModal,
      closeApproveModal,
      submitApproveInvoice,
      moveInvoiceToPaid,
      checkInvoiceFolder,
      onFolderChange,
      currentFolder,
      renderDueDays,
    };
  },
};
</script>
