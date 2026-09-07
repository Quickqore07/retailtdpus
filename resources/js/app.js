/**
 * Main JavaScript Entry Point
 * CSS is imported here so it's available to both Blade and Vue components
 */

// Import global CSS - this makes styles available everywhere
import '../css/app.css'

// Import Vue
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

// Import plugins
import axiosPlugin from './plugins/axios'
import messagePlugin from './plugins/message'
import { LoadingBarPlugin } from './components/loading-bar'

// Import global components
import SvgIcon from './components/SvgIcon.vue'
// Create Pinia instance
const pinia = createPinia()

// Create Vue app
const app = createApp(App)

// Register plugins
app.use(pinia)
app.use(router)
app.use(axiosPlugin)
app.use(messagePlugin)
app.use(LoadingBarPlugin)

// Register global components
app.component('SvgIcon', SvgIcon)

// Mount the app
app.mount('#app')
