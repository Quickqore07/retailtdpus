/**
 * useMessage Composable
 * Provides access to the message service in Composition API components
 */

import { inject, getCurrentInstance } from 'vue'

export function useMessage() {
    // Try to get from provide/inject first
    const injected = inject('message', null)
    if (injected) return injected

    // Fallback to global properties (for Options API compatibility)
    const instance = getCurrentInstance()
    if (instance?.appContext.config.globalProperties.$message) {
        return instance.appContext.config.globalProperties.$message
    }

    // If all else fails, throw error
    throw new Error('useMessage must be used within an app with message plugin installed')
}

export function useLoadingBar() {
    // Try to get from provide/inject first
    const injected = inject('loadingBar', null)
    if (injected) return injected

    // Fallback to global properties
    const instance = getCurrentInstance()
    if (instance?.appContext.config.globalProperties.$bar) {
        return instance.appContext.config.globalProperties.$bar
    }

    // If all else fails, throw error
    throw new Error('useLoadingBar must be used within an app with loading bar plugin installed')
}

