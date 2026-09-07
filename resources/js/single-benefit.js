/**
 * Standalone entry for the single benefit page.
 * Mounts only SingleBenefit so it renders when this Blade view is loaded.
 */
import '../css/app.css'
import { createApp } from 'vue'
import SingleBenefit from './views/onboarding-process/SingleBenefit.vue'

const app = createApp(SingleBenefit)
app.mount('#app')
