export default [
    {
        path: '/reports/payroll/weekly',
        name: 'reports.payroll.weekly',
        component: () => import('@/views/reports/payroll/weekly-payroll-report.vue'),
        meta: {
            title: 'Weekly Payroll Reports'
        }
    },
    {
        path: '/reports/payroll/paychex',
        name: 'reports.payroll.paychex',
        component: () => import('@/views/reports/payroll/paychex-report.vue'),
        meta: {
            title: 'Paychex Reports'
        }
    },
    {
        path: '/reports/payroll/labour',
        name: 'reports.payroll.labour',
        component: () => import('@/views/reports/payroll/labour-report.vue'),
        meta: {
            title: 'Labour Reports'
        }
    },
    {
        path: '/reports/payroll/payroll-journal',
        name: 'reports.payroll.payroll-journal',
        component: () => import('@/views/reports/payroll/payroll-journal-report.vue'),
        meta: {
            title: 'Payroll Journal Report'
        }
    },
    {
        path: '/reports/payroll/network-check',
        name: 'reports.payroll.network-check',
        component: () => import('@/views/reports/payroll/network-check-report.vue'),
        meta: {
            title: 'Network Check Reports'
        }
    },
    {
        path: '/reports/payroll/network-instant',
        name: 'reports.payroll.network-instant',
        component: () => import('@/views/reports/payroll/network-instant-report.vue'),
        meta: {
            title: 'Network Instant Reports'
        }
    },
    {
        path: '/reports/payroll/mwa',
        name: 'reports.payroll.mwa',
        component: () => import('@/views/reports/payroll/mwa-report.vue'),
        meta: {
            title: 'MWA Report'
        }
    }
]