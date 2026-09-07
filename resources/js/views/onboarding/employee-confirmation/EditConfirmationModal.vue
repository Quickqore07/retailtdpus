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
          class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
          :class="statusChipClass"
        >
          <span class="inline-block h-2 w-2 rounded-full" :class="statusDotClass" />
          {{ statusChipLabel }}
        </span>
      </div>
    </template>

    <div v-if="item" class=" overflow-auto px-5 py-4 space-y-4">
      <div
        v-if="detailLoading"
        class="flex items-center justify-center py-16 text-sm text-gray-500 dark:text-gray-400"
      >
        Loading employee details...
      </div>

      <template v-else>
      <!-- Step tracker -->
      <div class="mb-2 flex">
        <div
          v-for="(step, index) in steps"
          :key="step"
          class="relative flex-1 pt-6 text-center text-xs font-semibold"
          :class="stepTextClass(index)"
        >
          <div
            class="absolute left-0 right-0 top-[11px] h-0.5 bg-gray-200 dark:bg-gray-700"
            :class="{
              'left-1/2': index === 0,
              'right-1/2 w-1/2': index === steps.length - 1,
            }"
          />
          <div
            class="absolute left-1/2 top-0 z-[1] flex h-[22px] w-[22px] -translate-x-1/2 items-center justify-center rounded-full border-2 text-[11px]"
            :class="stepBubbleClass(index)"
          >
            {{ stepBubbleContent(index) }}
          </div>
          {{ stepLabel(index, step) }}
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-1 flex items-center justify-between">
          <h6 class="!mb-0 text-sm font-semibold text-gray-900 dark:text-gray-100">Employee Details</h6>
          <Button
            v-if="canEditEmployee && !employeeEditing"
            variant="outline-secondary"
            size="sm"
            @click="startEditEmployee"
          >
            Edit
          </Button>
        </div>

        <div v-if="!employeeEditing" class="grid grid-cols-[140px_1fr] gap-x-4 gap-y-2 text-sm">
          <div class="text-gray-500 dark:text-gray-400">First Name</div>
          <div class="text-gray-900 dark:text-gray-100">{{ employee?.first_name || '—' }}</div>
          <div class="text-gray-500 dark:text-gray-400">Middle Name</div>
          <div class="text-gray-900 dark:text-gray-100">{{ employee?.middle_name || '—' }}</div>
          <div class="text-gray-500 dark:text-gray-400">Last Name</div>
          <div class="text-gray-900 dark:text-gray-100">{{ employee?.last_name || '—' }}</div>
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

        <div v-else class="space-y-3 pt-2">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <Input
              v-model="employeeForm.first_name"
              label="First Name"
              placeholder="First name"
              :error="employeeErrors.first_name"
              required
            />
            <Input
              v-model="employeeForm.middle_name"
              label="Middle Name"
              placeholder="Middle name"
              :error="employeeErrors.middle_name"
            />
            <Input
              v-model="employeeForm.last_name"
              label="Last Name"
              placeholder="Last name"
              :error="employeeErrors.last_name"
              required
            />
            <Input
              v-model="employeeForm.street"
              label="Street"
              placeholder="Street address"
              :error="employeeErrors.street"
              required
            />
            <Input
              v-model="employeeForm.apt_number"
              label="Apt / Suite #"
              placeholder="Apt or suite number"
              :error="employeeErrors.apt_number"
            />
            <Input
              v-model="employeeForm.city"
              label="City"
              placeholder="City"
              :error="employeeErrors.city"
              required
            />
            <Input
              v-model="employeeForm.state"
              label="State"
              placeholder="State"
              :error="employeeErrors.state"
              required
            />
            <Input
              v-model="employeeForm.zip"
              label="Zip"
              placeholder="Zip code"
              :error="employeeErrors.zip"
            />
            <Input
              v-model="employeeForm.dob"
              type="date"
              label="Date of Birth"
              :error="employeeErrors.dob"
            />
            <Input
              v-model="employeeForm.ssn"
              label="SSN"
              placeholder="SSN"
              :error="employeeErrors.ssn"
            />
            <Input
              v-model="employeeForm.email"
              type="email"
              label="Email"
              placeholder="Email"
              :error="employeeErrors.email"
            />
            <Input
              v-model="employeeForm.phone"
              label="Phone"
              placeholder="Phone"
              :error="employeeErrors.phone"
            />
            <Input
              v-model="employeeForm.hire_date"
              type="date"
              label="Date of Joining"
              :error="employeeErrors.hire_date"
              required
            />
          </div>

          <p v-if="employeeError" class="!mb-0 text-sm text-red-600 dark:text-red-400">{{ employeeError }}</p>

          <div class="flex justify-end gap-2 pt-1">
            <Button
              variant="outline-secondary"
              size="sm"
              :disabled="employeeSaving"
              @click="cancelEditEmployee"
            >
              Cancel
            </Button>
            <Button
              variant="primary"
              size="sm"
              :loading="employeeSaving"
              :disabled="employeeSaving"
              @click="saveEmployee"
            >
              Save Changes
            </Button>
          </div>
        </div>
      </div>

      <div
        v-if="(localItem || item)?.status === 'left' || (localItem || item)?.left_terminate"
        class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300"
      >
        This employee is marked as left / terminated for this company. Status selection is disabled.
        <p v-if="(localItem || item)?.left_note" class="!mb-0 mt-2 text-xs">
          <span class="font-semibold">Note:</span> {{ (localItem || item).left_note }}
        </p>
        <p v-if="(localItem || item)?.left_at" class="!mb-0 mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">
          Left on {{ formatDisplayDate((localItem || item).left_at) }}
          <span v-if="(localItem || item).left_updated_by_user?.name"> by {{ (localItem || item).left_updated_by_user.name }}</span>
        </p>
      </div>

      <template v-else-if="activeView === 'status'">
        <p class="!mb-0 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300">
          Select the box the employee attests to. A U.S. citizen provides one
          <b>List A</b> document next; any other status provides <b>List B + List C</b>.
        </p>

        <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/40">
          <div class="mb-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200">
            Check <b>one</b> of the following boxes to attest to the employee's citizenship or immigration status.
          </div>

          <div class="space-y-2">
            <label
              v-for="option in statusOptions"
              :key="option.id"
              class="flex cursor-pointer items-start gap-3 rounded-lg border bg-white p-3 transition-colors dark:bg-gray-800"
              :class="form.i9_choice === option.id
                ? 'border-primary bg-primary/5 dark:border-emerald-400 dark:bg-emerald-400/10'
                : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'"
            >
              <input
                v-model="form.i9_choice"
                type="radio"
                class="mt-1 text-primary focus:ring-primary dark:text-emerald-400 dark:focus:ring-emerald-400"
                :value="option.id"
                name="i9_choice"
              />
              <div class="min-w-0 flex-1">
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                  {{ option.number }}. {{ option.label }}
                </div>
                <p v-if="option.hint" class="!mb-0 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                  {{ option.hint }}
                </p>

                <div
                  v-if="option.id === 'lpr' && form.i9_choice === 'lpr'"
                  class="mt-3 flex flex-wrap items-end gap-2"
                >
                  <Input
                    v-model="form.uscis_number"
                    label="USCIS or A-Number"
                    placeholder="A000000000"
                    class="min-w-[200px] flex-1"
                  />
                </div>

                <div
                  v-if="option.id === 'alien' && form.i9_choice === 'alien'"
                  class="mt-3 space-y-3"
                >
                  <Input
                    v-model="form.work_authorization_exp_date"
                    label="Authorized to work until (exp. date, if any)"
                    type="date"
                  />
                  <p class="!mb-0 text-xs font-semibold text-gray-600 dark:text-gray-300">
                    Enter <u>one</u> of these:
                  </p>
                  <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <Input
                      v-model="form.alien_uscis"
                      label="USCIS A-Number"
                      placeholder="A000000000"
                    />
                    <Input
                      v-model="form.alien_i94"
                      label="Form I-94 Admission Number"
                      placeholder="00000000000"
                    />
                    <div class="space-y-3">
                      <Input
                        v-model="form.alien_passport"
                        label="Foreign Passport"
                        placeholder="Passport number"
                      />
                      <Input
                        v-model="form.alien_country"
                        label="Country of Issuance"
                        placeholder="Country of issuance"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </label>
          </div>

          <p v-if="error" class="mt-3 !mb-0 text-sm text-red-600 dark:text-red-400">{{ error }}</p>

          <div class="mt-4 flex justify-end">
            <Button
              variant="primary"
              size="sm"
              :disabled="!form.i9_choice"
              @click="continueToDocuments"
            >
              Continue
            </Button>
          </div>
        </div>
      </template>

      <template v-else-if="activeView === 'documents'">
        <div
          v-if="(localItem || item)?.hr_status === 'rejected'"
          class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-900/20 dark:text-red-200"
        >
          <p class="!mb-1 font-semibold">This case was rejected during review.</p>
          <p class="!mb-0">Please correct and re-upload the required documents below, then submit for review again.</p>
          <p v-if="(localItem || item)?.review_notes" class="!mb-0 mt-2 text-xs">
            <span class="font-semibold">Reviewer notes:</span> {{ (localItem || item).review_notes }}
          </p>
        </div>
        <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300">
          <template v-if="!requiresListBC">
            Upload one <b>List A</b> document (identity and employment authorization).
          </template>
          <template v-else>
            Upload <b>List B</b> (identity) and <b>List C</b> (employment authorization) documents.
          </template>
        </p>

        <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/40 space-y-4">
          <div v-if="isListAOnly" class="space-y-3">
            <div class="flex flex-col gap-1.5">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                List A Document Type
                <span class="text-red-500">*</span>
              </label>
              <select
                v-model="docForm.list_a_doc_type"
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
              >
                <option value="" disabled>Select document type</option>
                <option
                  v-for="option in listAOptions"
                  :key="option.key"
                  :value="option.key"
                >
                  {{ option.value }}
                </option>
              </select>
              <p v-if="docErrors.list_a_doc_type" class="text-sm text-red-600 dark:text-red-400">
                {{ docErrors.list_a_doc_type }}
              </p>
            </div>

            <template v-if="!isNoPassportSelected">
              <DocumentFileField
                v-model="docForm.list_a_file"
                label="List A Document"
                placeholder="Upload List A document"
                :error="docErrors.list_a"
                required
              />
              <a
                v-if="localItem?.list_a_url"
                :href="localItem.list_a_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-block text-sm text-blue-600 hover:underline dark:text-blue-400"
              >
                View current List A document<span v-if="listADocLabel"> — {{ listADocLabel }}</span>
              </a>
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <Input
                  v-model="docForm.list_a_issuing_authority"
                  label="Issuing Authority"
                  placeholder="Issuing authority"
                />
                <Input
                  v-model="docForm.list_a_document_number"
                  label="Document Number"
                  placeholder="Document number"
                />
                <Input
                  v-model="docForm.list_a_expiration_date"
                  type="date"
                  label="Expiration Date"
                />
              </div>
            </template>
            <p v-else class="!mb-0 text-xs text-blue-700 dark:text-blue-300">
              No List A document selected. Please provide List B and List C documents below instead.
            </p>
          </div>

          <template v-if="requiresListBC">
            <div class="space-y-3">
              <div class="flex flex-col gap-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                  List B Document Type (Identity)
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="docForm.list_b_doc_type"
                  class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
                  <option value="" disabled>Select document type</option>
                  <option
                    v-for="option in listBOptions"
                    :key="option.key"
                    :value="option.key"
                  >
                    {{ option.value }}
                  </option>
                </select>
                <p v-if="docErrors.list_b_doc_type" class="text-sm text-red-600 dark:text-red-400">
                  {{ docErrors.list_b_doc_type }}
                </p>
              </div>
              <DocumentFileField
                v-model="docForm.list_b_file"
                label="List B Document (Identity)"
                placeholder="Upload List B document"
                :error="docErrors.list_b"
                required
              />
              <a
                v-if="localItem?.list_b_url"
                :href="localItem.list_b_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-block text-sm text-blue-600 hover:underline dark:text-blue-400"
              >
                View current List B document<span v-if="listBDocLabel"> — {{ listBDocLabel }}</span>
              </a>
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <Input
                  v-model="docForm.list_b_issuing_authority"
                  label="Issuing Authority"
                  placeholder="Issuing authority"
                />
                <Input
                  v-model="docForm.list_b_document_number"
                  label="Document Number"
                  placeholder="Document number"
                />
                <Input
                  v-model="docForm.list_b_expiration_date"
                  type="date"
                  label="Expiration Date"
                />
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex flex-col gap-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                  List C Document Type (Employment Authorization)
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="docForm.list_c_doc_type"
                  class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
                  <option value="" disabled>Select document type</option>
                  <option
                    v-for="option in listCOptions"
                    :key="option.key"
                    :value="option.key"
                  >
                    {{ option.value }}
                  </option>
                </select>
                <p v-if="docErrors.list_c_doc_type" class="text-sm text-red-600 dark:text-red-400">
                  {{ docErrors.list_c_doc_type }}
                </p>
              </div>
              <DocumentFileField
                v-model="docForm.list_c_file"
                label="List C Document (Employment Authorization)"
                placeholder="Upload List C document"
                :error="docErrors.list_c"
                required
              />
              <a
                v-if="localItem?.list_c_url"
                :href="localItem.list_c_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-block text-sm text-blue-600 hover:underline dark:text-blue-400"
              >
                View current List C document<span v-if="listCDocLabel"> — {{ listCDocLabel }}</span>
              </a>
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <Input
                  v-model="docForm.list_c_issuing_authority"
                  label="Issuing Authority"
                  placeholder="Issuing authority"
                />
                <Input
                  v-model="docForm.list_c_document_number"
                  label="Document Number"
                  placeholder="Document number"
                />
                <Input
                  v-model="docForm.list_c_expiration_date"
                  type="date"
                  label="Expiration Date"
                />
              </div>
            </div>
          </template>

          <p v-if="error" class="!mb-0 text-sm text-red-600 dark:text-red-400">{{ error }}</p>

          <div class="flex justify-between gap-2 pt-2">
            <Button variant="outline-secondary" size="sm" @click="activeView = 'status'">
              Back
            </Button>
            <Button
              variant="primary"
              size="sm"
              :disabled="!canSaveDocuments || docLoading"
              :loading="docLoading"
              @click="saveDocuments"
            >
              Save Status & Documents
            </Button>
          </div>
        </div>
      </template>

      <template v-else-if="activeView === 'review' && canReview">
        <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300">
          Review the selected I-9 documents below, add notes if needed, and submit your review.
        </p>

        <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/40 space-y-4">
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
            <h6 class="!mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Selected Documents</h6>
            <div class="space-y-3">
              <div
                v-for="doc in reviewDocuments"
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
                      Uploaded: {{ doc.fileName }}
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
                  <span
                    v-else
                    class="text-xs text-amber-600 dark:text-amber-400"
                  >
                    No file uploaded
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-2">
            <Textarea
              v-model="reviewForm.review_notes"
              label="Review Notes"
              placeholder="Add review notes (optional)"
              :rows="4"
              :disabled="reviewLoading || !canReview"
            />
            <p
              v-if="localItem?.reviewer?.name"
              class="!mb-0 text-xs text-gray-500 dark:text-gray-400"
            >
              Last reviewed by {{ localItem.reviewer.name }}
            </p>
          </div>

          <p v-if="error" class="!mb-0 text-sm text-red-600 dark:text-red-400">{{ error }}</p>

          <div class="flex justify-between gap-2 pt-2">
            <Button variant="outline-secondary" size="sm" @click="activeView = 'documents'">
              Back
            </Button>
            <div class="flex items-center gap-2">
              <Button
                v-if="canReview"
                variant="danger"
                size="sm"
                :disabled="reviewLoading"
                :loading="reviewAction === 'reject' && reviewLoading"
                @click="markAsRejected"
              >
                Reject
              </Button>
              <Button
                v-if="canReview"
                variant="danger"
                size="sm"
                :disabled="reviewLoading"
                :loading="reviewAction === 'tnc' && reviewLoading"
                @click="markAsTnc"
              >
                TNC
              </Button>
              <Button
                v-if="canReview"
                variant="primary"
                size="sm"
                :disabled="reviewLoading"
                :loading="reviewAction === 'review' && reviewLoading"
                @click="submitReview"
              >
                {{ localItem?.reviewed_by ? 'Update Review' : 'Submit Review' }}
              </Button>
            </div>
          </div>
        </div>
      </template>

      <template v-else-if="activeView === 'authorize'">
        <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300">
          Confirm attested status and submitted documents, then upload the employment authorization document to authorize this employee.
        </p>

        <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/40 space-y-4">
          <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h6 class="!mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Attested Status</h6>
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
            <h6 class="!mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Submitted Documents</h6>
            <div class="space-y-3">
              <div
                v-for="doc in reviewDocuments"
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
                      Uploaded: {{ doc.fileName }}
                    </p>
                  </div>
                  <a
                    v-if="doc.url"
                    :href="doc.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex shrink-0 mt-1 items-center gap-1 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                  >
                    View Document
                  </a>
                </div>
              </div>
            </div>
            <p
              v-if="localItem?.review_notes"
              class="!mb-0 !mt-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300"
            >
              <span class="font-semibold text-gray-700 dark:text-gray-200">Review Notes:</span>
              {{ localItem.review_notes }}
            </p>
          </div>
          <div
            v-if="isTncStatus"
            class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-900/20 dark:text-red-200"
          >
            <p class="!mb-1 font-semibold">This case is a TNC. The employee must submit</p>
            <p class="!mb-0">
              to resolve it. Once received and verified, mark as Authorized — otherwise it remains TNC.
            </p>
          </div>
          <div v-if="isTncStatus && canApprove" class="rounded-lg border border-red-200 bg-white p-4 dark:border-red-900 dark:bg-gray-800">
            <h6 class="!mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
              TNC Document
            </h6>
            <div class="mb-3 flex flex-col gap-1.5">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                TNC Document Type
                <span class="text-red-500">*</span>
              </label>
              <select
                v-model="authForm.tnc_doc_type"
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                :disabled="!!localItem?.authorized_by || !canApprove"
              >
                <option value="" disabled>Select document type</option>
                <optgroup
                  v-for="group in tncDocTypeOptionGroups"
                  :key="group.label"
                  :label="group.label"
                >
                  <option
                    v-for="option in group.options"
                    :key="`${group.prefix}-${option.key}`"
                    :value="encodeTncDocType(group.prefix, option.key)"
                  >
                    {{ option.value }}
                  </option>
                </optgroup>
              </select>
              <p v-if="authErrors.tnc_doc_type" class="text-sm text-red-600 dark:text-red-400">
                {{ authErrors.tnc_doc_type }}
              </p>
            </div>
            <DocumentFileField
              v-model="authForm.tnc_document_file"
              label="TNC Supporting Document"
              placeholder="Upload TNC document"
              :error="authErrors.tnc_document"
              :required="!localItem?.tnc_document_id"
              :disabled="!!localItem?.authorized_by || !canApprove"
            />
            <a
              v-if="localItem?.tnc_document_url"
              :href="localItem.tnc_document_url"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-2 inline-block text-sm text-blue-600 hover:underline dark:text-blue-400"
            >
              View current TNC document<span v-if="tncDocTypeLabel"> — {{ tncDocTypeLabel }}</span>
            </a>
          </div>
          <div v-else-if="canApprove" class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h6 class="!mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
              Employment Authorization Determination
            </h6>
            <DocumentFileField
              v-model="authForm.authorization_doc_file"
              label="Authorization Document"
              placeholder="Upload employment authorization document"
              :error="authErrors.authorization_doc"
              :required="!localItem?.authorization_doc_id"
              :disabled=" !canApprove"
            />
            <a
              v-if="localItem?.authorization_doc_url"
              :href="localItem.authorization_doc_url"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-2 inline-block text-sm text-blue-600 hover:underline dark:text-blue-400"
            >
              View current authorization document
            </a>
          </div>

          <p
            v-if="localItem?.authorized_by && localItem?.authorizer?.name"
            class="!mb-0 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
          >
            Authorized by {{ localItem.authorizer.name }}
          </p>

          <p v-if="error" class="!mb-0 text-sm text-red-600 dark:text-red-400">{{ error }}</p>

          <div class="flex justify-between gap-2 pt-2">
            <Button
              variant="outline-secondary"
              size="sm"
              :disabled="authLoading"
              @click="activeView = 'review'"
            >
              Back
            </Button>
            <Button
              v-if="canApprove && !localItem?.authorized_by"
              variant="primary"
              size="sm"
              :disabled="!canSubmitAuthorize || authLoading"
              :loading="authLoading"
              @click="submitAuthorize"
            >
              Authorize Employee
            </Button>
          </div>
        </div>
      </template>
      </template>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Textarea from '@/components/ui/textarea.vue'
import DocumentFileField from '@/components/common/DocumentFileField.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { usePermission } from '@/composables/usePermission'
import { formatDate } from '@/utils/date'
import i9Docs from '@/views/onboarding-process/docs.json'

const listDocOptions = (group) => Object.values(i9Docs[group] || {}).map((item) => ({
  key: item.key,
  value: item.value,
}))

const getDocTypeLabel = (group, key) => {
  if (!key) return ''
  return i9Docs[group]?.[key]?.value || key
}

const listADocLabel = computed(() => getDocTypeLabel('list_a_doc_title_1', docForm.value.list_a_doc_type))
const listBDocLabel = computed(() => getDocTypeLabel('list_b_doc_title', docForm.value.list_b_doc_type))
const listCDocLabel = computed(() => getDocTypeLabel('list_c_doc_title', docForm.value.list_c_doc_type))
const listAOptions = [...listDocOptions('list_a_doc_title_1'), { key: 'no_passport', value: 'No passport' }]
const listBOptions = listDocOptions('list_b_doc_title')
const listCOptions = listDocOptions('list_c_doc_title')

const tncDocTypeOptionGroups = [
  { label: 'List A', prefix: 'list_a', group: 'list_a_doc_title_1', options: [...listAOptions, { key: 'no_passport', value: 'No passport' }] },
  { label: 'List B', prefix: 'list_b', group: 'list_b_doc_title', options: listBOptions },
  { label: 'List C', prefix: 'list_c', group: 'list_c_doc_title', options: listCOptions },
]

const encodeTncDocType = (prefix, key) => `${prefix}|${key}`

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

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  item: {
    type: Object,
    default: null,
  },
  initialView: {
    type: String,
    default: 'status',
  },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const message = useMessage()
const { can } = usePermission()
const docLoading = ref(false)
const reviewLoading = ref(false)
const authLoading = ref(false)
const reviewAction = ref('review')
const detailLoading = ref(false)
const error = ref(null)
const activeView = ref('status')
const localItem = ref(null)
const employeeEditing = ref(false)
const employeeSaving = ref(false)
const employeeError = ref(null)

const emptyDocForm = () => ({
  list_a_doc_type: '',
  list_a_issuing_authority: '',
  list_a_document_number: '',
  list_a_expiration_date: '',
  list_b_doc_type: '',
  list_b_issuing_authority: '',
  list_b_document_number: '',
  list_b_expiration_date: '',
  list_c_doc_type: '',
  list_c_issuing_authority: '',
  list_c_document_number: '',
  list_c_expiration_date: '',
  list_a_file: null,
  list_b_file: null,
  list_c_file: null,
})

const docForm = ref(emptyDocForm())

const docErrors = ref({
  list_a_doc_type: null,
  list_b_doc_type: null,
  list_c_doc_type: null,
  list_a: null,
  list_b: null,
  list_c: null,
})

const reviewForm = ref({
  review_notes: '',
})

const authForm = ref({
  authorization_doc_file: null,
  tnc_doc_type: '',
  tnc_document_file: null,
})

const authErrors = ref({
  authorization_doc: null,
  tnc_doc_type: null,
  tnc_document: null,
})

const emptyEmployeeForm = () => ({
  first_name: '',
  middle_name: '',
  last_name: '',
  street: '',
  apt_number: '',
  city: '',
  state: '',
  zip: '',
  dob: '',
  ssn: '',
  email: '',
  phone: '',
  hire_date: '',
})

const employeeForm = ref(emptyEmployeeForm())

const emptyEmployeeErrors = () => ({
  first_name: null,
  middle_name: null,
  last_name: null,
  street: null,
  apt_number: null,
  city: null,
  state: null,
  zip: null,
  dob: null,
  ssn: null,
  email: null,
  phone: null,
  hire_date: null,
})

const employeeErrors = ref(emptyEmployeeErrors())

const statusOptions = [
  { id: 'citizen', number: 1, label: 'A citizen of the United States' },
  {
    id: 'national',
    number: 2,
    label: 'A noncitizen national of the United States',
    hint: '(See instructions)',
  },
  { id: 'lpr', number: 3, label: 'A lawful permanent resident' },
  { id: 'alien', number: 4, label: 'An alien authorized to work' },
]

const steps = ['Added', 'Status Selected', 'Review', 'Authorized']

const emptyForm = () => ({
  i9_choice: null,
  uscis_number: '',
  work_authorization_exp_date: '',
  alien_uscis: '',
  alien_i94: '',
  alien_passport: '',
  alien_country: '',
})

const form = ref(emptyForm())

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const employee = computed(() => localItem.value?.employee || props.item?.employee || null)

const effectiveI9Choice = computed(() => localItem.value?.i9_choice || form.value.i9_choice)

const isListAOnly = computed(() => ['citizen', 'national'].includes(effectiveI9Choice.value))
const isNoPassportSelected = computed(() => docForm.value.list_a_doc_type === 'no_passport')
const requiresListBC = computed(() => !isListAOnly.value || isNoPassportSelected.value)

const canSaveDocuments = computed(() => {
  if (isListAOnly.value && !isNoPassportSelected.value) {
    const hasType = !!docForm.value.list_a_doc_type
    const hasFile = !!docForm.value.list_a_file || !!localItem.value?.list_a_doc_id
    return hasType && hasFile
  }
  const hasBType = !!docForm.value.list_b_doc_type
  const hasCType = !!docForm.value.list_c_doc_type
  const hasB = !!docForm.value.list_b_file || !!localItem.value?.list_b_doc_id
  const hasC = !!docForm.value.list_c_file || !!localItem.value?.list_c_doc_id
  return hasBType && hasCType && hasB && hasC
})

const canReview = computed(() => can('manual-i9', 'review'))
const canApprove = computed(() => can('manual-i9', 'approve'))
const canEditEmployee = computed(() => can('manual-i9', 'update'))

const canSubmitAuthorize = computed(() => {
  if (isTncStatus.value) {
    return (!!authForm.value.tnc_document_file || !!localItem.value?.tnc_document_id)
      && !!(authForm.value.tnc_doc_type || localItem.value?.tnc_doc_type)
  }
  return !!authForm.value.authorization_doc_file || !!localItem.value?.authorization_doc_id
})

const isTncStatus = computed(() => (localItem.value?.hr_status || props.item?.hr_status) === 'tnc')

const tncDocTypeLabel = computed(() => getTncDocTypeLabel(
  authForm.value.tnc_doc_type || localItem.value?.tnc_doc_type
))

const i9ChoiceLabel = computed(() => {
  const choice = effectiveI9Choice.value
  const option = statusOptions.find((item) => item.id === choice)
  return option ? `${option.number}. ${option.label}` : '—'
})

const reviewDocuments = computed(() => {
  const item = localItem.value
  const docs = []

  if (isListAOnly.value && !isNoPassportSelected.value) {
    docs.push({
      list: 'A',
      typeLabel: listADocLabel.value,
      url: item?.list_a_url,
      fileName: docForm.value.list_a_file?.name
        || item?.list_a_document?.document_name
        || (item?.list_a_doc_id ? 'Document on file' : null),
    })
    return docs
  }

  if (docForm.value.list_b_doc_type || item?.list_b_doc_type) {
    docs.push({
      list: 'B',
      typeLabel: listBDocLabel.value,
      url: item?.list_b_url,
      fileName: docForm.value.list_b_file?.name
        || item?.list_b_document?.document_name
        || (item?.list_b_doc_id ? 'Document on file' : null),
    })
  }

  if (docForm.value.list_c_doc_type || item?.list_c_doc_type) {
    docs.push({
      list: 'C',
      typeLabel: listCDocLabel.value,
      url: item?.list_c_url,
      fileName: docForm.value.list_c_file?.name
        || item?.list_c_document?.document_name
        || (item?.list_c_doc_id ? 'Document on file' : null),
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

const currentStep = computed(() => {
  const status = localItem.value?.status || props.item?.status
  const choice = effectiveI9Choice.value
  const item = localItem.value || props.item
  if (status === 'verified' || status === 'currently_working' || item?.authorized_by) return 4
  if (item?.reviewed_by) return 4
  if (hasDocumentsUploaded(item)) return 3
  if (choice) return 2
  return 1
})

const statusChipLabel = computed(() => {
  const item = localItem.value || props.item
  if (item?.status === 'left') return 'Left / Terminated'
  if (item?.hr_status === 'incorrect_i9') return 'Incorrect I9'
  if (item?.status === 'verified' || item?.status === 'currently_working' || item?.authorized_by) {
    return 'Authorized'
  }
   if (item?.hr_status === 'tnc') return 'TNC'
  if (item?.hr_status === 'rejected') return 'Rejected'
  if (item?.hr_status === 'reviewed') return 'Reviewed'
  if (item?.hr_status === 'document_uploaded') return 'Documents Uploaded'
  if (item?.hr_status === 'pending') return 'Pending'
  if (item?.reviewed_by) return 'Reviewed'
  if (hasDocumentsUploaded(item)) return 'Documents Uploaded'
  if (effectiveI9Choice.value) return 'Status Selected'
  return 'Unselected'
})

const statusChipClass = computed(() => {
  const item = localItem.value || props.item
  if (item?.status === 'left') {
    return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
  }
  if (item?.hr_status === 'incorrect_i9') {
    return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300'
  }
  if (item?.status === 'verified' || item?.status === 'currently_working' || item?.authorized_by) {
    return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
  }
  if (item?.hr_status === 'tnc') {
    return 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300'
  }
  if (item?.hr_status === 'rejected') {
    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
  }
  if (item?.reviewed_by) {
    return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300'
  }
  if (hasDocumentsUploaded(item)) {
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
  }
  if (effectiveI9Choice.value) {
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
  }
  return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
})

const statusDotClass = computed(() => {
  const item = localItem.value || props.item
  if (item?.status === 'left') return 'bg-gray-500'
  if (item?.hr_status === 'incorrect_i9') return 'bg-orange-500'
  if (item?.status === 'verified' || item?.status === 'currently_working') return 'bg-green-500'
  if (item?.hr_status === 'tnc' || item?.hr_status === 'rejected') return 'bg-red-500'
  if (effectiveI9Choice.value) return 'bg-amber-500'
  return 'bg-gray-400'
})

const REVIEW_STEP_INDEX = 2

const rejectedOrTncStatus = computed(() => {
  const item = localItem.value || props.item
  if (item?.hr_status === 'rejected') return 'rejected'
  if (item?.hr_status === 'tnc') return 'tnc'
  return null
})

const stepLabel = (index, fallback) => {
  if (index === REVIEW_STEP_INDEX) {
    if (rejectedOrTncStatus.value === 'rejected') return 'Rejected'
    if (rejectedOrTncStatus.value === 'tnc') return 'TNC'
  }
  return fallback
}

const stepBubbleContent = (index) => {
  const n = index + 1
  if (index === REVIEW_STEP_INDEX && rejectedOrTncStatus.value && n < currentStep.value) {
    return '✗'
  }
  return n < currentStep.value ? '✓' : n
}

const stepTextClass = (index) => {
  const n = index + 1
  if (index === REVIEW_STEP_INDEX && rejectedOrTncStatus.value && n < currentStep.value) {
    return 'text-red-600 dark:text-red-400'
  }
  if (n < currentStep.value) return 'text-green-600 dark:text-green-400'
  if (n === currentStep.value) return 'text-primary dark:text-emerald-400'
  return 'text-gray-400 dark:text-gray-500'
}

const stepBubbleClass = (index) => {
  const n = index + 1
  if (index === REVIEW_STEP_INDEX && rejectedOrTncStatus.value && n < currentStep.value) {
    return 'border-red-500 bg-red-500 text-white'
  }
  if (n < currentStep.value) {
    return 'border-green-500 bg-green-500 text-white'
  }
  if (n === currentStep.value) {
    return 'border-primary bg-primary text-white dark:border-emerald-400 dark:bg-emerald-400'
  }
  return 'border-gray-200 bg-white text-gray-400 dark:border-gray-600 dark:bg-gray-800'
}

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

const resetDocFiles = () => {
  docForm.value.list_a_file = null
  docForm.value.list_b_file = null
  docForm.value.list_c_file = null
}

const resetDocForm = () => {
  docForm.value = emptyDocForm()
  docErrors.value = {
    list_a_doc_type: null,
    list_b_doc_type: null,
    list_c_doc_type: null,
    list_a: null,
    list_b: null,
    list_c: null,
  }
}

const hasDocumentsUploaded = (item) => {
  if (!item?.i9_choice) return false
  if (['citizen', 'national'].includes(item.i9_choice)) {
    return !!item.list_a_doc_id
  }
  return !!item.list_b_doc_id && !!item.list_c_doc_id
}

const resolveInitialView = (item) => {

  console.log(canReview)
  if (!item || item.status === 'left') {
    return 'status'
  }

  if (props.initialView === 'authorize' && canApprove.value) {
    return 'authorize'
  }

  if (props.initialView === 'review' && canReview.value) {
    return 'review'
  }

  if (props.initialView === 'documents' && item.i9_choice) {
    return 'documents'
  }

  if (item.hr_status === 'rejected' && item.i9_choice) {
    return 'documents'
  }
  
  if (item.authorized_by || item.status === 'verified' || item.status === 'currently_working') {
    return canApprove.value ? 'authorize' : (canReview.value ? 'review' : 'documents')
  }

  if (item.reviewed_by) {
    return canApprove.value ? 'authorize' : (canReview.value ? 'review' : 'documents')
  }

  if (hasDocumentsUploaded(item) && canReview.value) {
    return 'review'
  }

  if (item.i9_choice) {
    return 'documents'
  }

  return 'status'
}

const applyItemToForms = (item) => {
  if (!item) return

  form.value = emptyForm()
  form.value.i9_choice = item.i9_choice || null
  form.value.uscis_number = item.uscis_number || ''
  form.value.work_authorization_exp_date = item.work_authorization_exp_date
    ? String(item.work_authorization_exp_date).slice(0, 10)
    : ''

  if (item.i9_choice === 'alien' && item.uscis_number) {
    const value = item.uscis_number
    if (value.startsWith('I94:')) {
      form.value.alien_i94 = value.replace(/^I94:/, '')
    } else if (value.startsWith('FP:')) {
      const parts = value.replace(/^FP:/, '').split('|')
      form.value.alien_passport = parts[0] || ''
      form.value.alien_country = parts[1] || ''
    } else {
      form.value.alien_uscis = value
    }
  } else if (item.i9_choice === 'lpr') {
    form.value.uscis_number = item.uscis_number || ''
  }

  resetDocFiles()
  docForm.value.list_a_doc_type = item.list_a_doc_type || ''
  docForm.value.list_a_issuing_authority = item.list_a_issuing_authority || ''
  docForm.value.list_a_document_number = item.list_a_document_number || ''
  docForm.value.list_a_expiration_date = item.list_a_expiration_date ? String(item.list_a_expiration_date).slice(0, 10) : ''
  docForm.value.list_b_doc_type = item.list_b_doc_type || ''
  docForm.value.list_b_issuing_authority = item.list_b_issuing_authority || ''
  docForm.value.list_b_document_number = item.list_b_document_number || ''
  docForm.value.list_b_expiration_date = item.list_b_expiration_date ? String(item.list_b_expiration_date).slice(0, 10) : ''
  docForm.value.list_c_doc_type = item.list_c_doc_type || ''
  docForm.value.list_c_issuing_authority = item.list_c_issuing_authority || ''
  docForm.value.list_c_document_number = item.list_c_document_number || ''
  docForm.value.list_c_expiration_date = item.list_c_expiration_date ? String(item.list_c_expiration_date).slice(0, 10) : ''
  reviewForm.value.review_notes = item.review_notes || ''
  authForm.value.authorization_doc_file = null
  authForm.value.tnc_doc_type = item.tnc_doc_type || ''
  authForm.value.tnc_document_file = null
  authErrors.value.authorization_doc = null
  authErrors.value.tnc_doc_type = null
  authErrors.value.tnc_document = null
}

const startEditEmployee = () => {
  const e = employee.value
  employeeErrors.value = emptyEmployeeErrors()
  employeeError.value = null
  employeeForm.value = {
    first_name: e?.first_name || '',
    middle_name: e?.middle_name || '',
    last_name: e?.last_name || '',
    street: e?.street || '',
    apt_number: e?.apt_number || '',
    city: e?.city || '',
    state: e?.state || '',
    zip: e?.zip || '',
    dob: e?.dob ? String(e.dob).slice(0, 10) : '',
    ssn: e?.ssn || '',
    email: e?.email || '',
    phone: e?.phone || '',
    hire_date: e?.hire_date ? String(e.hire_date).slice(0, 10) : '',
  }
  employeeEditing.value = true
}

const cancelEditEmployee = () => {
  employeeEditing.value = false
  employeeError.value = null
  employeeErrors.value = emptyEmployeeErrors()
}

const saveEmployee = async () => {
  employeeError.value = null
  employeeErrors.value = emptyEmployeeErrors()

  const confirmationId = localItem.value?.id || props.item?.id
  if (!confirmationId) return

  if (!employeeForm.value.first_name?.trim()) {
    employeeErrors.value.first_name = 'First name is required.'
  }
  if (!employeeForm.value.last_name?.trim()) {
    employeeErrors.value.last_name = 'Last name is required.'
  }
  if (!employeeForm.value.street?.trim()) {
    employeeErrors.value.street = 'Street is required.'
  }
  if (!employeeForm.value.city?.trim()) {
    employeeErrors.value.city = 'City is required.'
  }
  if (!employeeForm.value.state?.trim()) {
    employeeErrors.value.state = 'State is required.'
  }
  if (!employeeForm.value.hire_date) {
    employeeErrors.value.hire_date = 'Date of joining is required.'
  }
  if (Object.values(employeeErrors.value).some(Boolean)) return

  employeeSaving.value = true
  try {
    const response = await useRequest('post', `onboarding/employee-confirmation/${confirmationId}/employee`, {
      first_name: employeeForm.value.first_name?.trim(),
      middle_name: employeeForm.value.middle_name?.trim() || null,
      last_name: employeeForm.value.last_name?.trim(),
      street: employeeForm.value.street?.trim(),
      apt_number: employeeForm.value.apt_number?.trim() || null,
      city: employeeForm.value.city?.trim(),
      state: employeeForm.value.state?.trim(),
      zip: employeeForm.value.zip?.trim() || null,
      dob: employeeForm.value.dob || null,
      ssn: employeeForm.value.ssn?.trim() || null,
      email: employeeForm.value.email?.trim() || null,
      phone: employeeForm.value.phone?.trim() || null,
      hire_date: employeeForm.value.hire_date,
    })

    if (response?.saved) {
      message.success(response.message || 'Employee details updated successfully.')
      localItem.value = response.model || response.data
      applyItemToForms(localItem.value)
      emit('saved', localItem.value)
      employeeEditing.value = false
    } else {
      employeeError.value = response?.message || 'Failed to update employee details.'
    }
  } catch (err) {
    const errors = err?.response?.data?.errors
    if (errors) {
      Object.keys(employeeErrors.value).forEach((key) => {
        employeeErrors.value[key] = errors[key]?.[0] || null
      })
      employeeError.value = Object.values(errors).flat()[0] || 'Validation failed.'
    } else {
      employeeError.value = err?.response?.data?.message || 'Failed to update employee details.'
    }
    message.error(err?.response?.data?.message || 'Failed to update employee details.')
  } finally {
    employeeSaving.value = false
  }
}

const hydrateForm = async () => {
  error.value = null
  resetDocForm()
  localItem.value = props.item ? { ...props.item } : null
  activeView.value = 'status'
  employeeEditing.value = false
  employeeSaving.value = false
  employeeError.value = null

  if (!props.item?.id) return

  detailLoading.value = true
  try {
    const response = await useRequest('get', `onboarding/employee-confirmation/${props.item.id}`)
    const item = response?.model || response?.data || response
    localItem.value = item
    applyItemToForms(item)
    activeView.value = resolveInitialView(item)
  } catch (err) {
    applyItemToForms(props.item)
    activeView.value = resolveInitialView(props.item)
    message.error(err?.response?.data?.message || 'Failed to load employee confirmation details.')
  } finally {
    detailLoading.value = false
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) hydrateForm()
  }
)

watch(
  () => props.item?.id,
  () => {
    if (props.modelValue) hydrateForm()
  }
)

watch(
  () => docForm.value.list_a_doc_type,
  (docType) => {
    if (docType === 'no_passport') {
      docForm.value.list_a_file = null
      docErrors.value.list_a = null
    }
  }
)

const close = () => {
  visible.value = false
  error.value = null
  activeView.value = 'status'
  employeeEditing.value = false
  employeeSaving.value = false
  employeeError.value = null
  resetDocForm()
  reviewForm.value.review_notes = ''
  authForm.value.authorization_doc_file = null
  authForm.value.tnc_doc_type = ''
  authForm.value.tnc_document_file = null
  authErrors.value.authorization_doc = null
  authErrors.value.tnc_doc_type = null
  authErrors.value.tnc_document = null
}

const resolveI9ChoicePayload = () => {
  if (!form.value.i9_choice) {
    error.value = 'Please select an I-9 status.'
    return null
  }

  let uscisNumber = null
  let expDate = null

  if (form.value.i9_choice === 'lpr') {
    uscisNumber = form.value.uscis_number?.trim()
    if (!uscisNumber) {
      error.value = 'For Item 3, enter the USCIS or A-Number.'
      return null
    }
  }

  if (form.value.i9_choice === 'alien') {
    const uscis = form.value.alien_uscis?.trim()
    const i94 = form.value.alien_i94?.trim()
    const passport = form.value.alien_passport?.trim()
    const country = form.value.alien_country?.trim()

    if (uscis) {
      uscisNumber = uscis
    } else if (i94) {
      uscisNumber = `I94:${i94}`
    } else if (passport) {
      uscisNumber = `FP:${passport}|${country || ''}`
    } else {
      error.value = 'For Item 4, enter one of: USCIS A-Number, Form I-94 number, or Foreign Passport + Country.'
      return null
    }

    expDate = form.value.work_authorization_exp_date || null
  }

  return {
    i9_choice: form.value.i9_choice,
    uscis_number: uscisNumber,
    work_authorization_exp_date: expDate,
  }
}

const continueToDocuments = () => {
  error.value = null

  if (!resolveI9ChoicePayload()) return

  activeView.value = 'documents'
}

const saveDocuments = async () => {
  error.value = null
  docErrors.value = {
    list_a_doc_type: null,
    list_b_doc_type: null,
    list_c_doc_type: null,
    list_a: null,
    list_b: null,
    list_c: null,
  }

  const confirmationId = localItem.value?.id || props.item?.id
  if (!confirmationId) return

  const i9Payload = resolveI9ChoicePayload()
  if (!i9Payload) {
    activeView.value = 'status'
    return
  }

  if (isListAOnly.value && !isNoPassportSelected.value) {
    if (!docForm.value.list_a_doc_type) {
      docErrors.value.list_a_doc_type = 'Please select a List A document type.'
    }
    if (!docForm.value.list_a_file && !localItem.value?.list_a_doc_id) {
      docErrors.value.list_a = 'List A document is required.'
    }
    if (docErrors.value.list_a_doc_type || docErrors.value.list_a) return
  } else {
    if (!docForm.value.list_b_doc_type) {
      docErrors.value.list_b_doc_type = 'Please select a List B document type.'
    }
    if (!docForm.value.list_c_doc_type) {
      docErrors.value.list_c_doc_type = 'Please select a List C document type.'
    }
    if (!docForm.value.list_b_file && !localItem.value?.list_b_doc_id) {
      docErrors.value.list_b = 'List B document is required.'
    }
    if (!docForm.value.list_c_file && !localItem.value?.list_c_doc_id) {
      docErrors.value.list_c = 'List C document is required.'
    }
    if (docErrors.value.list_b_doc_type || docErrors.value.list_c_doc_type || docErrors.value.list_b || docErrors.value.list_c) {
      return
    }
  }

  const payload = new FormData()
  payload.append('i9_choice', i9Payload.i9_choice)
  if (i9Payload.uscis_number) payload.append('uscis_number', i9Payload.uscis_number)
  if (i9Payload.work_authorization_exp_date) payload.append('work_authorization_exp_date', i9Payload.work_authorization_exp_date)
  if (docForm.value.list_a_doc_type) payload.append('list_a_doc_type', docForm.value.list_a_doc_type)
  if (listADocLabel.value) payload.append('list_a_doc_name', listADocLabel.value)
  if (docForm.value.list_a_issuing_authority) payload.append('list_a_issuing_authority', docForm.value.list_a_issuing_authority)
  if (docForm.value.list_a_document_number) payload.append('list_a_document_number', docForm.value.list_a_document_number)
  if (docForm.value.list_a_expiration_date) payload.append('list_a_expiration_date', docForm.value.list_a_expiration_date)
  if (docForm.value.list_b_doc_type) payload.append('list_b_doc_type', docForm.value.list_b_doc_type)
  if (listBDocLabel.value) payload.append('list_b_doc_name', listBDocLabel.value)
  if (docForm.value.list_b_issuing_authority) payload.append('list_b_issuing_authority', docForm.value.list_b_issuing_authority)
  if (docForm.value.list_b_document_number) payload.append('list_b_document_number', docForm.value.list_b_document_number)
  if (docForm.value.list_b_expiration_date) payload.append('list_b_expiration_date', docForm.value.list_b_expiration_date)
  if (docForm.value.list_c_doc_type) payload.append('list_c_doc_type', docForm.value.list_c_doc_type)
  if (listCDocLabel.value) payload.append('list_c_doc_name', listCDocLabel.value)
  if (docForm.value.list_c_issuing_authority) payload.append('list_c_issuing_authority', docForm.value.list_c_issuing_authority)
  if (docForm.value.list_c_document_number) payload.append('list_c_document_number', docForm.value.list_c_document_number)
  if (docForm.value.list_c_expiration_date) payload.append('list_c_expiration_date', docForm.value.list_c_expiration_date)
  if (docForm.value.list_a_file) payload.append('list_a_file', docForm.value.list_a_file)
  if (docForm.value.list_b_file) payload.append('list_b_file', docForm.value.list_b_file)
  if (docForm.value.list_c_file) payload.append('list_c_file', docForm.value.list_c_file)

  docLoading.value = true
  try {
    const response = await useRequest('post', `onboarding/employee-confirmation/${confirmationId}/step2`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    if (response?.saved) {
      message.success(response.message || 'I-9 status and documents saved successfully.')
      localItem.value = response.model || response.data
      applyItemToForms(localItem.value)
      emit('saved', localItem.value)
      activeView.value = canReview.value ? 'review' : 'documents'
    } else {
      message.error(response?.message || 'Failed to save I-9 status and documents.')
    }
  } catch (err) {
    const errors = err?.response?.data?.errors
    if (errors) {
      docErrors.value.list_a_doc_type = errors.list_a_doc_type?.[0] || null
      docErrors.value.list_b_doc_type = errors.list_b_doc_type?.[0] || null
      docErrors.value.list_c_doc_type = errors.list_c_doc_type?.[0] || null
      docErrors.value.list_a = errors.list_a_file?.[0] || null
      docErrors.value.list_b = errors.list_b_file?.[0] || null
      docErrors.value.list_c = errors.list_c_file?.[0] || null
      error.value = Object.values(errors).flat()[0]
        || errors.i9_choice?.[0]
        || errors.uscis_number?.[0]
        || null
    }
    message.error(err?.response?.data?.message || 'Failed to save I-9 status and documents.')
  } finally {
    docLoading.value = false
  }
}

const submitReview = async () => {
  if (!canReview.value) return

  error.value = null
  const confirmationId = localItem.value?.id || props.item?.id
  if (!confirmationId) return

  reviewLoading.value = true
  reviewAction.value = 'review'
  try {
    const response = await useRequest('post', `onboarding/employee-confirmation/${confirmationId}/review`, {
      review_notes: reviewForm.value.review_notes?.trim() || null,
    })

    if (response?.saved) {
      message.success(response.message || 'Review submitted successfully.')
      localItem.value = response.model || response.data
      applyItemToForms(localItem.value)
      emit('saved', localItem.value)
      activeView.value = canApprove.value ? 'authorize' : 'review'
    } else {
      message.error(response?.message || 'Failed to submit review.')
    }
  } catch (err) {
    const errors = err?.response?.data?.errors
    if (errors) {
      error.value = Object.values(errors).flat()[0] || 'Validation failed.'
    }
    message.error(err?.response?.data?.message || 'Failed to submit review.')
  } finally {
    reviewLoading.value = false
    reviewAction.value = 'review'
  }
}

const markAsRejected = async () => {
  if (!canReview.value) return
  error.value = null
  reviewLoading.value = true
  reviewAction.value = 'reject'
  const confirmationId = localItem.value?.id || props.item?.id
  try {
    const response = await useRequest('post', `onboarding/employee-confirmation/${confirmationId}/reject`)
    if (response?.saved) {
      message.success(response.message || 'Employee marked as rejected.')
      localItem.value = response.model || response.data
      applyItemToForms(localItem.value)
      emit('saved', localItem.value)
    }
  } catch (err) {
    message.error(err?.response?.data?.message || 'Failed to mark employee as rejected.')
  } finally {
    reviewLoading.value = false
    reviewAction.value = 'review'
  }
}

const markAsTnc = async () => {
  if (!canReview.value) return
  error.value = null
  reviewLoading.value = true
  reviewAction.value = 'tnc'
  const confirmationId = localItem.value?.id || props.item?.id
  try {
    const response = await useRequest('post', `onboarding/employee-confirmation/${confirmationId}/tnc`)
    if (response?.saved) {
      message.success(response.message || 'Employee marked as TNC.')
      localItem.value = response.model || response.data
      applyItemToForms(localItem.value)
      emit('saved', localItem.value)
      activeView.value = canApprove.value ? 'authorize' : 'review'
    }
  } catch (err) {
    message.error(err?.response?.data?.message || 'Failed to mark employee as TNC.')
  } finally {
    reviewLoading.value = false
    reviewAction.value = 'review'
  }
}

const submitAuthorize = async () => {
  if (!canApprove.value) return

  error.value = null
  authErrors.value.authorization_doc = null
  authErrors.value.tnc_doc_type = null
  authErrors.value.tnc_document = null

  const confirmationId = localItem.value?.id || props.item?.id
  if (!confirmationId) return

  if (isTncStatus.value) {
    if (!authForm.value.tnc_doc_type && !localItem.value?.tnc_doc_type) {
      authErrors.value.tnc_doc_type = 'Please select a TNC document type.'
      return
    }
    if (!authForm.value.tnc_document_file && !localItem.value?.tnc_document_id) {
      authErrors.value.tnc_document = 'TNC document is required.'
      return
    }
  } else if (!authForm.value.authorization_doc_file && !localItem.value?.authorization_doc_id) {
    authErrors.value.authorization_doc = 'Employment authorization document is required.'
    return
  }

  const payload = new FormData()
  if (authForm.value.tnc_doc_type) {
    payload.append('tnc_doc_type', authForm.value.tnc_doc_type)
  }
  if (authForm.value.tnc_document_file) {
    payload.append('tnc_document_file', authForm.value.tnc_document_file)
  }
  if (authForm.value.authorization_doc_file) {
    payload.append('authorization_doc_file', authForm.value.authorization_doc_file)
  }

  authLoading.value = true
  try {
    const response = await useRequest('post', `onboarding/employee-confirmation/${confirmationId}/authorize`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    if (response?.saved) {
      message.success(response.message || 'Employee authorized successfully.')
      localItem.value = response.model || response.data
      applyItemToForms(localItem.value)
      emit('saved', localItem.value)
      close()
    } else {
      message.error(response?.message || 'Failed to authorize employee.')
    }
  } catch (err) {
    const errors = err?.response?.data?.errors
    if (errors) {
      authErrors.value.authorization_doc = errors.authorization_doc_file?.[0] || null
      authErrors.value.tnc_doc_type = errors.tnc_doc_type?.[0] || null
      authErrors.value.tnc_document = errors.tnc_document_file?.[0] || null
      error.value = Object.values(errors).flat()[0] || null
    }
    message.error(err?.response?.data?.message || 'Failed to authorize employee.')
  } finally {
    authLoading.value = false
  }
}
</script>

<style scoped>
.border-primary {
  border-color: var(--color-primary);
}

.bg-primary {
  background-color: var(--color-primary);
}

.bg-primary\/5 {
  background-color: color-mix(in srgb, var(--color-primary) 5%, transparent);
}

.text-primary {
  color: var(--color-primary);
}
</style>
