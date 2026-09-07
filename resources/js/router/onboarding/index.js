import employeeOnboarding from './employee-onboarding';
import pendingApplications from './pending-applications';
import i9Review from './i9-review';
import employeeRateRequest from './employee-rate-request';
import benefits from './benefits';
import employeeConfirmation from './employee-confirmation';
export default [
    ...pendingApplications,
    ...i9Review,
    ...employeeOnboarding,
    ...employeeRateRequest,
    ...benefits,
    ...employeeConfirmation
]