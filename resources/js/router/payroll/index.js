import employeeHours from './employee-hours';
import employeeHoursRequests from './employee-hours-request';
import employeeInactive from './employee-inactive';
import employeeNewRateRequest from './employee-new-rate-request';
import mwa from './mwa';
export default [
    ...employeeHours,
    ...employeeHoursRequests,
    ...employeeInactive,
    ...employeeNewRateRequest,
    ...mwa
]