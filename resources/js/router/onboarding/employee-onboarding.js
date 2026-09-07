export default [
    {
        path: '/onboarding/employee/pending-i9-w4',
        name: 'onboarding.pendingI9W4.index',
        component: () => import('@/views/employee/index.vue'),
        meta: {
            title: 'Pending I-9 / W-4',
            resource: 'employee?employee_type=PendingI9W4'
        }
    },
    {
        path: '/onboarding/employee/pending-i9-w4/:id',
        name: 'onboarding.pendingI9W4.show',
        component: () => import('@/views/employee/show.vue'),
        meta: {
            title: 'Pending I-9 / W-4',
            resource: 'employee?employee_type=PendingI9W4'
        }
    },
    {
        path: '/onboarding/employee/pending-i9-w4/:id/edit',
        name: 'onboarding.pendingI9W4.edit',
        component: () => import('@/views/employee/form.vue'),
        meta: {
            title: 'Pending I-9 / W-4',
            resource: 'employee?employee_type=PendingI9W4',
            mode: 'edit'
        }
    },
    {
        path: '/onboarding/employee/missing-profile-picture',
        name: 'onboarding.missingProfilePicture.index',
        component: () => import('@/views/employee/index.vue'),
        meta: {
            title: 'Missing profile photo',
            resource: 'employee?employee_type=MissingProfilePicture'
        }
    },
    {
        path: '/onboarding/employee/missing-profile-picture/:id',
        name: 'onboarding.missingProfilePicture.show',
        component: () => import('@/views/employee/show.vue'),
        meta: {
            title: 'Missing profile photo',
            resource: 'employee?employee_type=MissingProfilePicture'
        }
    },
    {
        path: '/onboarding/employee/missing-profile-picture/:id/edit',
        name: 'onboarding.missingProfilePicture.edit',
        component: () => import('@/views/employee/form.vue'),
        meta: {
            title: 'Missing profile photo',
            resource: 'employee?employee_type=MissingProfilePicture',
            mode: 'edit'
        }
    },
    {
        path: '/onboarding/employee/new',
        name: 'onboarding.new.index',
        component: () => import('@/views/employee/index.vue'),
        meta: {
            title: 'Onboarding',
            resource: 'employee?employee_type=new'
        }
    },  
    {
        path: '/onboarding/employee/new/create',
        name: 'onboarding.new.create',
        component: () => import('@/views/employee/form.vue'),
        meta: {
            title: 'Onboarding',
            resource: 'employee?employee_type=new',
            mode: 'create'
        }
    },  
    {
        path: '/onboarding/employee/new/:id',
        name: 'onboarding.new.show',
        component: () => import('@/views/employee/show.vue'),
        meta: {
            title: 'Onboarding',
            resource: 'employee?employee_type=new'
        }
    },
    {
        path: '/onboarding/employee/new/:id/edit',
        name: 'onboarding.new.edit',
        component: () => import('@/views/employee/form.vue'),
        meta: {
            title: 'Onboarding',
            resource: 'employee?employee_type=new',
            mode: 'edit'
        }
    },
    {
        path: '/onboarding/employee/existing',
        name: 'onboarding.existing.index',
        component: () => import('@/views/employee/index.vue'),
        meta: {
            title: 'Onboarding',
            resource: 'employee?employee_type=existing'
        }
    },
    {
        path: '/onboarding/employee/existing/:id',
        name: 'onboarding.existing.show',
        component: () => import('@/views/employee/show.vue'),
        meta: {
            title: 'Onboarding',
            resource: 'employee?employee_type=existing'
        }
    },
    {
        path: '/onboarding/employee/existing/:id/edit',
        name: 'onboarding.existing.edit',
        component: () => import('@/views/employee/form.vue'),
        meta: {
            title: 'Onboarding',
            resource: 'employee?employee_type=existing',
            mode: 'edit'
        }
    },
    {
        path: '/onboarding/employee/existing/create',
        name: 'onboarding.existing.create',
        component: () => import('@/views/employee/form.vue'),
        meta: {
            title: 'Onboarding',
            resource: 'employee?employee_type=existing',
            mode: 'create'
        }
    },  
]