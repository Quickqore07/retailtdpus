<template>
    <div class="form-panel">
        <h3 class="form-title">Emergency Contact Information</h3>

        <div class="form-content">
            <p class="form-description">
                In case of any emergency, please list anyone who you would like us to contact and with whom you will allow us to share information about your location, situation, and needs.
            </p>

            <div class="employee-info mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field-group">
                        <label class="field-label">Employee Name</label>
                        <div class="field-value">{{ onboardingData?.applicant_first_name || 'N/A' }} {{ onboardingData?.applicant_middle_initial || 'N/A' }} {{ onboardingData?.applicant_last_name || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Division</label>
                        <div class="field-value">{{ onboardingData?.employee?.workgroup?.name || 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact #1 -->
            <div class="contact-section">
                <div class="section-header">Emergency Contact #1</div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field-group">
                        <label class="field-label">Name</label>
                        <div class="field-value">{{ data?.e_emergency_contact_first_name || 'N/A' }} {{ data?.e_emergency_contact_last_name || 'N/A' }}</div>    
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Relationship</label>
                        <div class="field-value">{{ data?.e_emergency_contact_relationship || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Cell Phone</label>
                        <div class="field-value">{{ data?.e_emergency_contact_phone_cell || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Business Phone</label>
                        <div class="field-value">{{ data?.e_emergency_contact_phone_work || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Home Phone</label>
                        <div class="field-value">{{ data?.e_emergency_contact_phone_home || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Email</label>
                        <div class="field-value">{{ data?.e_emergency_contact_email || 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact #2 -->
            <div class="contact-section">
                <div class="section-header">Emergency Contact #2</div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field-group">
                        <label class="field-label">Name</label>
                        <div class="field-value">{{ contact2Data?.name || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Relationship</label>
                        <div class="field-value">{{ contact2Data?.relationship || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Cell Phone</label>
                        <div class="field-value">{{ contact2Data?.cell_phone || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Business Phone</label>
                        <div class="field-value">{{ contact2Data?.business_phone || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Home Phone</label>
                        <div class="field-value">{{ contact2Data?.home_phone || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Email</label>
                        <div class="field-value">{{ contact2Data?.email || 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="signature-section mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field-group">
                        <label class="field-label">Employee Signature</label>
                        <div class="field-value">{{ data?.e_signature || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Date</label>
                        <div class="field-value">{{ formatDate(data?.created_at) || 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { formatDate } from '@/utils/date'

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
    onboardingData: {
        type: Object,
        default: () => ({})
    },
    readonly: {
        type: Boolean,
        default: true
    }
})

const contact2Data = computed(() => {
    if (!props.data) return {}
    
    const firstName = props.data.e_emergency_contact2_first_name || ''
    const lastName = props.data.e_emergency_contact2_last_name || ''
    const name = `${firstName} ${lastName}`.trim() || 'N/A'
    
    return {
        name,
        relationship: props.data.e_emergency_contact2_relationship || 'N/A',
        cell_phone: props.data.e_emergency_contact2_phone_cell || 'N/A',
        business_phone: props.data.e_emergency_contact2_phone_work || 'N/A',
        home_phone: props.data.e_emergency_contact2_phone_home || 'N/A',
        email: props.data.e_emergency_contact2_email || 'N/A',
    }
})
</script>

<style scoped>
.form-panel {
    padding: 1.5rem;
}

.form-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: #111827;
}

.form-description {
    color: #6b7280;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.employee-info {
    background: #f9fafb;
    border-radius: 0.5rem;
    padding: 1rem;
}

.contact-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #eff6ff;
    border-radius: 0.5rem;
}

.section-header {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    padding: 0.5rem 0;
    margin-bottom: 1rem;
    border-bottom: 2px solid #3b82f6;
}

.field-group {
    margin-bottom: 1rem;
}

.field-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.field-value {
    padding: 0.5rem 0.75rem;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    color: #111827;
}

.signature-section {
    background: #f9fafb;
    border-radius: 0.5rem;
    padding: 1.5rem;
}
</style>
