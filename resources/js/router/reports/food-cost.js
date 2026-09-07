export default [
    {
        path: '/reports/food-cost/flm',
        name: 'reports.food-cost.flm',
        component: () => import('@/views/reports/food-cost/flm-report.vue'),
        meta: {
            title: 'FLM Report'
        }
    },
    {
        path: '/reports/food-cost/flm-t',
        name: 'reports.food-cost.flm-t',
        component: () => import('@/views/reports/food-cost/flm-t-report.vue'),
        meta: {
            title: 'FLM-T Report'
        }
    },
    {
        path: '/reports/food-cost/store-wise-weekly-flm',
        name: 'reports.food-cost.store-wise-weekly-flm',
        component: () => import('@/views/reports/food-cost/store-wise-weekly-flm-report.vue'),
        meta: {
            title: 'Store Wise Weekly FLM Report'
        }
    },
]