/**
 * useShowable Composable
 * For Vue 3 Composition API (<script setup>)
 * Provides show/detail page functionality
 */
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRequest } from '@/services/api'
import { useMessage, useLoadingBar } from './useMessage'
import { usePermission } from './usePermission'
import { useAuthStore } from '@/stores/auth'
export function useShowable(resource,resourceName) {
    const route = useRoute()
    const router = useRouter()
    const access = ref([])
    const { getAllowedActions } = usePermission()
    const authStore = useAuthStore()

    watch(() => authStore.user?.role?.name, (newVal) => {
        canAccess()
    })
    onMounted(() => {
        canAccess()
    })
    const canAccess = () => {
        access.value = getAllowedActions(resourceName)
            if(!access.value.includes('show')) {
                // router.go(-1)
            }
        // setTimeout(() => {
        // }, 2000)
    }


    // Initialize message and loading bar
    const message = useMessage()
    const loadingBar = useLoadingBar()
    
    const model = ref(null)
    const show = ref(false)
    const payload = ref({})

    const setData = (res) => {
        const data = res.data || res || {}
        model.value = data?.model || data
        payload.value = data
        loadingBar.finish()
        show.value = true
    }

    const fetch = async () => {
        const resourceName = route.meta?.resource || resource
        const id = route.params?.id

        let url = `${resourceName}`
        let query = ''

        if(resourceName.includes('?')){
            const arr = resourceName.split('?')
            url = `${arr[0]}`
            query = arr[1]
        }

        if (resourceName && id) {
            try {
                const res = await useRequest('get', `${url}/${id}?${query}`)
                setData({ data: res?.data || res })
            } catch (error) {
                router.push('/')
                console.error('Error fetching data:', error)
                message.error('Failed to load data')
            }
        }
    }

    const removeDB = async (resourceName, id,path = null) => {
        const r = confirm("Are you sure you want to delete this item?")
        if (r != true) {
            return
        }
        let success = false;
        loadingBar.start()


        let url = `${resourceName}`
        let query = ''

        if(resourceName.includes('?')){
            const arr = resourceName.split('?')
            url = `${arr[0]}`
            query = arr[1]
        }
        try {
            const res = await useRequest('delete', `${url}/${id}?${query}`)
            if (res.deleted) {
                router.push(`/${path || resourceName}`)
                message.success(res.message || "Deleted successfully")
                success = true;
            } else {
                message.error(res.message || "Failed to delete")
            }
        } catch (error) {
            if (error.response?.status === 422) {
                message.error(error.response.data.message || "Validation error occurred")
            } else {
                message.error(error.response?.data?.message || "An error occurred while deleting")
            }
        } finally {
            loadingBar.finish()
            return success;
        }
    }

   

    onMounted(async () => {
        const resourceName = route.meta?.resource || resource
        const id = route.params?.id
        if (resourceName && id) {
            try {
                let newUrl = resourceName;
                let query = ''
                if(resourceName.includes('?')){
                    const arr = resourceName.split('?')
                    newUrl = arr[0]
                    query = arr[1]
                }
                const res = await useRequest('get', `${newUrl}/${id}?${query}`)

                setData({ data: res?.data || res })
            } catch (error) {
                console.error('Error fetching data:', error)
                message.error('Failed to load data')
                loadingBar.finish()
                // router.go(-1)
            }
        }
    })

    return {
        model,
        show,
        setData,
        fetch,
        removeDB,
        access,
        payload
    }
}

