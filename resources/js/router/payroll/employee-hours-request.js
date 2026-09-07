export default [
    {
        path: '/payroll/employee-hours-request',
        name: 'payroll.employee-hours-requests.index',
        component: () => import('@/views/payroll/employee-hours-request/index.vue'),
        meta: {
            title: 'Employee Hours Requests',
            resource: 'payroll/employee-hours-request'
        }
    },
    {
        path: '/payroll/employee-hours-request/create',
        name: 'payroll.employee-hours-requests.create',
        component: () => import('@/views/payroll/employee-hours-request/form.vue'),
        meta: {
            title: 'New Employee Hours Request',
            resource: 'payroll/employee-hours-request',
            mode: 'create'
        }
    },
    {
        path: '/payroll/employee-hours-request/:id',
        name: 'payroll.employee-hours-requests.show',
        component: () => import('@/views/payroll/employee-hours-request/show.vue'),
        meta: {
            title: 'Employee Hours Request Details',
            resource: 'payroll/employee-hours-request'
        }
    }
];
