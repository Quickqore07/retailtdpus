/**
 * Standalone public attendance kiosk (no login).
 */
import '../css/app.css'
import { createApp } from 'vue'
import AttendanceApp from './views/attendance/index.vue'

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/attendance-sw.js', { scope: '/attendance/' }).catch(() => {
      // Service worker registration is optional; the page still works without it.
    })
  })
}

createApp(AttendanceApp).mount('#app')
