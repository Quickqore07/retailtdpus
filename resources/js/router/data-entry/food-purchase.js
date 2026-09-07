export default [
    {
        path: '/data-entry/food-purchases',
        name: 'data-entry.food-purchases.index',
        component: () => import('@/views/data-entry/food-purchases/index.vue'),
        meta: {
            title: 'Food Purchases',
            resource: 'data-entry/food-purchases'
        }
    },
    {
        path: '/data-entry/food-purchases/:id',
        name: 'data-entry.food-purchases.show',
        component: () => import('@/views/data-entry/food-purchases/show.vue'),
        meta: {
            title: 'Food Purchase',
            resource: 'data-entry/food-purchases'
        }
    },
    {
        path: '/data-entry/food-purchases/:id/edit',
        name: 'data-entry.food-purchases.edit',
        component: () => import('@/views/data-entry/food-purchases/form.vue'),
        meta: {
            title: 'Edit Food Purchase',
            resource: 'data-entry/food-purchases',
            mode: 'edit'
        }
    },
    {
        path: '/data-entry/food-purchases/create',
        name: 'data-entry.food-purchases.create',
        component: () => import('@/views/data-entry/food-purchases/form.vue'),
        meta: {
            title: 'Create Food Purchase',
            resource: 'data-entry/food-purchases',
            mode: 'create'
        }
    }
]
