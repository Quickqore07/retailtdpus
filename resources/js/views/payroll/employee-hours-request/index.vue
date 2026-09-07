<template>
  <div class="employee-hours-request-index">
    <Filterable
      ref="filterableRef"
      title="Employee Hours Requests"
      url="payroll/employee-hours-request"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
    >
      <template #extra>
        <Button
          v-if="access.includes('create')"
          icon-left="plus"
          icon-size="sm"
          variant="primary"
          size="sm"
          to="/payroll/employee-hours-request/create"
        >
          New request
        </Button>
      </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Employee Name</Th>
          <Th>Company</Th>
          <Th>Date</Th>
          <Th>Role</Th>
          <Th>Hours</Th>
          <Th>Rate</Th>
          <Th>Tips</Th>
          <Th>Mileage</Th>
          <Th>Status</Th>
          <Th>DO Approved By</Th>
          <Th>HR Approved By</Th>
          <Th>Admin Approved By</Th>
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">{{ index + 1 }}</Td>
          <Td weight="medium" color="primary">
            {{
              item.employee?.pos_name
                ? item.employee?.employee_id + " - " + item.employee?.pos_name
                : "N/A"
            }}
          </Td>
          <Td color="secondary">{{ item.company?.name || "N/A" }}</Td>
          <Td color="secondary">{{ formatDate(item.date) }}</Td>
          <Td color="secondary">
            {{
              item.role?.name
                ? item.role?.code + " - " + item.role?.name
                : "N/A"
            }}
          </Td>
          <Td color="secondary">{{ formatNumber(item.total_hours) }}</Td>
          <Td color="secondary">${{ formatNumber(item.pay_rate) }}</Td>
          <Td color="secondary">${{ formatNumber(item.tips) }}</Td>
          <Td color="secondary">${{ formatNumber(item.mileage_excess) }}</Td>
          <Td color="secondary" > 
            <span :class="statusClass(item.status)">{{ item.status ? item.status.charAt(0).toUpperCase() + item.status.slice(1) : "-" }}</span></Td>
          <Td color="secondary">{{ item.do_approved_by?.name || "-" }}</Td>
          <Td color="secondary">{{ item.hr_approved_by?.name || "-" }}</Td>
          <Td color="secondary">{{ item.admin_approved_by?.name || "-" }}</Td>
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <Button
                v-if="access.includes('update') && item.status === 'pending'"
                icon-left="check"
                icon-size="sm"
                variant="success"
                size="sm"
                @click="approveHoursRequest(item.id)"
                :loading="approveLoading.includes(item.id)"
                :disabled="approveLoading.includes(item.id)"
              >
                Approve
              </Button>
              <router-link
                v-if="access.includes('show')"
                :to="`/payroll/employee-hours-request/${item.id}`"
                class="bg-blue-500 !text-white px-2 py-1 rounded-md"
                title="View"
              >
                <SvgIcon name="eye" size="md" />
              </router-link>
              <Button
                v-if="access.includes('delete') && item.status != 'approved'"
                icon-left="trash"
                icon-size="sm"
                variant="danger"
                size="sm"
                customClass="!px-2 !py-1.5"
                @click="handleDelete(item.id)"
              ></Button>
            </div>
          </Td>
        </tr>
      </template>
    </Filterable>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRoute } from "vue-router";
import Filterable from "@/components/filterable/filterable.vue";
import { useIndexable } from "@/composables/useIndexable";
import SvgIcon from "@/components/SvgIcon.vue";
import Td from "@/components/ui/td.vue";
import Th from "@/components/ui/th.vue";
import { formatDate } from "@/utils/date";
import Button from "@/components/ui/button.vue";
import { useRequest } from "@/services/api";
import { useMessage } from "@/composables/useMessage";

const filterableRef = ref(null);
const route = useRoute();
const resource = route.meta?.resource || "payroll/employee-hours-request";
const approveLoading = ref([]);
const message = useMessage();
const { setData, access, removeDB } = useIndexable(
  resource,
  "employee-hours-request"
);

const sortableColumns = [
  { value: "date", label: "Date" },
  { value: "company_id", label: "Company" },
  { value: "role_id", label: "Role" },
  { value: "total_hours", label: "Hours" },
  { value: "pay_rate", label: "Rate" },
  { value: "created_at", label: "Created At" },
];

const filterGroups = [
  {
    title: "Basic Information",
    filters: [
      {
        name: "employee_id",
        title: "Employee",
        type: "lookup_only",
        placeholder: "Enter employee",
        resource: "employees",
        column: "pos_name",
      },
      {
        name: "company_id",
        title: "Company",
        type: "lookup_only",
        resource: "companies",
        column: "name",
        placeholder: "Select company",
      },
      {
        name: "date",
        title: "Date",
        type: "date",
        placeholder: "Select date",
      },
      {
        name: "role_id",
        title: "Role",
        type: "lookup_only",
        resource: "employee-roles",
        column: "name",
        placeholder: "Select role",
      },
      {
        name: "pay_type",
        title: "Pay Type",
        type: "dropdown",
        placeholder: "Select pay type",
        column: "label",
        options: [
          { id: "HR", label: "HR" },
          { id: "WK", label: "WK" },
        ],
      },
      {
        name: "status",
        title: "Status",
        type: "dropdown",
        placeholder: "Select status",
        column: "label",
        options: [
          { id: "pending", label: "Pending" },
          { id: "approved", label: "Approved" },
          { id: "rejected", label: "Rejected" },
        ],
      },
    ],
  },
  {
    title: "Dates",
    filters: [
      {
        name: "created_at",
        title: "Created At",
        type: "datetime",
        placeholder: "Select date",
      },
      {
        name: "updated_at",
        title: "Updated At",
        type: "datetime",
        placeholder: "Select date",
      },
    ],
  },
];

const formatNumber = (value) => {
  if (!value && value !== 0) return "0.00";
  return parseFloat(value).toFixed(2);
};

const approveHoursRequest = async (id) => {
  approveLoading.value.push(id);
  try {
    const response = await useRequest("post", `${resource}/${id}/approve`);
    if (response.success) {
      message.success("Hours request approved successfully");
    } else {
      message.error("Failed to approve hours request");
    }
    if (filterableRef.value) {
      filterableRef.value.fetch();
    }
  } catch (error) {
    console.error(error);
    message.error("Failed to approve hours request");
  } finally {
    approveLoading.value.splice(approveLoading.value.indexOf(id), 1);
  }
};

const handleDelete = async (id) => {
  const success = await removeDB(resource, id);
  if (success && filterableRef.value) {
    filterableRef.value.fetch();
  }
};

const statusClass = (status) => {
  if (status === 'pending') return 'text-yellow-500 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-2 py-1 rounded-md';
  if (status === 'approved') return 'text-green-500 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-md';
  if (status === 'rejected') return 'text-red-500 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-2 py-1 rounded-md';
  return '';
}

defineExpose({ setData });
</script>
