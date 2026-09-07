import users from './user';
import roles from './role';
import states from './state';
import counties from './county';
import regions from './region';
import areas from './area';
import workgroups from './workgroup';
import offices from './office';
import companyGroups from './company-group';
import companies from './company';
import employeeRoles from './employee-role';
import minimumWages from './minimum-wage';
import ledgers from './ledger';
import bankRules from './bank-rule';
import bankCategoryRules from './bank-category-rule';
import fundRequirements from './fund-requirement';
import appSettings from './app-settings';
import pandl from './pandl';
import activityLogs from './activity-log';
import pjCalendars from './pj-calendar';
export default [
    ...appSettings,
    ...users,
    ...roles,
    ...states,
    ...counties,
    ...regions,
    ...areas,
    ...workgroups,
    ...offices,
    ...companyGroups,
    ...companies,
    ...employeeRoles,
    ...minimumWages,
    ...ledgers,
    ...bankRules,
    ...bankCategoryRules,
    ...fundRequirements,
    ...pandl,
    ...pjCalendars,
    ...activityLogs
]