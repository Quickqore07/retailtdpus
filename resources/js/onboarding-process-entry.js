/**
 * Standalone entry for the onboarding process page.
 * Mounts only OnboardingProcess so it renders when this Blade view is loaded.
 */
import '../css/app.css'
import { createApp } from 'vue'
import OnboardingProcess from './views/onboarding-process/index.vue'

const app = createApp(OnboardingProcess)
app.mount('#app')
