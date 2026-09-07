import { ref } from 'vue'
import axios from 'axios'

export const CHARGEBACK_NOTIFICATION_TYPES = [
    'chargeback_submitted',
    'sales_receipt_uploaded',
]

const unreadCount = ref(0)
const loading = ref(false)

export function useChargebackNotificationCount() {
    const fetchUnreadCount = async () => {
        loading.value = true
        try {
            const res = await axios.get('/api/notifications/unread-count', {
                params: {
                    types: CHARGEBACK_NOTIFICATION_TYPES.join(','),
                },
            })
            unreadCount.value = res.data?.count ?? 0
        } catch (e) {
            console.error('Failed to load chargeback notification count', e)
        } finally {
            loading.value = false
        }
    }

    return {
        unreadCount,
        loading,
        fetchUnreadCount,
    }
}
