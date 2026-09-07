import { ref } from 'vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'

const downloadBlob = (blob, filename) => {
    const url = window.URL.createObjectURL(new Blob([blob]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
}

export function useChargeBackExcelExport() {
    const message = useMessage()
    const isExporting = ref(false)

    const exportFromBackend = async ({
        url,
        filename,
        params = {},
        filterableRef = null,
        extraParams = {},
    }) => {
        if (isExporting.value) {
            return
        }

        isExporting.value = true

        try {
            const currentParams = filterableRef?.value?.getCurrentParams?.() || {}
            const response = await useRequest('get', url, null, {
                params: {
                    ...currentParams,
                    ...extraParams,
                    ...params,
                },
                responseType: 'blob',
            })

            downloadBlob(response, filename)
            message.success('Exported to Excel successfully')
        } catch (error) {
            message.error(error?.response?.data?.message || 'Failed to export data')
        } finally {
            isExporting.value = false
        }
    }

    return {
        isExporting,
        exportFromBackend,
    }
}
