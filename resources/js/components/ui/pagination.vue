<template>
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <select
                v-if="showLimit"
                :value="limit"
                @change="onLimitChange"
                :disabled="loading"
                class="rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-2 py-1 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <option v-for="opt in limitOptions" :key="opt" :value="opt">{{ opt }}</option>
            </select>

            <span class="text-sm text-gray-600 dark:text-gray-400 text-center sm:text-left">
                Showing {{ collection.from || 0 }}-{{ collection.to || 0 }} of {{ collection.total || 0 }} entries
            </span>
        </div>

        <div class="flex items-center justify-center sm:justify-end gap-2 flex-wrap">
            <Button @click="prevPage" :disabled="!collection.has_prev || loading" variant="outline" size="xs">
                Prev
            </Button>
            <span class="px-2 text-sm text-gray-700 dark:text-gray-300">
                {{ collection.current_page || 1 }} / {{ collection.last_page || 1 }}
            </span>
            <Button @click="nextPage" :disabled="!collection.has_next || loading" variant="outline" size="xs">
                Next
            </Button>

            <div v-if="showJump" class="flex items-center gap-2 ml-2">
                <span class="text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">Go to:</span>
                <input
                    v-model.number="jumpToPageInput"
                    @keyup.enter="jumpToPage"
                    type="number"
                    min="1"
                    :max="collection.last_page || 1"
                    :disabled="loading"
                    placeholder="Page"
                    class="w-16 px-2 py-1 text-xs text-center rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                />
                <Button @click="jumpToPage" :disabled="loading" variant="outline" size="xs">
                    Go
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import Button from '@/components/ui/button.vue'
import { useMessage } from '@/composables/useMessage'

const props = defineProps({
    collection: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
            per_page: 25,
            prev_page_url: null,
            next_page_url: null,
        }),
    },
    loading: {
        type: Boolean,
        default: false,
    },
    showJump: {
        type: Boolean,
        default: true,
    },
    showLimit: {
        type: Boolean,
        default: true,
    },
    limit: {
        type: Number,
        default: 25,
    },
    limitOptions: {
        type: Array,
        default: () => [10, 15, 25, 50, 100],
    },
})

const emit = defineEmits(['page-change', 'limit-change'])
const message = useMessage()
const jumpToPageInput = ref(null)

function nextPage() {
    if (!props.collection?.has_next) return
    emit('page-change', (props.collection.current_page || 1) + 1, props.collection.per_page || 25)
}

function prevPage() {
    if (!props.collection?.has_prev) return
    emit('page-change', Math.max((props.collection.current_page || 1) - 1, 1), props.collection.per_page || 25)
}

function jumpToPage() {
    const targetPage = Number(jumpToPageInput.value)
    const lastPage = props.collection?.last_page || 1

    if (!targetPage || targetPage < 1) {
        message.error('Please enter a valid page number')
        return
    }

    if (targetPage > lastPage) {
        message.error(`Page number cannot exceed ${lastPage}`)
        return
    }

    emit('page-change', targetPage, props.collection.per_page || 25)
    jumpToPageInput.value = null
}

function onLimitChange(event) {
    emit('page-change', props.collection.current_page || 1, Number(event.target.value))
}
</script>
