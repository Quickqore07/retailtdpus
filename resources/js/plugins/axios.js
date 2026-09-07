/**
 * Axios Vue Plugin
 * Makes axios available globally via inject/provide
 */

import api from '@/services/api'

// Symbol for injection key
export const axiosKey = Symbol('axios')

// Plugin definition
export default {
    install(app) {
        // Make axios available via this.$api in Options API
        app.config.globalProperties.$api = api

        // Make axios available via inject('axios') in Composition API
        app.provide(axiosKey, api)
    }
}

