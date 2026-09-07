export default [
    {
        path: '/onboarding/employee-rate-request',
        name: 'onboarding.employee-rate-requests.index',
        component: () => import('@/views/onboarding/employee-rate-request/index.vue'),
        meta: {
            title: 'Employee Rate Requests',
            resource: 'onboarding/employee-rate-request'
        }
    },
    // {
    //     path: '/onboarding/employee-rate-request/create',
    //     name: 'onboarding.employee-rate-requests.create',
    //     component: () => import('@/views/onboarding/employee-rate-request/form.vue'),
    //     meta: {
    //         title: 'Create Employee Rate Request',
    //         resource: 'onboarding/employee-rate-request',
    //         mode: 'create'
    //     }
    // },
    {
        path: '/onboarding/employee-rate-request/:id/edit',
        name: 'onboarding.employee-rate-requests.edit',
        component: () => import('@/views/onboarding/employee-rate-request/form.vue'),
        meta: {
            title: 'Edit Employee Rate Request',
            resource: 'onboarding/employee-rate-request',
            mode: 'edit'
        }
    },
    {
        path: '/onboarding/employee-rate-request/:id',
        name: 'onboarding.employee-rate-requests.show',
        component: () => import('@/views/onboarding/employee-rate-request/show.vue'),
        meta: {
            title: 'Employee Rate Request Details',
            resource: 'onboarding/employee-rate-request'
        }
    }
]

