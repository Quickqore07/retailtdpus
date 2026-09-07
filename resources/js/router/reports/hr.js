export default [
    {
        path: '/reports/hr/network-summary',
        name: 'reports.hr.network-summary',
        component: () => import('@/views/reports/hr/network-summary-report.vue'),
        meta: {
            title: 'Network Summary Reports'
        }
    },
    {
        path: '/reports/hr/network-payroll-review',
        name: 'reports.hr.network-payroll-review',
        component: () => import('@/views/reports/hr/network-payroll-review-report.vue'),
        meta: {
            title: 'Network Payroll Review Reports'
        }
    },
    {
        path: '/reports/hr/network-hours',
        name: 'reports.hr.network-hours',
        component: () => import('@/views/reports/hr/network-hours-report.vue'),
        meta: {
            title: 'Network Hours Reports'
        }
    },
    {
        path: '/reports/hr/store-summary',
        name: 'reports.hr.store-summary',
        component: () => import('@/views/reports/hr/store-summary-report.vue'),
        meta: {
            title: 'Store Summary Reports'
        }
    },
    {
        path: '/reports/hr/employee-hours-anomaly',
        name: 'reports.hr.employee-hours-anomaly',
        component: () => import('@/views/reports/hr/employee-hours-anomaly-report.vue'),
        meta: {
            title: 'Employee Hours Anomaly Reports'
        }
    },
    {
        path: '/reports/hr/employee-payroll-info',
        name: 'reports.hr.employee-payroll-info',
        component: () => import('@/views/reports/hr/employee-payroll-info-report.vue'),
        meta: {
            title: 'Employee Payroll Information Report'
        }
    }
]