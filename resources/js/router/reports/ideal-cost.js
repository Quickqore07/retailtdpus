export default [
    
    {
        path: '/reports/ideal-cost/purchase',
        name: 'reports.ideal-cost.purchase',
        component: () => import('@/views/reports/ideal-cost/purchase-report.vue'),
        meta: {
            title: 'Purchase Report (Ideal Cost)'
        }
    },
    {
        path: '/reports/ideal-cost/sales',
        name: 'reports.ideal-cost.sales',
        component: () => import('@/views/reports/ideal-cost/sales-report.vue'),
        meta: {
            title: 'Sales Report'
        }
    },
    {
        path: '/reports/ideal-cost/ideal-cost',
        name: 'reports.ideal-cost.ideal-cost',
        component: () => import('@/views/reports/ideal-cost/ideal-cost-report.vue'),
        meta: {
            title: 'Ideal Cost Report'
        }
    },
    {
        path: '/reports/ideal-cost/ideal-cost-purchase-difference',
        name: 'reports.ideal-cost.ideal-cost-purchase-difference',
        component: () => import('@/views/reports/ideal-cost/ideal-cost-purchase-difference-report.vue'),
        meta: {
            title: 'Ideal Cost & Purchase Difference Report'
        }
    },
    {
        path: '/reports/ideal-cost/ideal-cost-summary',
        name: 'reports.ideal-cost.ideal-cost-summary',
        component: () => import('@/views/reports/ideal-cost/summary-report.vue'),
        meta: {
            title: 'Ideal Cost Summary Report'
        }
    },
    {
        path: '/reports/ideal-cost/food-truck',
        name: 'reports.ideal-cost.food-truck',
        component: () => import('@/views/reports/ideal-cost/food-truck-report.vue'),
        meta: {
            title: 'Food Truck Report'
        }
    },
    {
        path: '/reports/ideal-cost/sales-projection',
        name: 'reports.ideal-cost.sales-projection',
        component: () => import('@/views/reports/ideal-cost/sales-projection-report.vue'),
        meta: {
            title: 'Sales Projection Report'
        }
    }
]