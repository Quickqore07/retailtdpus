export default [
    {
        path: '/payroll/employee-hours',
        name: 'payroll.employee-hours.index',
        component: () => import('@/views/payroll/employee-hours/index.vue'),
        meta: {
            title: 'Employee Hours',
            resource: 'payroll/employee-hours'
        }
    },
    {
        path: '/payroll/employee-hours/:id',
        name: 'payroll.employee-hours.show',
        component: () => import('@/views/payroll/employee-hours/show.vue'),
        meta: {
            title: 'Employee Hours',
            resource: 'payroll/employee-hours'
        }
    },{
        path: '/payroll/employee-hours/:id/edit',
        name: 'payroll.employee-hours.edit',
        component: () => import('@/views/payroll/employee-hours/form.vue'),
        meta: {
            title: 'Edit Employee Hours',
            resource: 'payroll/employee-hours',
            mode: 'edit'
        }
    },
    // {
    //     path: '/payroll/employee-hours/create',
    //     name: 'payroll.employee-hours.create',
    //     component: () => import('@/views/payroll/employee-hours/form.vue'),
    //     meta: {
    //         title: 'Create Employee Hours',
    //         resource: 'payroll/employee-hours',
    //         mode: 'create'
    //     }
    // }
]

