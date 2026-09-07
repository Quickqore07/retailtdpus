/**
 * useFormable Composable
 * For Vue 3 Composition API (<script setup>)
 * Provides form functionality with create/edit modes
 */
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRequest } from '@/services/api'
import { useMessage, useLoadingBar } from './useMessage'
import { usePermission } from './usePermission'
import { useAuthStore } from '@/stores/auth'
export function useFormable(resource, redirect,resourceName) {
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
        if(!access.value.includes('create') && mode.value === 'create') {
            router.push('/')
        }
        if(!access.value.includes('update') && mode.value === 'edit') {
            router.push('/')
        }
    }
    // Initialize message and loading bar once at the top
    const message = useMessage()
    const loadingBar = useLoadingBar()

    const isSaving = ref(false)
    const show = ref(false)
    const form = ref({
        organization: {},
    })
    const errors = ref({})

    const mode = computed(() => route.meta?.mode)

    // Helper function to get API URL based on mode
    const getURL = () => {
        let url = route.meta.resource
        let query = ''
        if(route.meta?.resource?.includes('?')){
            const arr = route.meta.resource.split('?')
            url = arr[0]
            query = arr[1]
        }
        const urls = {
            create: `${url}/create`,
            edit: `${url}/${route.params.id}/edit`,
        }

        return (urls[route.meta.mode] || urls["create"]) + `?${query}`
    }

    const setData = (res) => {
        form.value = res.data.form
        loadingBar.finish()
        show.value = true
    }

    const cancel = () => {
        const r = route.meta?.resource || resource
        const id = route.params?.id
        const redirectPath = redirect || r
        let url = `/${redirectPath}`
        
        if (mode.value === "edit") {
            url = `/${redirectPath}/${id}`
        }

        router.push(url)
    }

    const getForm = () => {
        const r = route.meta?.resource || resource
        const id = route.params?.id
        let url = `${r}`
        let query = ''
        let method = "post"

        if(r.includes('?')){
            const arr = r.split('?')
            url = `${arr[0]}`
            query = arr[1]
        }
        if (mode.value === "edit") {
            url = `${url}/${id}`
            method = "put"
        }
        return { url: `${url}?${query}`, method }
    }

    const catchError = (error) => {
        
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors
        }
        message.error(error.response.data.message)
    }

    const save = async (data) => {
        isSaving.value = true
        errors.value = {}

        const { url, method } = getForm()
        try {
            const payload = data instanceof Event ? form.value : data ?? form.value;
            const res = await useRequest(method, url, payload)
            const redirectPath = redirect || route.meta?.resource || resource

            if (res.after_save_redirect) {
                router.push(res.after_save_redirect)
            } else {
                router.push(`/${redirectPath}/${res.id}`)
            }
            
            // Show success toast
            message.success("Saved successfully")
            return res
        } catch (error) {
            catchError(error)
            return null
        } finally {
            isSaving.value = false
        }
    }

    // Load form data on mount
    onMounted(async () => {
        if (!route.meta?.resource && !resource) {
            loadingBar.finish()
            return
        }

        try {
            const url = `/${getURL()}`
            const res = await useRequest('get', url, undefined, { params: route.query })
            setData({ data: { form: res?.data?.form || res?.form } })
        } catch (error) {
            if (error.response?.data?.message) {
                message.error(error.response.data.message)
            } else {
                message.error('Something went wrong.')
            }
            loadingBar.finish()
            router.push(`/${redirect}`)
        }
    })

    return {
        isSaving,
        show,
        form,
        errors,
        mode,
        setData,
        cancel,
        save,
        getForm,
        access
    }
}

