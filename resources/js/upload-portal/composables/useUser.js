import { ref, computed } from 'vue'
import axios from '../plugins/axios'

const user = ref(null)
const folderData = ref(null)
const invoiceFolder = ref(null)
const userLoading = ref(true)
export function useUser() {
  const loadUser = async () => {
    userLoading.value = true
    try {
      const response = await axios.get('/upload-portal/api/me')
      if (response.data.success) {
        user.value = response.data.user
        invoiceFolder.value = response.data.invoice_folder
      }
    } catch (error) {
      console.error('Error loading user:', error)
    } finally {
      userLoading.value = false
    }
  }

  /**
   * Get all user permissions from their role
   */
  const spPermissions = computed(() => {
    return user.value?.sp_permission || []
  })

  const rolePermissions = computed(() => {
    return user.value?.role?.permissions || []
  })

  /**
   * Check if user has permission for upload-portal actions
   * @param {string} action - The action name (e.g., 'add', 'edit', 'delete', 'view')
   * @returns {boolean}
   */
  const can = (resource, action) => {
    // Admin always has access
    if (user.value?.role?.name?.toLowerCase() === 'admin' || user.value?.role?.name?.toLowerCase() === 'superadmin') {
      return true
    }

    // Find the permission for upload-portal resource
    const permission = spPermissions.value.find(p => p.name === resource)
    const rolePermission = rolePermissions.value.find(p => p.name === resource)
    if (!permission && !rolePermission) {
      return false
    }

    // Check if the action is allowed (value should be 1)
    return permission?.actions?.[action] === 1 || rolePermission?.actions?.[action] === 1
  }
  return {
    user,
    userLoading,
    spPermissions,
    rolePermissions,
    loadUser,
    can,
    invoiceFolder,
  }
}
