<template>
    <div class="thank-you-container">
        <div class="success-icon-wrapper">
            <svg class="success-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        
        <h1 class="thank-you-title">Thank You!</h1>
        
        <p class="thank-you-message">
            You have completed your onboarding form successfully. Our team will reach out to you shortly.
        </p>
        
        <div class="info-box">
            <div class="info-icon">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="info-content">
                <h3 class="info-title">What's Next?</h3>
                <ul class="info-list">
                    <li>You will receive a email for i9 and W4 form from WorkBright</li>
                </ul>
            </div>
        </div>
        
        <div class="contact-info">
            <p class="contact-text">
                If you have any questions, please don't hesitate to contact our HR department.
            </p>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'

const props = defineProps({
    onboardingId: {
        type: String,
        required: true,
    },
})

onMounted(() => {
    // Optional: You can add analytics or tracking here
    console.log('Onboarding completed for ID:', props.onboardingId)
})
</script>

<style scoped>
.thank-you-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem;
    text-align: center;
}

.success-icon-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.success-icon {
    width: 80px;
    height: 80px;
    color: #10b981;
    animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.thank-you-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 1rem;
    animation: fadeInUp 0.6s ease-out 0.2s both;
}

.thank-you-message {
    font-size: 1.125rem;
    color: #4b5563;
    line-height: 1.6;
    margin-bottom: 2rem;
    animation: fadeInUp 0.6s ease-out 0.4s both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.info-box {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    text-align: left;
    animation: fadeInUp 0.6s ease-out 0.6s both;
}

.info-icon {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    color: #16a34a;
    margin-top: 0.25rem;
}

.info-content {
    flex: 1;
}

.info-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #166534;
    margin-bottom: 0.75rem;
}

.info-list {
    list-style: disc;
    margin-left: 1.25rem;
    color: #15803d;
}

.info-list li {
    margin-bottom: 0.5rem;
    line-height: 1.5;
}

.info-list li:last-child {
    margin-bottom: 0;
}

.contact-info {
    animation: fadeInUp 0.6s ease-out 0.8s both;
}

.contact-text {
    font-size: 0.875rem;
    color: #6b7280;
    font-style: italic;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .thank-you-title {
        color: #f9fafb;
    }
    
    .thank-you-message {
        color: #d1d5db;
    }
    
    .info-box {
        background: #064e3b;
        border-color: #065f46;
    }
    
    .info-title {
        color: #86efac;
    }
    
    .info-list {
        color: #6ee7b7;
    }
    
    .contact-text {
        color: #9ca3af;
    }
}

/* Responsive design */
@media (max-width: 640px) {
    .thank-you-container {
        padding: 1.5rem;
    }
    
    .thank-you-title {
        font-size: 2rem;
    }
    
    .success-icon {
        width: 60px;
        height: 60px;
    }
    
    .thank-you-message {
        font-size: 1rem;
    }
}
</style>
