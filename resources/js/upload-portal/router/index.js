import { createRouter, createWebHistory } from 'vue-router'
import axios from 'axios'

const routes = [
  {
    path: '/upload-portal',
    component: () => import('../layouts/UploadPortalLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'upload-portal-home',
        component: () => import('../views/upload-portal/Home.vue')
      },
      {
        path: 'documents',
        name: 'upload-portal-all-documents',
        component: () => import('../views/upload-portal/Documents.vue')
      },
      {
        path: 'documents/:folderId',
        name: 'upload-portal-documents',
        component: () => import('../views/upload-portal/Documents.vue')
      },
      {
        path: 'folders',
        name: 'upload-portal-folders',
        component: () => import('../views/upload-portal/Folders.vue')
      },
      {
        path: 'ar-invoice',
        component: () => import('../views/upload-portal/ARInvoice.vue'),
        children: [
          {
            path: '',
            redirect: { name: 'upload-portal-ar-customers' }
          },
          {
            path: 'customers',
            name: 'upload-portal-ar-customers',
            component: () => import('../views/upload-portal/ar-invoice/CustomersPage.vue')
          },
          {
            path: 'customers/:customerId',
            name: 'upload-portal-ar-customer-view',
            component: () => import('../views/upload-portal/ARCustomerView.vue')
          },
          {
            path: 'invoices',
            name: 'upload-portal-ar-invoices-list',
            component: () => import('../views/upload-portal/ar-invoice/InvoicesPage.vue')
          },
          {
            path: 'payments',
            name: 'upload-portal-ar-payments-list',
            component: () => import('../views/upload-portal/ar-invoice/PaymentsPage.vue')
          },
          {
            path: 'reports/aging',
            name: 'upload-portal-ar-aging-report',
            component: () => import('../views/upload-portal/ar-invoice/reports/AgingReport.vue')
          },
          {
            path: 'reports/customer-balance',
            name: 'upload-portal-ar-customer-balance-report',
            component: () => import('../views/upload-portal/ar-invoice/reports/CustomerBalance.vue')
          },
          {
            path: 'reports/statement',
            name: 'upload-portal-ar-statement-report',
            component: () => import('../views/upload-portal/ar-invoice/reports/StatementReport.vue')
          }
        ]
      },
      {
        path: 'ar-settings',
        component: () => import('../views/upload-portal/ARSettings.vue'),
        children: [
          {
            path: 'settings',
            name: 'upload-portal-ar-settings',
            component: () => import('../views/upload-portal/ar-settings/SettingsPage.vue')
          },
          {
            path: 'email-templates',
            name: 'upload-portal-ar-email-templates',
            component: () => import('../views/upload-portal/ar-settings/EmailTemplatesPage.vue')
          },
        ]
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach(async (to, from, next) => {
  if (to.matched.some(record => record.meta.requiresAuth)) {
    try {
    const response = await axios.get('/upload-portal/api/me')
      if (response.data.success) {
        next()
      } else {
        window.location.href = '/upload-portal/login'
      }
    } catch (error) {
      window.location.href = '/upload-portal/login'
    }
  } else {
    next()
  }
})

export default router
