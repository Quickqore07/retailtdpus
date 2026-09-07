/**
 * Permission Composable
 * Provides permission checking functionality throughout the app
 */

import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useSPPermission() {   
    const authStore = useAuthStore()

    /**
     * Get all user permissions from their role
     */
    const spPermissions = computed(() => {
        return authStore.user?.sp_permission || []
    })

    /**
     * Check if user has permission for a specific resource and action
     * @param {string} resource - The resource name (e.g., 'user', 'role')
     * @param {string} action - The action name (e.g., 'index', 'create', 'update', 'delete', 'show')
     * @returns {boolean}
     */
    const can = (resource, action) => {
        // Admin always has access
        if (authStore.user?.role?.name?.toLowerCase() === 'admin') {
            return true
        }

        // Find the permission for this resource
        const permission = spPermissions.value.find(p => p.name === resource)
        
        if (!permission) {
            return false
        }

        // Check if the action is allowed (value should be 1)
        return permission.actions?.[action] === 1
    }

    /**
     * Check if user has any permission for a resource
     * Useful for showing/hiding entire sections
     * @param {string} resource - The resource name
     * @returns {boolean}
     */
    const canAccess = (resource) => {
        // Admin always has access
        if (authStore.user?.role?.name?.toLowerCase() === 'admin') {
            return true
        }

        const permission = spPermissions.value.find(p => p.name === resource)
        
        if (!permission) {
            return false
        }

        // Check if any action is allowed
        return permission.actions?.index === 1
    }

    /**
     * Check if user can perform multiple actions on a resource
     * @param {string} resource - The resource name
     * @param {string[]} actions - Array of action names
     * @returns {boolean}
     */
    const canAll = (resource, actions) => {
        return actions.every(action => can(resource, action))
    }

    /**
     * Check if user can perform any of the actions on a resource
     * @param {string} resource - The resource name
     * @param {string[]} actions - Array of action names
     * @returns {boolean}
     */
    const canAny = (resource, actions) => {
        return actions.some(action => can(resource, action))
    }

    /**
     * Get allowed actions for a resource
     * @param {string} resource - The resource name
     * @returns {string[]} Array of allowed action names
     */
    const getAllowedActions = (resource) => {
    
        const permission = spPermissions.value.find(p => p.name === resource)
        if (!permission) {
            return []
        }
        if(authStore.user?.role?.name?.toLowerCase() === 'admin') {
            return Object.entries(permission.actions || {})
                .map(([action]) => action)
        }
        return Object.entries(permission.actions || {})
            .filter(([, value]) => value === 1)
            .map(([action]) => action)
    }

    return {
        spPermissions,
        can,
        canAccess,
        canAll,
        canAny,
        getAllowedActions
    }
}

/**
 * Permission directive for v-sp-permission
 * Usage: v-sp-permission="'user.create'" or v-sp-permission="['user', 'create']"
 */
export const vSPPermission = {
    mounted(el, binding) {
        const { can } = useSPPermission()
        const value = binding.value

        let hasPermission = false

        if (typeof value === 'string') {
            // Format: "resource.action" (e.g., "user.create")
            const [resource, action] = value.split('.')
            hasPermission = can(resource, action)
        } else if (Array.isArray(value)) {
            // Format: ["resource", "action"]
            const [resource, action] = value
            hasPermission = can(resource, action)
        }

        if (!hasPermission) {
            // Remove element if no permission
            el.style.display = 'none'
            // Or completely remove from DOM
            // el.parentNode?.removeChild(el)
        }
    }
}

