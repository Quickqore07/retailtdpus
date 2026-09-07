export default [
    {
        path: '/payroll/employee-new-rate-request',
        name: 'payroll.employee-new-rate-requests.index',
        component: () => import('@/views/payroll/employee-new-rate-request/index.vue'),
        meta: {
            title: 'Employee Rate Requests',
            resource: 'payroll/employee-new-rate-request'
        }
    },
    {    
        path: '/payroll/employee-new-rate-request/create',
        name: 'payroll.employee-new-rate-requests.create',
        component: () => import('@/views/payroll/employee-new-rate-request/form.vue'),
        meta: {
            title: 'Create Employee Rate Request',
            resource: 'payroll/employee-new-rate-request',
            mode: 'create'
        }
    },
    {
        path: '/payroll/employee-new-rate-request/:id/edit',
        name: 'payroll.employee-new-rate-requests.edit',
        component: () => import('@/views/payroll/employee-new-rate-request/form.vue'),
        meta: {
            title: 'Edit Employee Rate Request',
            resource: 'payroll/employee-new-rate-request',
            mode: 'edit'
        }
    },
    {
        path: '/payroll/employee-new-rate-request/:id',
        name: 'payroll.employee-new-rate-requests.show',
        component: () => import('@/views/payroll/employee-new-rate-request/show.vue'),
        meta: {
            title: 'Employee Rate Request Details',
            resource: 'payroll/employee-new-rate-request'
        }
    }
]

