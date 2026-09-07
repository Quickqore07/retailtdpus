/**
 * Message/Toast Plugin for Vue 3
 * Provides global $message for showing notifications
 */

import { createApp, h } from 'vue'
import Toast from '../components/ui/toast.vue'

class MessageService {
    constructor() {
        this.toastRef = null
        this.toastApp = null
        this.container = null
    }

    init() {
        if (this.toastRef) return

        // Create container
        this.container = document.createElement('div')
        this.container.id = 'toast-container'
        document.body.appendChild(this.container)

        // Create a wrapper to hold the ref
        const wrapper = {
            setup: () => {
                return {}
            },
            render: () => {
                return h(Toast, {
                    ref: (el) => {
                        this.toastRef = el
                    }
                })
            }
        }

        this.toastApp = createApp(wrapper)
        this.toastApp.mount(this.container)
    }

    show(options) {
        this.init()
        if (this.toastRef && typeof this.toastRef.addToast === 'function') {
            return this.toastRef.addToast(options)
        }
        console.error('Toast component not properly initialized')
        return null
    }

    success(message, title = null, duration = 5000) {
        return this.show({
            type: 'success',
            message,
            title,
            duration
        })
    }

    error(message, title = null, duration = 6000) {
        return this.show({
            type: 'error',
            message,
            title,
            duration
        })
    }

    warning(message, title = null, duration = 5000) {
        return this.show({
            type: 'warning',
            message,
            title,
            duration
        })
    }

    info(message, title = null, duration = 5000) {
        return this.show({
            type: 'info',
            message,
            title,
            duration
        })
    }

    remove(id) {
        if (this.toastRef && typeof this.toastRef.removeToast === 'function') {
            this.toastRef.removeToast(id)
        }
    }
}

// Create singleton instance
const messageService = new MessageService()

// Vue plugin
export default {
    install(app) {
        // Add to global properties
        app.config.globalProperties.$message = messageService

        // Also provide for composition API
        app.provide('message', messageService)
    }
}

// Export for direct import if needed
export { messageService }

// Default export for static usage (like in route guards)
export const Message = messageService

