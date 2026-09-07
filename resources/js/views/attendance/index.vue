<template>
  <div
    class="flex min-h-screen items-center justify-center bg-gray-100 bg-[radial-gradient(circle_at_top_left,rgba(16,185,129,0.18),transparent_40%),radial-gradient(circle_at_bottom_right,rgba(59,130,246,0.14),transparent_35%)] p-6"
  >
    <div class="w-full max-w-md">
      <header class="mb-5 text-center">
        <p class="mb-1.5 text-xs font-bold uppercase tracking-widest text-emerald-600">
          Time Clock
        </p>
        <h1 class="m-0 text-3xl font-extrabold text-gray-900">Attendance</h1>
        <p class="mt-2 text-[0.95rem] text-gray-500">
          Enter your employee code to check in, take a break, or check out.
        </p>
        <button
          v-if="canInstall"
          type="button"
          class="mt-4 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800 transition hover:bg-emerald-100"
          @click="installApp"
        >
          Install app
        </button>
      </header>

      <section
        v-if="!user"
        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.06)]"
      >
        <form @submit.prevent="verifyCode" class="space-y-4">
          <Input
            v-model="employeeCode"
            label="Employee Code"
            type="text"
            inputmode="numeric"
            maxlength="6"
            placeholder="6-digit code"
            required
            :error="error"
            @update:model-value="onCodeInput"
          />
          <Button
            type="submit"
            variant="primary"
            class="w-full"
            :loading="loading"
            :disabled="employeeCode.length !== 6"
          >
            Continue
          </Button>
        </form>
      </section>

      <section
        v-else
        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.06)]"
      >
        <div class="mb-4">
          <p class="m-0 text-xs uppercase tracking-wider text-gray-500">Signed in as</p>
          <h2 class="mt-0.5 text-xl text-gray-900">{{ user.name }}</h2>
          <p class="mt-0.5 text-sm text-gray-500">Code {{ user.user_code }}</p>
        </div>

        <div class="mb-4 rounded-xl border p-4" :class="statusPanelClass">
          <p class="m-0 text-xs uppercase tracking-wider text-gray-500">Current status</p>
          <p class="mt-1 text-[1.35rem] font-bold text-gray-900">{{ statusLabel }}</p>

          <template v-if="isCheckedOut">
            <p class="mt-1.5 text-sm text-gray-600">
              Checked in {{ formatTime(attendance.check_in) }}
              <span v-if="attendance.check_out"> · Checked out {{ formatTime(attendance.check_out) }}</span>
            </p>
            <p class="mt-1.5 text-base font-semibold text-gray-800">
              Worked {{ formatMinutes(attendance.work_minutes ?? attendance.elapsed_work_minutes) }}
            </p>
            <p v-if="attendance.break_minutes" class="mt-1 text-sm text-gray-600">
              Break total: {{ formatMinutes(attendance.break_minutes) }}
            </p>
            <p class="mt-2 text-sm font-medium text-slate-700">
              No more activity for today. You can check in again tomorrow.
            </p>
          </template>
          <template v-else>
            <p v-if="attendance?.check_in" class="mt-1.5 text-sm text-gray-600">
              Checked in {{ formatTime(attendance.check_in) }}
              <span v-if="elapsedLabel"> · {{ elapsedLabel }} worked</span>
            </p>
            <p v-if="attendance?.break_minutes" class="mt-1.5 text-sm text-gray-600">
              Break total: {{ formatMinutes(attendance.break_minutes) }}
            </p>
          </template>
        </div>

        <p
          v-if="message"
          class="mb-4 rounded-lg border px-3.5 py-3 text-sm"
          :class="messageType === 'success'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
            : 'border-amber-200 bg-amber-50 text-amber-900'"
        >
          {{ message }}
        </p>
        <p
          v-if="error"
          class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-red-800"
        >
          {{ error }}
        </p>

        <div class="grid gap-3">
          <Button
            v-if="!attendance"
            variant="primary"
            class="w-full"
            :loading="loading"
            @click="runAction('check-in')"
          >
            Check In
          </Button>

          <template v-else-if="!isCheckedOut">
            <Button
              v-if="attendance.status === 'checked_in'"
              variant="outline-secondary"
              class="w-full"
              :loading="loading"
              @click="runAction('start-break')"
            >
              Start Break
            </Button>
            <Button
              v-if="attendance.status === 'on_break'"
              variant="primary"
              class="w-full"
              :loading="loading"
              @click="runAction('end-break')"
            >
              End Break
            </Button>
            <Button
              v-if="attendance.status === 'checked_in'"
              variant="danger"
              class="w-full"
              :loading="loading"
              @click="openCheckoutWarning"
            >
              Check Out
            </Button>
          </template>

          <Button
            v-if="isCheckedOut"
            variant="outline-secondary"
            class="w-full"
            @click="switchUser"
          >
            Done
          </Button>
        </div>
      </section>
    </div>

    <Modal
      v-model="showCheckoutModal"
      title="Confirm check out"
      size="md"
      :show-footer="true"
      :show-cancel="true"
      :show-confirm="true"
      cancel-text="Cancel"
      confirm-text="Check Out"
      :loading="loading"
      :close-on-backdrop="false"
      @confirm="confirmCheckout"
      @close="showCheckoutModal = false"
    >
      <div class="space-y-4">
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
          After you check out, you cannot check in again for today. You can check in again tomorrow.
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
          <p class="m-0 text-xs uppercase tracking-wider text-gray-500">Working time today</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ elapsedLabel || '0m' }}</p>
          <p class="mt-2 text-sm text-gray-600">
            Checked in at {{ formatTime(attendance?.check_in) }}
            <span v-if="attendance?.break_minutes">
              · Break {{ formatMinutes(attendance.break_minutes) }}
            </span>
          </p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import axios from 'axios'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Modal from '@/components/common/Modal.vue'

const STORAGE_KEY = 'attendanceEmployeeCode'

const employeeCode = ref('')
const user = ref(null)
const attendance = ref(null)
const loading = ref(false)
const error = ref('')
const message = ref('')
const messageType = ref('success')
const showCheckoutModal = ref(false)
const nowTick = ref(Date.now())
const canInstall = ref(false)
let installPromptEvent = null
let tickTimer = null
let coords = { latitude: null, longitude: null }

function todayKey() {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function saveEmployeeCode(code) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify({ code, date: todayKey() }))
}

function loadEmployeeCode() {
  const raw = localStorage.getItem(STORAGE_KEY)
  if (!raw) return null

  try {
    const parsed = JSON.parse(raw)
    if (parsed?.code && /^\d{6}$/.test(parsed.code) && parsed.date === todayKey()) {
      return parsed.code
    }
  } catch {
    // Legacy plain-string storage is treated as expired.
  }

  localStorage.removeItem(STORAGE_KEY)
  return null
}

const csrfHeaders = () => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  return token ? { 'X-CSRF-TOKEN': token } : {}
}

const isCheckedOut = computed(() => attendance.value?.status === 'checked_out')

const statusLabel = computed(() => {
  if (!attendance.value) return 'Not checked in'
  if (attendance.value.status === 'on_break') return 'On break'
  if (attendance.value.status === 'checked_in') return 'Checked in'
  if (attendance.value.status === 'checked_out') return 'Checked out'
  return attendance.value.status
})

const statusPanelClass = computed(() => {
  if (!attendance.value) return 'border-gray-200 bg-gray-50'
  if (attendance.value.status === 'on_break') return 'border-amber-200 bg-amber-50'
  if (attendance.value.status === 'checked_in') return 'border-emerald-200 bg-emerald-50'
  if (attendance.value.status === 'checked_out') return 'border-slate-300 bg-slate-50'
  return 'border-gray-200 bg-gray-50'
})

const elapsedLabel = computed(() => {
  if (!attendance.value?.check_in) return ''
  void nowTick.value

  if (attendance.value.status === 'checked_out') {
    return formatMinutes(attendance.value.work_minutes ?? attendance.value.elapsed_work_minutes)
  }

  const start = new Date(attendance.value.check_in).getTime()
  let breakMs = (attendance.value.break_minutes || 0) * 60 * 1000
  if (attendance.value.status === 'on_break' && attendance.value.active_break?.start_time) {
    breakMs += Math.max(0, Date.now() - new Date(attendance.value.active_break.start_time).getTime())
  }
  const end = attendance.value.check_out ? new Date(attendance.value.check_out).getTime() : Date.now()
  const minutes = Math.max(0, Math.floor((end - start - breakMs) / 60000))
  return formatMinutes(minutes)
})

function onCodeInput(value) {
  employeeCode.value = String(value || '').replace(/\D/g, '').slice(0, 6)
  error.value = ''
}

const DEFAULT_ATTENDANCE_TIMEZONE = 'America/New_York'

function formatTime(value) {
  if (!value) return ''
  return new Date(value).toLocaleTimeString([], {
    hour: '2-digit',
    minute: '2-digit',
    timeZone: user.value?.timezone || DEFAULT_ATTENDANCE_TIMEZONE,
  })
}

function formatMinutes(minutes) {
  const total = Math.max(0, Number(minutes) || 0)
  const hours = Math.floor(total / 60)
  const mins = total % 60
  if (hours <= 0) return `${mins}m`
  return `${hours}h ${mins}m`
}

async function requestLocation() {
  if (!navigator.geolocation) {
    return false
  }

  try {
    const position = await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
      })
    })
    coords = {
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
    }
    return true
  } catch {
    coords = { latitude: null, longitude: null }
    return false
  }
}

async function verifyCode() {
  error.value = ''
  message.value = ''
  if (employeeCode.value.length !== 6) {
    error.value = 'Enter your 6-digit employee code.'
    return
  }

  loading.value = true
  try {
    const { data } = await axios.post(
      '/attendance/verify',
      { employee_code: employeeCode.value },
      { headers: csrfHeaders(), withCredentials: true }
    )
    user.value = data.user
    attendance.value = data.attendance
    saveEmployeeCode(employeeCode.value)
    message.value = data.message || 'Welcome.'
    messageType.value = data.attendance?.status === 'checked_out' ? 'warning' : 'success'
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to verify code.'
    user.value = null
    attendance.value = null
  } finally {
    loading.value = false
  }
}

async function refreshStatus() {
  if (!employeeCode.value) return
  try {
    const { data } = await axios.get('/attendance/status', {
      params: { employee_code: employeeCode.value },
      headers: csrfHeaders(),
      withCredentials: true,
    })
    user.value = data.user
    attendance.value = data.attendance
    if (data.attendance?.status === 'checked_out') {
      message.value = 'You have already checked out for today. You can check in again tomorrow.'
      messageType.value = 'warning'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load attendance status.'
    switchUser()
  }
}

function openCheckoutWarning() {
  error.value = ''
  message.value = ''
  showCheckoutModal.value = true
}

async function confirmCheckout() {
  await runAction('check-out')
}

async function runAction(action) {
  error.value = ''
  message.value = ''
  loading.value = true

  if (action === 'check-in') {
    const gotLocation = await requestLocation()
    if (!gotLocation || coords.latitude === null || coords.longitude === null) {
      error.value = 'Location access is required to check in. Please enable location services and try again.'
      loading.value = false
      return
    }
  }

  const endpoints = {
    'check-in': '/attendance/check-in',
    'check-out': '/attendance/check-out',
    'start-break': '/attendance/start-break',
    'end-break': '/attendance/end-break',
  }

  try {
    const payload = { employee_code: employeeCode.value }
    if (action === 'check-in') {
      payload.latitude = coords.latitude
      payload.longitude = coords.longitude
    }

    const { data } = await axios.post(endpoints[action], payload, {
      headers: csrfHeaders(),
      withCredentials: true,
    })

    user.value = data.user
    attendance.value = data.attendance
    message.value = data.message
    messageType.value = 'success'

    if (action === 'check-out') {
      showCheckoutModal.value = false
      message.value = `Checked out successfully. You worked ${formatMinutes(data.attendance?.work_minutes)}. You can check in again tomorrow.`
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Action failed. Please try again.'
    if (err.response?.data?.attendance) {
      attendance.value = err.response.data.attendance
    }
    if (err.response?.data?.user) {
      user.value = err.response.data.user
    }
  } finally {
    loading.value = false
  }
}

function switchUser() {
  user.value = null
  attendance.value = null
  employeeCode.value = ''
  error.value = ''
  message.value = ''
  showCheckoutModal.value = false
  localStorage.removeItem(STORAGE_KEY)
}

async function installApp() {
  if (!installPromptEvent) return

  installPromptEvent.prompt()
  await installPromptEvent.userChoice
  installPromptEvent = null
  canInstall.value = false
}

function onBeforeInstallPrompt(event) {
  event.preventDefault()
  installPromptEvent = event
  canInstall.value = true
}

function onAppInstalled() {
  installPromptEvent = null
  canInstall.value = false
}

onMounted(async () => {
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true
  if (!isStandalone) {
    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt)
    window.addEventListener('appinstalled', onAppInstalled)
  }

  tickTimer = setInterval(() => {
    nowTick.value = Date.now()
  }, 30000)

  const saved = loadEmployeeCode()
  if (saved) {
    employeeCode.value = saved
    loading.value = true
    await refreshStatus()
    loading.value = false
  }
})

onUnmounted(() => {
  window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt)
  window.removeEventListener('appinstalled', onAppInstalled)
  if (tickTimer) clearInterval(tickTimer)
})
</script>
