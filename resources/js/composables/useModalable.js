/**
 * useModalable Composable
 * For Vue 3 Composition API (<script setup>)
 * Provides modal functionality with data fetching
 */
import { ref, onMounted } from 'vue'
import { useRequest } from '@/services/api'

export function useModalable(resource, setDataCallback) {
    const collection = ref([])
    const query = ref({
        page: 1,
    })
    const show = ref(false)
    const loading = ref(false)

    const fetch = async () => {
        loading.value = true
        try {
            const res = await useRequest('get', `/api/${resource}`, undefined, { params: query.value })
            if (setDataCallback && typeof setDataCallback === 'function') {
                setDataCallback({ data: res })
            } else if (res.collection) {
                collection.value = res.collection
            }
        } catch (error) {
            console.error('Error fetching data:', error)
        } finally {
            loading.value = false
        }
    }

    onMounted(() => {
        show.value = false
        fetch()
    })

    return {
        collection,
        query,
        show,
        loading,
        fetch
    }
}

