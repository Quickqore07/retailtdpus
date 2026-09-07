import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../views/dashboard.vue';
import settings from './settings';
import payroll from './payroll';
import employee from './employee';
import reports from './reports';
import onboarding from './onboarding';
import ar from './ar';
import ap from './ap';
import dataEntry from './data-entry';
import upload from './upload';
import chargeBack from './charge-back';

const routes = [
    {
        path: '/',
        name: 'dashboard',
        component: Dashboard
    },
    ...settings,
    ...payroll,
    ...employee,
    ...onboarding,
    ...ar,
    ...ap,
    ...dataEntry,
    ...upload,
    ...reports,
    ...chargeBack,
];

const router = createRouter({
    history: createWebHistory(),
    routes
});


router.beforeEach((to, from, next) => {
    // if(to.meta?.resource){
    //     useRequest('get', `/api/${to.meta.resource}`).then((res) => {
    //         next((vm) => {
    //             vm.setData(res);
    //         });
    //     }).catch((error) => {
    //         next((vm) => {
    //             vm.cancel();
    //         });
    //     });
    // }
    next();
});

export default router;