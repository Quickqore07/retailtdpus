export default [
    {
        path: '/reports/ttm/report',
        name: 'reports.ttm.report',
        component: () => import('@/views/reports/ttm/ttm-report.vue'),
        meta: {
            title: 'TTM Report'
        }
    },
    {
        path: '/reports/ttm/upload',
        name: 'reports.ttm.upload',
        component: () => import('@/views/reports/ttm/upload/index.vue'),
        meta: {
            title: 'TTM Upload'
        }
    }
]
