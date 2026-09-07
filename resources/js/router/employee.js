export default [
    {
        path: '/employee',
        name: 'employee.index',
        component: () => import('@/views/employee/index.vue'),
        meta: {
            title: 'Employees',
            resource: 'employee'
        }
    },
    {
        path: '/employee/create',
        name: 'employee.create',
        component: () => import('@/views/employee/form.vue'),
        meta: {
            title: 'Create Employee',
            resource: 'employee',
            mode: 'create'
        }
    },
    {
        path: '/employee/:id',
        name: 'employee.show',
        component: () => import('@/views/employee/show.vue'),
        meta: {
            title: 'Employee',
            resource: 'employee'
        }
    },
    {
        path: '/employee/:id/edit',
        name: 'employee.edit',
        component: () => import('@/views/employee/form.vue'),
        meta: {
            title: 'Edit Employee',
            resource: 'employee',
            mode: 'edit'
        }
    },
    {
        path: '/employee/documents/:id',
        name: 'employee.documents',
        component: () => import('@/views/employee/documents.vue'),
        meta: {
            title: 'Employee Documents',
            resource: 'employee/documents'
        }
    }
]