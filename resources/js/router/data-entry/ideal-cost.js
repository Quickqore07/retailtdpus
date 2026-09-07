export default [
    {
        path: '/data-entry/ideal-costs',
        name: 'data-entry.ideal-costs.index',
        component: () => import('@/views/data-entry/ideal-costs/index.vue'),
        meta: {
            title: 'Ideal Costs',
            resource: 'data-entry/ideal-costs'
        }
    },
    {
        path: '/data-entry/ideal-costs/:id',
        name: 'data-entry.ideal-costs.show',
        component: () => import('@/views/data-entry/ideal-costs/show.vue'),
        meta: {
            title: 'Ideal Cost',
            resource: 'data-entry/ideal-costs'
        }
    },
    {
        path: '/data-entry/ideal-costs/:id/edit',
        name: 'data-entry.ideal-costs.edit',
        component: () => import('@/views/data-entry/ideal-costs/form.vue'),
        meta: {
            title: 'Edit Ideal Cost',
            resource: 'data-entry/ideal-costs',
            mode: 'edit'
        }
    },
    {
        path: '/data-entry/ideal-costs/create',
        name: 'data-entry.ideal-costs.create',
        component: () => import('@/views/data-entry/ideal-costs/form.vue'),
        meta: {
            title: 'Create Ideal Cost',
            resource: 'data-entry/ideal-costs',
            mode: 'create'
        }
    }
]
