/**
 * useSettings Composable
 * For Vue 3 Composition API (<script setup>)
 * Provides settings page functionality
 */
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter, onBeforeRouteEnter, onBeforeRouteUpdate } from 'vue-router'
import { getCurrentInstance } from 'vue'
import { useRequest } from '@/services/api'
import LoadingBar from "@js/components/loading-bar"

export function useSettings(resource, redirect) {
    const route = useRoute()
    const router = useRouter()
    const instance = getCurrentInstance()

    const isSaving = ref(false)
    const show = ref(false)
    const bu_access = ref(false)
    const form = ref({})
    const errors = ref({})
    const filterableRef = ref(null)

    const mode = computed(() => route.meta?.mode)

    // Access store if available
    const getStore = () => {
        try {
            if (instance?.appContext.config.globalProperties.$store) {
                return instance.appContext.config.globalProperties.$store
            }
        } catch (e) {
            // Handle error
        }
        return null
    }

    onMounted(() => {
        const store = getStore()
        if (store?.state?.app?.user_data?.bu_access !== undefined) {
            bu_access.value = store.state.app.user_data.bu_access
        }
    })

    const setData = (res) => {
        form.value = res.data.form
        
        const store = getStore()
        if (store?.state?.app?.user_data?.bu_access !== undefined) {
            bu_access.value = store.state.app.user_data.bu_access
        }
        
        if (res.data.collection && res.data.collection.data.length > 0 && filterableRef.value) {
            filterableRef.value.setData(res)
        }
        
        const { $bar } = instance?.appContext.config.globalProperties || {}
        if ($bar) $bar.finish()

        show.value = true
    }

    const cancel = () => {
        const r = route.meta?.resource || resource
        const url = `/${r}?cancel`
        router.push(url)
    }

    const getForm = () => {
        const r = route.meta?.resource || resource
        const url = `/api/${r}`
        const method = "post"
        return { url, method }
    }

    const catchError = (error) => {
        console.log(error)
        const { $message } = instance?.appContext.config.globalProperties || {}
        
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors
            if ($message) $message.error(error.response.data.message)
        }
    }

    const save = async () => {
        isSaving.value = true
        errors.value = {}

        const { url, method } = getForm()
        const { $message, $t } = instance?.appContext.config.globalProperties || {}

        try {
            await useRequest(method, url, form.value)
            const id = Math.random().toString(36).substring(7)
            const redirectPath = redirect || route.meta?.resource || resource
            router.push(`/${redirectPath}?id=${id}`)
            if ($message) $message.success($t?.("saved_success") || "Saved successfully")
        } catch (error) {
            catchError(error)
        } finally {
            isSaving.value = false
        }
    }

    const removeDB = async (resourceName, id) => {
        const { $t, $bar, $message } = instance?.appContext.config.globalProperties || {}
        
        const r = confirm($t?.("are_you_sure") || "Are you sure?")
        if (r != true) {
            return
        }

        if ($bar) $bar.start()
        
        try {
            const res = await useRequest('delete', `/api/${resourceName}/${id}`)
            if (res.deleted) {
                if ($message) $message.success($t?.("success_delete") || "Deleted successfully")
                const refreshRes = await useRequest('get', `/api/${resourceName}`)
                setData({ data: { form: refreshRes } })
            }
        } catch (error) {
            if (error.response && error.response.status === 422 && $message) {
                $message.error(error.response.data.message)
            }
        } finally {
            if ($bar) $bar.finish()
        }
    }

    // Setup route guards
    onBeforeRouteEnter(async (to, from, next) => {
        if (!to.meta?.resource && !resource) {
            LoadingBar.finish()
            return next()
        }
        
        try {
            const resourceName = to.meta?.resource || resource
            const res = await useRequest('get', `/api/${resourceName}`)
            next((vm) => {
                if (vm.setData) {
                    vm.setData({ data: { form: res } })
                }
            })
        } catch (error) {
            next((vm) => {
                if (vm.cancel) {
                    vm.cancel()
                }
            })
        }
    })

    onBeforeRouteUpdate(async (to, from, next) => {
        if (!to.meta?.resource && !resource) {
            const { $bar } = instance?.appContext.config.globalProperties || {}
            if ($bar) $bar.finish()
            return next()
        }
        
        try {
            const resourceName = to.meta?.resource || resource
            const res = await useRequest('get', `/api/${resourceName}`)
            setData({ data: { form: res } })
            next()
        } catch (error) {
            next()
        }
    })

    return {
        isSaving,
        show,
        bu_access,
        form,
        errors,
        filterableRef,
        mode,
        setData,
        cancel,
        save,
        getForm,
        removeDB
    }
}

