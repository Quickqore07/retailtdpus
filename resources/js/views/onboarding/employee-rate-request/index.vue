<template>
  <div class="employee-rate-request-index">
    <Filterable
      ref="filterableRef"
      title="Employee Rate Requests"
      url="onboarding/employee-rate-request"
      :sortable="sortableColumns"
      :filter-groups="filterGroups"
      :create-button="access.includes('create')"
      create-route="/onboarding/employee-rate-request/create"
    >
      <template #extra>
        <!-- <Button
          v-if="access.includes('create')"
          icon-left="plus"
          icon-size="sm"
          variant="primary"
          size="sm"
          to="/onboarding/employee-rate-request/create"
        >
          New Rate Request
        </Button> -->
      </template>
      <template #heading>
        <tr>
          <Th>No</Th>
          <Th>Employee Name</Th>
          <Th>Company</Th>
          <Th>Effective Date</Th>
          <Th>Role</Th>
          <Th>Pay Type</Th>
          <Th>Rate Type</Th>
          <Th>Rate</Th>
          <Th>Onboarding Status</Th>
          <!-- <Th>DO Approved By</Th>
          <Th>HR Approved By</Th>
          <Th>Admin Approved By</Th> -->
          <Th align="right">Actions</Th>
        </tr>
      </template>

      <template #default="{ item, index }">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
          <Td color="default">
            {{ index + 1 }}
          </Td>
          <Td weight="medium" color="primary">
            <a  target="_blank" :href="`/employee/${item.employee?.id}`">{{ getFullName(item) }}</a>
          </Td>
          <Td color="secondary">
            {{ item.company?.name || "N/A" }}
          </Td>
          <Td color="secondary">
            {{ formatDate(item.effective_date) }}
          </Td>
          <Td color="secondary">
            {{
              item.role?.name
                ? item.role?.code + " - " + item.role?.name
                : "N/A"
            }}
          </Td>
          <Td color="secondary">
            {{ item.pay_type || "N/A" }}
          </Td>
          <Td color="secondary">
            {{ item.rate_type || "N/A" }}
          </Td>
          <Td color="secondary"> ${{ formatNumber(item.rate) }} </Td>
          <Td color="secondary">
            {{ renderOnboardingStatus(item.employee?.onboarding_status) || "N/A" }}
          </Td>
          <!-- <Td color="secondary">
            {{ item.do_approved_by?.name || "N/A" }}
          </Td>
          <Td color="secondary">
            {{ item.hr_approved_by?.name || "N/A" }}
          </Td>
          <Td color="secondary">
            {{ item.admin_approved_by?.name || "N/A" }}
          </Td> -->
          <!-- <Td weight="medium" color="primary">
              ${{ formatNumber(calculateEarnings(item)) }}
            </Td> -->
          <Td align="right" weight="medium">
            <div class="flex items-center justify-end gap-2">
              <Button
                v-if="access.includes('approve') && item.status === 'pending'"
                icon-left="check"
                icon-size="sm"
                variant="success"
                size="sm"
                @click="approveRateRequest(item.id)"
                :loading="approvingRequestId === item.id"
                :disabled="approvingRequestId !== null"
              >
                Approve</Button
              >
              <Button
                v-if="access.includes('approve') && item.status === 'pending'"
                icon-left="x"
                icon-size="sm"
                variant="danger"
                size="sm"
                customClass="!px-2 !py-1"
                @click="openRejectModal(item.id)"
              >
                Reject
              </Button>
              <router-link
                v-if="access.includes('show')"
                :to="`/onboarding/employee-rate-request/${item.id}`"
                class="bg-blue-500 !text-white px-2 py-1 rounded-md"
                title="View"
              >
                <SvgIcon name="eye" size="md" />
              </router-link>
              <router-link
                v-if="access.includes('update') && item.status === 'pending'"
                :to="`/onboarding/employee-rate-request/${item.id}/edit`"
                class="bg-yellow-500 !text-white px-2 py-1 rounded-md"
                title="Edit"
              >
                <SvgIcon name="edit" size="md" />
              </router-link>
            </div>
          </Td>
        </tr>
      </template>
    </Filterable>

    <Modal
      v-model="rejectModalOpen"
      title="Reject rate request"
      size="lg"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Cancel"
      confirm-text="Reject"
      :loading="rejectSubmitting"
      @confirm="submitReject"
    >
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
        Enter a reason. It will be included in the email sent to the user who last updated this employee record.
      </p>
      <Textarea
        v-model="rejectReason"
        label="Reason"
        :rows="4"
        placeholder="Explain why this request is being rejected…"
        required
      />
    </Modal>
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
import Modal from "@/components/common/Modal.vue";
import Textarea from "@/components/ui/textarea.vue";
import { useRequest } from "@/services/api";
import { useMessage } from "@/composables/useMessage";

const getFullName = (item) => {
  return item.employee?.employee_id + ' - ' + item.employee?.pos_name 
}
const filterableRef = ref(null);
const route = useRoute();
const resource = route.meta?.resource || "onboarding/employee-rate-request";
const approvingRequestId = ref(null);
const message = useMessage();
const rejectModalOpen = ref(false);
const rejectTargetId = ref(null);
const rejectReason = ref("");
const rejectSubmitting = ref(false);

// Use the useIndexable composable
const { setData, access } = useIndexable(resource, "employee-rate-request");

// Sortable columns configuration
const sortableColumns = [
  { value: "created_at", label: "Created At" },
  { value: "effective_date", label: "Effective Date" },
  { value: "role", label: "Role" },
  { value: "pay_type", label: "Pay Type" },
  { value: "rate_type", label: "Rate Type" },
  { value: "rate", label: "Rate" },
  { value: "updated_at", label: "Updated At" },
];

// Filter groups configuration
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
        name: "effective_date",
        title: "Effective Date",
        type: "date",
        placeholder: "Select effective date",
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
        name: "rate_type",
        title: "Rate Type",
        type: "dropdown",
        placeholder: "Select rate type",
        column: "label",
        options: [
          { id: "Payroll Regular", label: "Payroll Regular" },
          { id: "Payroll Slab", label: "Payroll Slab" },
          { id: "1099 Regular", label: "1099 Regular" },
          { id: "1099 Slab", label: "1099 Slab" },
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

const renderOnboardingStatus = (status) => {
  let str = status.replace(/_/g, ' ')
  return str.charAt(0).toUpperCase() + str.slice(1)
}

// Format number helper
const formatNumber = (value) => {
  if (!value && value !== 0) return "0.00";
  return parseFloat(value).toFixed(2);
};
const approveRateRequest = async (id) => {
  // Prevent parallel approvals from multiple rows.
  if (approvingRequestId.value !== null) return;
  approvingRequestId.value = id;
  try {
    const response = await useRequest("post", `${resource}/${id}/approve`);
    if (response.success) {
      message.success("Rate request approved successfully");
    } else {
      message.error("Failed to approve rate request");
    }
    if (filterableRef.value) {
      filterableRef.value.fetch();
    }
  } catch (error) {
    console.error(error);
    message.error(error.response?.data?.message || "Failed to approve rate request");
  } finally {
    approvingRequestId.value = null;
  }
};

const openRejectModal = (id) => {
  rejectTargetId.value = id;
  rejectReason.value = "";
  rejectModalOpen.value = true;
};

const submitReject = async () => {
  const reason = (rejectReason.value || "").trim();
  if (!reason) {
    message.error("Please enter a rejection reason.");
    return;
  }
  const id = rejectTargetId.value;
  if (!id) return;
  rejectSubmitting.value = true;
  try {
    const response = await useRequest("post", `${resource}/${id}/reject`, {
      reason,
    });
    if (response.success) {
      message.success("Rate request rejected.");
      rejectModalOpen.value = false;
      rejectTargetId.value = null;
      rejectReason.value = "";
      filterableRef.value?.fetch();
    } else {
      message.error(response.message || "Failed to reject rate request");
    }
  } catch (error) {
    message.error(
      error.response?.data?.message || "Failed to reject rate request"
    );
  } finally {
    rejectSubmitting.value = false;
  }
};

// Expose setData for useIndexable route guards
defineExpose({
  setData,
});
</script>
  
  