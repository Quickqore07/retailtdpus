export default [
    {
        path: '/settings/employee-roles',
        name: 'settings.employee-roles.index',
        component: () => import('@/views/settings/employee-roles/index.vue'),
        meta: {
            title: 'Employee Roles',
            resource: 'settings/employee-roles'
        }
    },
    {
        path: '/settings/employee-roles/create',
        name: 'settings.employee-roles.create',
        component: () => import('@/views/settings/employee-roles/form.vue'),
        meta: {
            title: 'Create Employee Role',
            resource: 'settings/employee-roles',
            mode: 'create'
        }
    },
    {
        path: '/settings/employee-roles/:id',
        name: 'settings.employee-roles.show',
        component: () => import('@/views/settings/employee-roles/show.vue'),
        meta: {
            title: 'Employee Role',
            resource: 'settings/employee-roles'
        }
    },
    {
        path: '/settings/employee-roles/:id/edit',
        name: 'settings.employee-roles.edit',
        component: () => import('@/views/settings/employee-roles/form.vue'),
        meta: {
            title: 'Edit Employee Role',
            resource: 'settings/employee-roles',
            mode: 'edit'
        }
    }
]

