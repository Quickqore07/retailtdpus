<template>
    <div class="form-panel">
        <h3 class="form-title">Handbook Acknowledgment</h3>
        
        <div class="form-content">
            <h6 class="text-center mb-4">
                Acknowledgement of Receipt of the Company's Employee Manual
            </h6>
            
            <div class="text-content">
                <p>I acknowledge that I have received the Company's Employee Handbook ("the Handbook") and understand that violations of the policies contained in the Handbook, including the anti-harassment policy, could result in disciplinary action, up to and including termination.</p>
                
                <p>I further understand that the information contained in the Handbook represents guidelines for the Company and that the Company reserves the right to modify the Handbook or amend or terminate any policy, procedure, or employee benefit program at any time.</p>
                
                <p>I further understand that the contents of the Handbook do not form a written employment contract. Either the Company or I have the right to terminate my employment at any time. I further understand that no manager, supervisor or representative of the Company, other than an officer, has any authority to enter into any agreement guaranteeing employment for any specific period of time or on any specific terms; and then only if said agreement is in writing, approved by the Board of Directors and signed by an officer.</p>
                
                <p>I further understand that if I have any questions about the interpretation or application of any policies contained in the Handbook, I should direct these questions to the on-site supervisor.</p>
            </div>

            <div class="form-fields">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field-group">
                        <label class="field-label">Employee's Signature</label>
                        <div class="field-value">{{ data?.employee_sign || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Date</label>
                        <div class="field-value">{{ formatDate(data?.date) || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Print Full Name</label>
                        <div class="field-value">{{ data?.print_full_name || 'N/A' }}</div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Location Name</label>
                        <div class="field-value">{{ data?.company_code || 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <h6 class="font-semibold mb-2"><i>Agreement to Arbitrate</i></h6>
                <h6 class="font-semibold">Arbitration</h6>
                
                <div class="text-content text-sm">
                    <p>(a) In consideration of the employee's employment by (the "Company"), the employee agrees that all claims and disputes they may have arising from or relating to their employment by the Company or their separation from the Company shall be decided by arbitration in accordance with the National Rules for the Resolution of Employment Disputes of the American Arbitration Association then in effect, unless the employee and the Company, in writing, mutually agree otherwise.</p>
                    
                    <p>(b) The employee agrees that, to the extent permitted by law, this Agreement covers all claims and disputes they may have with the Company, including but not limited to all claims arising in tort or in contract, and all claims arising from or relating to federal, state, or local statutes or ordinances including, but not limited to, disputes governed by the Employment Retirement Income Security Act, Title VII of the Civil Rights Act, the Age Discrimination in Employment Act, the Americans with Disabilities Act, and the Family and Medical Leave Act.</p>
                    
                    <p>(c) The Employee agrees that any claim or dispute they have with the Company shall be conducted only on an individual basis and not in a class, collective or representative action or as a named or unnamed member in a class, collective, consolidated or private attorney general action, unless both the Company and the Employee specifically agree to do so in a writing signed by the Company and the employee.</p>
                    
                    <p>(d) To the extent that arbitration of any specific claim is prohibited by law, all other claims shall nonetheless be submitted to arbitration.</p>
                    
                    <p>(e) Notice of the demand for arbitration shall be served in writing and shall be filed with the American Arbitration Association. Notice shall be served and filed within six months of the time the claim or dispute arose, except in the case of statutory rights described in subparagraph (b) of this Agreement.</p>
                    
                    <p>(f) Unless the Employee and the Company, in writing, mutually agree otherwise, the cost of the arbitration hearing shall be shared equally by us to the extent permitted by law. Each side shall be responsible for its own attorney's fees, costs and disbursements to the extent permitted by law.</p>
                    
                    <p>(g) The award rendered by the arbitration or arbitrators shall be final, and judgment may be entered upon it in accordance with Pennsylvania law in a court having jurisdiction thereof.</p>
                    
                    <p>(h) The Employee agrees that this Arbitration Agreement does not change their status as an at-will employee, and that their employment may be terminated by themselves or by the Company at any time, for any reason, with or without notice.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { formatDate } from '@/utils/date'

defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
    readonly: {
        type: Boolean,
        default: true
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

.text-content p {
    margin-bottom: 1rem;
    line-height: 1.6;
    color: #374151;
}

.form-fields {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f9fafb;
    border-radius: 0.5rem;
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
</style>
