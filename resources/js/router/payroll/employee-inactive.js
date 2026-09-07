export default [
    {
        path: '/payroll/employee-inactive',
        name: 'payroll.employee-inactive.index',
        component: () => import('@/views/payroll/employee-inactive/index.vue'),
        meta: {
            title: 'Employee Inactive',
            resource: 'payroll/employee-inactive'
        }
    },
    {
        path: '/payroll/employee-inactive/:id',
        name: 'payroll.employee-inactive.show',
        component: () => import('@/views/payroll/employee-inactive/show.vue'),
        meta: {
            title: 'Employee Inactive',
            resource: 'payroll/employee-inactive'
        }
    },
    {
        path: '/payroll/employee-inactive/:id/edit',
        name: 'payroll.employee-inactive.edit',
        component: () => import('@/views/payroll/employee-inactive/form.vue'),
        meta: {
            title: 'Edit Employee Inactive',
            resource: 'payroll/employee-inactive',
            mode: 'edit'
        }
    },
    {
        path: '/payroll/employee-inactive/create',
        name: 'payroll.employee-inactive.create',
        component: () => import('@/views/payroll/employee-inactive/form.vue'),
        meta: {
            title: 'Create Employee Inactive',
            resource: 'payroll/employee-inactive',
            mode: 'create'
        }
    }
]
