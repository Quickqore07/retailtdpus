export default [
    {
        path: '/onboarding/employee-confirmation',
        name: 'onboarding.employee-confirmation.index',
        component: () => import('@/views/onboarding/employee-confirmation/index.vue'),
        meta: {
            title: 'Employee Confirmation',
            resource: 'employee-confirmation'
        }
    }
]
