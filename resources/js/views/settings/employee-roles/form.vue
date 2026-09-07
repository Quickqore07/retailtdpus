<template>
  <div v-if="show" class="employee-role-form">
    <!-- Form Panel -->
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold">
            {{
              mode === "edit"
                ? "Edit Employee Role"
                : "Create New Employee Role"
            }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <!-- Basic Information -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <Input
            v-model="form.name"
            label="Role Name"
            placeholder="Enter role name"
            :required="true"
            :error="errors.name ? errors.name[0] : null"
            icon-left="briefcase"
          />

          <Input
            v-model="form.code"
            label="Role Code"
            placeholder="Enter role code"
            :required="true"
            :error="errors.code ? errors.code[0] : null"
            icon-left="tag"
          />
          <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                Status
              </label>
              <div class="flex items-center space-x-2">
                <input
                  type="checkbox"
                  v-model="form.active"
                  :true-value="true"
                  :false-value="false"
                  :checked="form.active ? true : false"
                  class="w-4 h-4 text-blue-600"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  {{ form.active ? "Active" : "Inactive" }}
                </span>
              </div>
              <p
                v-if="errors.active"
                class="text-xs text-red-600 dark:text-red-400 mt-1"
              >
                {{ errors.active[0] }}
              </p>
            </div>

            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                Tipped
              </label>
              <div class="flex items-center space-x-2">
                <input
                  type="checkbox"
                  v-model="form.tipped"
                  :true-value="true"
                  :false-value="false"
                  :checked="form.tipped ? true : false"
                  class="w-4 h-4 text-blue-600"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  {{ form.tipped ? "Tipped" : "Not Tipped" }}
                </span>
              </div>
              <p
                v-if="errors.tipped"
                class="text-xs text-red-600 dark:text-red-400 mt-1"
              >
                {{ errors.tipped[0] }}
              </p>
            </div>
          </div>
        </div>

        <div>
          <h6
            class="text-base font-semibold text-gray-800 dark:text-white mb-4"
          >
            Sub Roles
          </h6>
          <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 flex items-end">
            <div
              v-for="(subRole, index) in form.sub_roles"
              :key="index"
              class="flex items-center gap-2 items-end"
            >
              <Input
                v-model="form.sub_roles[index]"
                :label="`Sub Role ${index + 1}`"
                placeholder="Enter sub role"
                icon-left="tag"
                class="w-full"
              />
              <Button
                variant="outline-danger"
                size="md"
                @click="removeSubRole(index)"
                type="button"
                custom-class="max-w-[130px] min-h-[34px]"
              >
                <SvgIcon name="trash" size="sm" class="text-red-500" />
              </Button>
            </div>
            <Button
              variant="outline-primary"
              size="md"
              @click="addSubRole"
              type="button"
              custom-class="max-w-[130px] h-auto"
            >
              Add Sub Role
            </Button>
          </div>
        </div>

        <!-- Action Buttons -->
        <div
          class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700"
        >
          <Button
            variant="outline-secondary"
            size="md"
            @click="cancel"
            type="button"
          >
            Cancel
          </Button>
          <Button
            variant="primary"
            size="md"
            type="submit"
            :loading="isSaving"
            v-if="
              mode === 'create'
                ? access.includes('create')
                : access.includes('update')
            "
          >
            {{ mode === "edit" ? "Update Role" : "Create Role" }}
          </Button>
        </div>
      </form>
    </Panel>
  </div>

  <!-- Loading State -->
  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading form..." centered />
  </div>
</template>

<script setup>
import { useRoute } from "vue-router";
import SvgIcon from "@/components/SvgIcon.vue";
import { useFormable } from "@/composables/useFormable";
import Panel from "@/components/ui/panel.vue";
import Button from "@/components/ui/button.vue";
import Input from "@/components/ui/input.vue";
import Spinner from "@/components/ui/spinner.vue";
import { watch } from "vue";

const route = useRoute();
const resource = route.meta?.resource || "settings/employee-roles";

// Use the useFormable composable
const { form, errors, isSaving, show, mode, save, cancel, setData, access } =
  useFormable(resource, "settings/employee-roles", "employee-role");
watch(
  () => form.value.active,
  (newVal) => {
    form.value.active = newVal ? true : false;
  }
);
watch(
  () => form.value.tipped,
  (newVal) => {
    form.value.tipped = newVal ? true : false;
  }
);
watch(
  () => form.value,
  (newVal) => {
    if (!newVal.sub_roles?.length) {
      newVal.sub_roles = [""];
    } else {
      newVal.sub_roles = newVal.sub_roles.map((subRole) => subRole.code);
    }
  }
);

const removeSubRole = (index) => {
  form.value.sub_roles.splice(index, 1);
};

const addSubRole = () => {
  form.value.sub_roles.push("");
};
const handleSave = async () => {
  try {
    form.value.sub_roles = form.value.sub_roles.filter((subRole) => subRole);
    await save(form.value);
  } catch (error) {
    console.error(error);
  }
};

// Expose setData for useFormable route guards
defineExpose({
  setData,
});
</script>


