/**
 * useIndexable Composable
 * For Vue 3 Composition API (<script setup>)
 * Provides index/list page functionality with route guards
 */
import { ref, watch, onMounted } from 'vue'
import { onBeforeRouteUpdate } from 'vue-router'
import { getCurrentInstance } from 'vue'
import { useRequest } from '@/services/api'
import { useMessage, useLoadingBar } from './useMessage'
import { usePermission } from './usePermission'
import { useAuthStore } from '@/stores/auth'

export function useIndexable(resource,resourceName) {
    const collection = ref([])
    const filterableRef = ref(null)
    const access = ref([])
    const { getAllowedActions } = usePermission()
    const authStore = useAuthStore()

    watch(() => authStore.user?.role?.name, (newVal) => {
        access.value = getAllowedActions(resourceName)
    })
    onMounted(() => {
        access.value = getAllowedActions(resourceName)
    })
    // Initialize message and loading bar
    const message = useMessage()
    const loadingBar = useLoadingBar()

    const setData = (res) => {
        if (res.data?.collection && filterableRef.value) {
            filterableRef.value.setData(res)
        }
        if (filterableRef.value) {
            filterableRef.value.setAllData(res.data)
        }

        loadingBar.finish()
    }

    const removeDB = async (resourceName, id) => {
        const r = confirm("Are you sure you want to delete this item?")
        if (r != true) {
            return false;
        }

        loadingBar.start()
        let success = false;
        
        try {
            let url = `${resourceName}`
            let query = ''

            if(resourceName.includes('?')){
                const arr = resourceName.split('?')
                url = `${arr[0]}`
                query = arr[1]
            }
            const res = await useRequest('delete', `${url}/${id}?${query}`)
            if (res.deleted) {
                message.success(res.message || "Deleted successfully")
                // const refreshRes = await useRequest('get', `${resourceName}`)
                // setData({ data: refreshRes })
                // if (filterableRef.value) {
                //     filterableRef.value.fetch()
                // }
                success = true;
            } else {
                message.error(res.message || "Failed to delete")
            }
        } catch (error) {
            console.error('Error deleting item:', error)
            if (error.response?.status === 422) {
                message.error(error.response.data.message || "Validation error occurred")
            } else {
                message.error(error.response?.data?.message || "An error occurred while deleting")
                success = false;
            }
        } finally {
            loadingBar.finish()
            return success;
        }
    }

    const removeMultipleDB = async (resourceName, ids) => {
        if (!ids || ids.length === 0) {
            message.error("No items selected")
            return false;
        }

        const r = confirm(`Are you sure you want to delete ${ids.length} item(s)?`)
        if (r != true) {
            return false;
        }

        loadingBar.start()
        let success = false;
        
        try {
            const res = await useRequest('post', `${resourceName}/delete-multiple`, { ids })
            if (res.deleted) {
                message.success(res.message || "Deleted successfully")
                const refreshRes = await useRequest('get', `${resourceName}`)
                setData({ data: refreshRes })
                if (filterableRef.value) {
                    filterableRef.value.fetch()
                }
                success = true;
            } else {
                message.error(res.message || "Failed to delete")
            }
        } catch (error) {
            console.error('Error deleting items:', error)
            if (error.response?.status === 422) {
                message.error(error.response.data.message || "Validation error occurred")
            } else {
                message.error(error.response?.data?.message || "An error occurred while deleting")
                success = false;
            }
        } finally {
            loadingBar.finish()
            return success;
        }
    }

    
    onBeforeRouteUpdate(async (to, from, next) => {
        console.log(to.meta?.resource)
        if (to.meta?.resource || resource) {
            try {
                const resourceName = to.meta?.resource || resource
                const res = await useRequest('get', `${resourceName}`)
                setData({ data: res })
                next()
            } catch (error) {
                next()
            }
        } else {
            next()
        }
    })

    return {
        collection,
        filterableRef,
        setData,
        removeDB,
        removeMultipleDB,
        access
    }
}

