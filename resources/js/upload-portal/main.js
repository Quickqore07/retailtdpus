import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import axios from './plugins/axios'
import messagePlugin from '../plugins/message'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

app.use(messagePlugin)
app.config.globalProperties.$axios = axios

app.mount('#upload-app')
