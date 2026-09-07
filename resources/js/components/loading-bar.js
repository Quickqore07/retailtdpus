/**
 * Loading Bar Service
 * Shows a progress bar at the top of the page during navigation/loading
 */

class LoadingBarService {
    constructor() {
        this.bar = null
        this.progress = 0
        this.isLoading = false
    }

    init() {
        if (this.bar) return

        // Create loading bar element
        this.bar = document.createElement('div')
        this.bar.className = 'loading-bar'
        this.bar.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #06b6d4);
            transition: width 0.3s ease, opacity 0.3s ease;
            z-index: 99999;
            opacity: 0;
        `
        document.body.appendChild(this.bar)
    }

    start() {
        this.init()
        this.isLoading = true
        this.progress = 0
        this.bar.style.opacity = '1'
        this.bar.style.width = '0%'

        // Simulate progress
        this.simulateProgress()
    }

    simulateProgress() {
        if (!this.isLoading) return

        const increment = Math.random() * 10
        this.progress = Math.min(this.progress + increment, 90)
        this.bar.style.width = `${this.progress}%`

        if (this.progress < 90) {
            setTimeout(() => this.simulateProgress(), 200)
        }
    }

    finish() {
        this.init()
        this.isLoading = false
        this.progress = 100
        this.bar.style.width = '100%'

        // Hide after completion
        setTimeout(() => {
            this.bar.style.opacity = '0'
            setTimeout(() => {
                this.bar.style.width = '0%'
            }, 300)
        }, 200)
    }

    fail() {
        this.init()
        this.isLoading = false
        this.bar.style.background = 'linear-gradient(90deg, #ef4444, #dc2626)'
        this.bar.style.width = '100%'

        // Hide after showing error
        setTimeout(() => {
            this.bar.style.opacity = '0'
            setTimeout(() => {
                this.bar.style.width = '0%'
                this.bar.style.background = 'linear-gradient(90deg, #3b82f6, #06b6d4)'
            }, 300)
        }, 500)
    }

    set(percent) {
        this.init()
        this.progress = Math.min(Math.max(percent, 0), 100)
        this.bar.style.width = `${this.progress}%`
        this.bar.style.opacity = '1'

        if (percent >= 100) {
            this.finish()
        }
    }
}

// Create singleton instance
const loadingBar = new LoadingBarService()

// Vue plugin
export const LoadingBarPlugin = {
    install(app) {
        // Add to global properties
        app.config.globalProperties.$bar = loadingBar

        // Also provide for composition API
        app.provide('loadingBar', loadingBar)
    }
}

// Default export for static usage
export default loadingBar

