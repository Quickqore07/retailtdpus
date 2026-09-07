<template>
  <div ref="rootRef" class="relative inline-flex">
    <button
      type="button"
      class="inline-flex items-center justify-center rounded-md p-1.5 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100"
      :aria-expanded="open"
      aria-haspopup="true"
      :title="title"
      @click.stop="toggle"
    >
      <slot name="icon">
        <SvgIcon :name="icon" :size="iconSize" />
      </slot>
    </button>
    <Teleport to="body">
      <div
        v-show="open"
        ref="menuRef"
        class="icon-menu-dropdown__panel fixed z-[200] min-w-[10rem] rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-600 dark:bg-gray-800"
        :style="menuStyle"
        role="menu"
        @click.stop
      >
        <slot :close="close" />
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import SvgIcon from '@/components/SvgIcon.vue'

const props = defineProps({
  icon: {
    type: String,
    default: 'more-vertical',
  },
  iconSize: {
    type: String,
    default: 'md',
  },
  title: {
    type: String,
    default: 'More actions',
  },
  align: {
    type: String,
    default: 'end',
    validator: (v) => ['start', 'end'].includes(v),
  },
})

const open = ref(false)
const rootRef = ref(null)
const menuRef = ref(null)
const menuStyle = ref({})

function close() {
  open.value = false
}

function updatePosition() {
  if (!rootRef.value || !open.value) return
  const rect = rootRef.value.getBoundingClientRect()
  const menuEl = menuRef.value
  const menuWidth = menuEl?.offsetWidth ?? 180
  const menuHeight = menuEl?.offsetHeight ?? 0
  const gap = 4
  const pad = 8

  let left =
    props.align === 'end' ? rect.right - menuWidth : rect.left
  let top = rect.bottom + gap

  left = Math.max(pad, Math.min(left, window.innerWidth - menuWidth - pad))
  if (top + menuHeight > window.innerHeight - pad && rect.top - menuHeight - gap >= pad) {
    top = rect.top - menuHeight - gap
  }

  menuStyle.value = {
    top: `${top}px`,
    left: `${left}px`,
  }
}

function toggle() {
  open.value = !open.value
  if (open.value) {
    nextTick(() => {
      updatePosition()
      requestAnimationFrame(() => updatePosition())
    })
  }
}

function onScrollOrResize() {
  if (open.value) updatePosition()
}

function onGlobalPointerDown(e) {
  if (!open.value) return
  const el = e.target
  if (rootRef.value?.contains(el) || menuRef.value?.contains(el)) return
  open.value = false
}

function onKeydown(e) {
  if (e.key === 'Escape' && open.value) {
    e.preventDefault()
    open.value = false
  }
}

watch(open, (isOpen) => {
  if (isOpen) {
    nextTick(() => {
      updatePosition()
      requestAnimationFrame(() => updatePosition())
    })
    document.addEventListener('pointerdown', onGlobalPointerDown, true)
    document.addEventListener('keydown', onKeydown)
  } else {
    document.removeEventListener('pointerdown', onGlobalPointerDown, true)
    document.removeEventListener('keydown', onKeydown)
  }
})

onMounted(() => {
  window.addEventListener('scroll', onScrollOrResize, true)
  window.addEventListener('resize', onScrollOrResize)
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScrollOrResize, true)
  window.removeEventListener('resize', onScrollOrResize)
  document.removeEventListener('pointerdown', onGlobalPointerDown, true)
  document.removeEventListener('keydown', onKeydown)
})
</script>
