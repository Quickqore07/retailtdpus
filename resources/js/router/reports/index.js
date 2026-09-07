import payroll from './payroll'
import hr from './hr'
import idealCost from './ideal-cost'
import sales from './sales'
import cash from './cash'
import foodCost from './food-cost'
import fund from './fund'
import finance from './finance'
import ttm from './ttm'
import ap from './ap'

export default [
    ...payroll,
    ...hr,
    ...idealCost,
    ...sales,
    ...cash,
    ...foodCost,
    ...fund,
    ...finance,
    ...ttm,
    ...ap,
    {
        path: '/reports/dtm',
        name: 'reports.dtm',
        component: () => import('@/views/reports/dtm-report.vue'),
        meta: {
            title: 'DTM Report'
        }
    },
    {
        path: '/reports/attendance',
        name: 'reports.attendance',
        component: () => import('@/views/reports/attendance-report.vue'),
        meta: {
            title: 'Attendance Report'
        }
    }
]