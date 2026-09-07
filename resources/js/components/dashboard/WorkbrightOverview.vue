<template>
  <div
    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between  gap-3">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
          {{ title }}
        </h4>
        <div class="flex items-center gap-3">

          <span class="text-sm text-gray-500 dark:text-gray-400">Total: {{ collection.total || rows.length }}</span>

          <Button icon-left="eye" icon-size="sm" variant="secondary" size="sm" @click="openLogsModal"
            :loading="logsLoading" :disabled="loading">
            View logs
          </Button>
          <Button icon-left="upload" icon-size="sm" variant="secondary" size="sm" @click="exportToExcel"
            :loading="exportLoading" :disabled="rows.length === 0 || loading">
            Export to Excel
          </Button>
        </div>
      </div>
      <Input v-model="searchInput" placeholder="Search employee / ID / onboarding #" :disabled="loading"
        icon-left="search" class="mb-2" />
      <div class="flex items-center justify-end gap-2">
        <Button v-if="selectable" icon-left="check" icon-size="sm" variant="primary" size="sm" @click="emitAuthorize"
          :loading="authorizeLoading" :disabled="loading || selectedIds.length === 0">
          Authorize Employee{{ selectedIds.length ? ` (${selectedIds.length})` : "" }}
        </Button>
      </div>
      <div v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">
        Loading WorkBright data...
      </div>

      <div v-else-if="rows.length === 0"
        class="text-sm text-gray-500 dark:text-gray-400 text-center h-[50%] flex items-center justify-center">
        No WorkBright onboarding data found.
      </div>

      <div class="overflow-x-auto max-h-[500px] overflow-y-auto" v-else>
        <table class="min-w-[700px] text-sm text-left text-gray-600 dark:text-gray-300">
          <thead
            class="text-xs sticky top-0 bg-white dark:bg-gray-800 z-[1] uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th v-if="selectable" class="py-2 pr-3 w-10">
                <input type="checkbox"
                  class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  :checked="allSelected" :disabled="loading || rows.length === 0"
                  @change="toggleSelectAll($event.target.checked)" />
              </th>
              <th class="py-2 pr-4">Employee</th>
              <!-- <th class="py-2 pr-4">Onboarding #</th> -->
              <th class="py-2 pr-4">Status</th>
              <th class="py-2 pr-4">Final Status</th>
              <th class="py-2 pr-4 w-px whitespace-nowrap text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in rows" :key="`${item.employee_id}-${item.onboarding_number}`"
              class="border-b border-gray-100 dark:border-gray-700 last:border-b-0">
              <td v-if="selectable" class="py-2 pr-3">
                <input type="checkbox"
                  class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  :checked="selectedIds.includes(item.id)" :disabled="loading"
                  @change="toggleRow(item.id, $event.target.checked)" />
              </td>
              <td class="py-2 pr-4 text-gray-900 dark:text-gray-100">
                <router-link v-if="item.employee_id" :to="`/employee/${item.employee_id}`"
                  class="text-blue-500 hover:text-blue-700">
                  {{ item.employee?.employee_id || item.employee_id }} - {{ item.employee?.pos_name || "-" }}
                </router-link>
                <span v-else>{{ item.employee?.employee_id || "-" }} - {{ item.employee?.pos_name || "-" }}</span>
              </td>
              <!-- <td class="py-2 pr-4">{{ item.onboarding_number || "-" }}</td> -->
              <td class="py-2 pr-4">
                <div class="flex items-center gap-2">
                  {{ formatStatus(item.status) }}
                  <div class="flex items-center gap-2" v-if="item.status !== 'verified'">
                    <span v-if="!item.i9_completed && !item.i9_approved" class="text-red-500 text-xs">I9 Pending</span>
                    <span v-if="!item.w4_completed && !item.w4_approved" class="text-red-500 text-xs">W4 Pending</span>
                    <span v-if="item.i9_completed && !item.i9_approved" class="text-red-500 text-xs">I9 Completed</span>
                    <span v-if="item.w4_completed && !item.w4_approved" class="text-red-500 text-xs">W4 Completed</span>
                    <span v-if="item.i9_rejected" class="text-red-500 text-xs">I9 Rejected</span>
                    <span v-if="item.w4_rejected" class="text-red-500 text-xs">W4 Rejected</span>
                    <span v-if="item.i9_approved" class="text-green-500 text-xs">I9 Approved</span>
                    <span v-if="item.w4_approved" class="text-green-500 text-xs">W4 Approved</span>
                  </div>
                </div>
              </td>
              <td class="py-2 pr-4">

                <div v-if="item.status === 'form_submitted' && item.i9_approved && item.w4_approved"
                  class="text-red-500 text-xs">Internal Review Pending</div>
                <div v-else-if="(!item.i9_approved && !item.w4_approved)" class="text-red-500 text-xs">I9 and W4 Pending
                </div>
                <div v-else>
                  {{ formatStatus(item.final_status) }}
                </div>
              </td>
              <td class="py-2 pl-2 text-right flex items-center gap-2">
                <Button icon-left="eye" icon-size="sm" variant="secondary" size="sm" @click="openLogsModalForRow(item)"
                  :loading="logsLoading && logsLoadingForId === item.id" :disabled="loading">
                  Log
                </Button>
                <Button icon-left="eye" icon-size="sm" variant="secondary" size="sm" @click="redirectToEmployee(item)"
                  :disabled="loading">
                  Review
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700" v-if="rows?.length">
      <Pagination :collection="collection" :loading="loading" @page-change="onPageChange" />
    </div>

    <Modal v-model="logsModalOpen" :title="logsModalTitle" size="5xl" :show-footer="false"
      body-class="!max-h-[70vh] overflow-y-auto !py-3">
      <div v-if="!logsLoading" class="flex justify-end mb-3">
        <Button icon-left="upload" icon-size="sm" variant="secondary" size="sm" @click="exportLogsToExcel"
          :loading="logsExportLoading">
          Export logs to Excel
        </Button>
      </div>
      <div v-if="logsLoading" class="text-sm text-gray-500 dark:text-gray-400 py-4">
        Loading logs...
      </div>
      <div v-else-if="!onboardingLogs.length" class="text-sm text-gray-500 dark:text-gray-400 py-4">
        No logs found.
      </div>
      <div v-else class="space-y-3">
        <div class="overflow-x-auto -mx-2">
          <table class="min-w-full text-sm text-left text-gray-600 dark:text-gray-300">
            <thead
              class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-[1]">
              <tr>
                <th class="py-2 pr-3">ID</th>
                <th class="py-2 pr-3 whitespace-nowrap">When</th>
                <th class="py-2 pr-3">Employee</th>
                <th class="py-2 pr-3">Onboarding #</th>
                <!-- <th class="py-2 pr-3">Event</th> -->
                <th class="py-2 pr-3">Description</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(log, index) in onboardingLogs" :key="log.id"
                class="border-b border-gray-100 dark:border-gray-700 last:border-b-0 align-top">
                <td class="py-2 pr-3 whitespace-nowrap text-gray-900 dark:text-gray-100">
                  {{ index + 1 }}
                </td>
                <td class="py-2 pr-3 whitespace-nowrap text-gray-900 dark:text-gray-100">
                  {{ formatLogWhen(log) }}
                </td>
                <td class="py-2 pr-3">
                  {{ log.employee_name ? `${log.employee_id}-${log.employee_name} ` : "—" }}

                </td>
                <td class="py-2 pr-3">{{ log.onboarding_number || "—" }}</td>
                <!-- <td class="py-2 pr-3">{{ log.event || "—" }}</td> -->
                <td class="py-2 pr-3 max-w-[200px]">{{ log.description || "—" }}</td>

              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :collection="logsCollection" :loading="logsLoading" :limit="logsCollection.per_page || 25"
          @page-change="onLogsPageChange" />
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import Button from "@/components/ui/button.vue";
import Modal from "@/components/common/Modal.vue";
import Pagination from "@/components/ui/pagination.vue";
import { useMessage } from "@/composables/useMessage";
import api from "@/services/api";
import Input from "../ui/input.vue";
import { formatDateTime } from "@/utils/date";
import { formatDate } from "@/utils/date";

const props = defineProps({
  title: {
    type: String,
    default: "WorkBright Onboarding",
  },
  exportEndpoint: {
    type: String,
    default: "dashboard/workbright-export",
  },
  rows: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  collection: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      from: 0,
      to: 0,
      total: 0,
      per_page: 30,
      prev_page_url: null,
      next_page_url: null,
    }),
  },
  selectable: {
    type: Boolean,
    default: false,
  },
  authorizeLoading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["page-change", "search-change", "authorize"]);
const selectedIds = ref([]);

const allSelected = computed(() => {
  return props.rows.length > 0 && props.rows.every((row) => selectedIds.value.includes(row.id));
});

watch(
  () => props.rows,
  (rows) => {
    const currentIds = new Set(rows.map((row) => row.id));
    selectedIds.value = selectedIds.value.filter((id) => currentIds.has(id));
  }
);

function toggleSelectAll(checked) {
  const pageIds = props.rows.map((row) => row.id);
  if (checked) {
    selectedIds.value = [...new Set([...selectedIds.value, ...pageIds])];
  } else {
    const pageIdSet = new Set(pageIds);
    selectedIds.value = selectedIds.value.filter((id) => !pageIdSet.has(id));
  }
}

function toggleRow(id, checked) {
  if (checked) {
    if (!selectedIds.value.includes(id)) {
      selectedIds.value = [...selectedIds.value, id];
    }
    return;
  }
  selectedIds.value = selectedIds.value.filter((selectedId) => selectedId !== id);
}

function emitAuthorize() {
  if (!selectedIds.value.length) return;
  emit("authorize", [...selectedIds.value]);
}
const message = useMessage();

const logsModalContextLabel = ref(null);

const logsModalTitle = computed(() => {
  if (logsFilterOnboardingListId.value == null) {
    return "WorkBright onboarding logs";
  }
  if (logsModalContextLabel.value) {
    return `Logs — ${logsModalContextLabel.value}`;
  }
  return "WorkBright onboarding logs";
});
const exportLoading = ref(false);
const logsExportLoading = ref(false);
const logsModalOpen = ref(false);
const logsLoading = ref(false);
const logsLoadingForId = ref(null);
const logsFilterOnboardingListId = ref(null);
const onboardingLogs = ref([]);
const logsCollection = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
  per_page: 25,
  has_prev: false,
  has_next: false,
});
const searchInput = ref("");
let searchTimer = null;

watch(
  () => searchInput.value,
  (val) => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      emit("search-change", val);
    }, 1000);
  }
);

function formatStatus(value) {
  if (!value) return "-";
  return (
    String(value).replaceAll("_", " ").charAt(0).toUpperCase() +
    String(value).replaceAll("_", " ").slice(1)
  );
}

function onPageChange(page) {
  emit("page-change", page);
}

function formatLogWhen(log) {
  const raw = log.date || log.created_at;
  if (!raw) return "—";
  return formatDateTime(raw);
}

async function openLogsModal() {
  logsFilterOnboardingListId.value = null;
  logsModalContextLabel.value = null;
  logsLoadingForId.value = null;
  logsModalOpen.value = true;
  await fetchOnboardingLogs(1, logsCollection.value.per_page || 25);
}

async function openLogsModalForRow(item) {
  const name = item.employee?.pos_name?.trim() || "Employee";
  const num = item.onboarding_number;
  logsModalContextLabel.value = num ? `${name} (onboarding #${num})` : name;
  logsFilterOnboardingListId.value = item.id;
  logsLoadingForId.value = item.id;
  logsModalOpen.value = true;
  await fetchOnboardingLogs(1, logsCollection.value.per_page || 25);
}

function onLogsPageChange(page, perPage) {
  fetchOnboardingLogs(page, perPage);
}

function redirectToEmployee(item) {
  window.open(`https://pie.workbright.com/staff/${item.work_bright_employee_id}/forms`, '_blank');
}

async function fetchOnboardingLogs(page = 1, perPage = null) {
  try {
    logsLoading.value = true;
    const limit = perPage ?? logsCollection.value.per_page ?? 25;
    const params = { page, limit };
    if (logsFilterOnboardingListId.value != null) {
      params.onboarding_list_id = logsFilterOnboardingListId.value;
    }
    const res = await api.get("dashboard/workbright-logs", { params });
    onboardingLogs.value = res.data?.data ?? [];
    const pag = res.data?.pagination;
    if (pag) {
      logsCollection.value = {
        current_page: pag.current_page ?? 1,
        last_page: pag.last_page ?? 1,
        from: pag.from ?? 0,
        to: pag.to ?? 0,
        total: pag.total ?? 0,
        per_page: pag.per_page ?? limit,
        has_prev: pag.has_prev ?? false,
        has_next: pag.has_next ?? false,
      };
    }
  } catch (e) {
    console.error("WorkBright logs:", e);
    message.error("Failed to load onboarding logs");
    onboardingLogs.value = [];
    logsCollection.value = {
      current_page: 1,
      last_page: 1,
      from: 0,
      to: 0,
      total: 0,
      per_page: logsCollection.value.per_page || 25,
      has_prev: false,
      has_next: false,
    };
  } finally {
    logsLoading.value = false;
    logsLoadingForId.value = null;
  }
}

async function exportLogsToExcel() {
  try {
    logsExportLoading.value = true;
    const params = {};
    if (logsFilterOnboardingListId.value != null) {
      params.onboarding_list_id = logsFilterOnboardingListId.value;
    }
    const response = await api.get("dashboard/workbright-logs-export", {
      params,
      responseType: "blob",
    });

    const blob = new Blob([response.data], {
      type:
        response.headers?.["content-type"] ||
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `WorkBright_Onboarding_Logs_${new Date().getTime()}.xlsx`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);

    message.success("Logs export downloaded");
  } catch (error) {
    console.error("Logs export error:", error);
    message.error("Failed to export logs");
  } finally {
    logsExportLoading.value = false;
  }
}

async function exportToExcel() {
  try {
    exportLoading.value = true;
    const response = await api.get(props.exportEndpoint, {
      params: { search: searchInput.value || "" },
      responseType: "blob",
    });

    const blob = new Blob([response.data], {
      type:
        response.headers?.["content-type"] ||
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `WorkBright_Onboarding_${new Date().getTime()}.xlsx`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);

    message.success("WorkBright export downloaded");
  } catch (error) {
    console.error("Export error:", error);
    message.error("Failed to export WorkBright data");
  } finally {
    exportLoading.value = false;
  }
}
</script>
