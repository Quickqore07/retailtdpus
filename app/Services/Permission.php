<?php

namespace App\Services;

class Permission
{

    public static function schema()
    {
        return [
            [
                'name' => 'workbright-dashboard',
                'actions' => [
                    'show' => 0,
                ]
            ],
            [
                'name' => 'fund-requirement',
                'actions' => [
                    'show' => 0,
                ]
            ],
            [
                'name' => 'user',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'role',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'activity-log',
                'actions' => [
                    'index' => 0,
                    'show' => 0,
                ]
            ],
            [
                'name' => 'app-settings',
                'actions' => [
                    'update' => 0,
                ]
            ],
            [
                'name' => 'employee-role',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'state',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'county',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'region',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'area',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'workgroup',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'office',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'company',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'company-group',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'minimum-wage',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'ledger',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                    'bank_details' => 0,
                ]
            ],
            [
                'name' => 'check-master',
                'actions' => [
                    'index' => 0,
                    'update' => 0,
                ]
            ],
            [
                'name' => 'bank-rule',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'bank-category-rule',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'pandl',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'pj-calendar',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],

            [
                'name' => 'upload-user',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'upload-workgroup',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'upload-company',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'upload-folder',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'employee-hour',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'employee-hours-request',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                    'approve' => 0,
                ]
            ],
            [
                'name' => 'employee-new-rate-request',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                    'approve' => 0,
                ]
            ],
            [
                'name' => 'mwa',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'pending-applications',
                'actions' => [
                    'index' => 0,
                    'show' => 0,
                    'reject' => 0
                ]
            ],
            [
                'name' => 'i9-review',
                'actions' => [
                    'index' => 0,
                    'show' => 0
                ]
            ],
            [
                'name' => 'employee-rate-request',
                'actions' => [
                    'index' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                    'approve' => 0,
                ]
            ],
            [
                'name' => 'employee-pending-i9-w4',
                'actions' => [
                    'index' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'employee-missing-profile-picture',
                'actions' => [
                    'index' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'benefits',
                'actions' => [
                    'index' => 0,
                    'send-mail' => 0,
                ]
            ],
            [
                'name' => 'manual-i9',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'update' => 0,
                    'approve' => 0,
                    'review' => 0,
                    'left' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'employee-inactive',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'driver',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'daily-sales',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            // [
            //     'name' => 'other-daily-sales',
            //     'actions' => [
            //         'index' => 0,
            //         'create' => 0,
            //         'show' => 0,
            //         'update' => 0,
            //         'delete' => 0,
            //     ]
            // ],
            [
                'name' => 'bank-entry',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'bank-upload',
                'actions' => [
                    'index' => 0,
                    'show' => 0,
                    'delete' => 0,
                    'upload' => 0,
                    'opening-balance' => 0,
                    'update-opening-balance' => 0,
                    'delete-opening-balance' => 0,
                ]
            ],
            // [
            //     'name' => 'bank-deposit',
            //     'actions' => [
            //         'index' => 0,
            //         'create' => 0,
            //         'show' => 0,
            //         'update' => 0,
            //         'delete' => 0,
            //     ]
            // ],
            // [
            //     'name' => 'shortage',
            //     'actions' => [
            //         'index' => 0,
            //         'create' => 0,
            //         'show' => 0,
            //         'update' => 0,
            //         'delete' => 0,
            //     ]
            // ],

            [
                'name' => 'food-purchase',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'ideal-cost',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'wc-entry',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'payroll-journal',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                ]
            ],
            [
                'name' => 'royalty-fees',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'mwa',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            // [
            //     'name' => 'pj-payment',
            //     'actions' => [
            //         'index' => 0,
            //         'create' => 0,
            //         'show' => 0,
            //         'update' => 0,
            //         'delete' => 0,
            //     ]
            // ],
            [
                'name' => 'vendor',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'expense-type',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'purchase-invoice',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                    'approve' => 0,
                    // 'paid' => 0,
                    'payment' => 0,
                    'cancel' => 0,
                ]
            ],
            [
                'name' => 'bill-wise-report',
                'actions' => [
                    'index' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'monthly-report',
                'actions' => [
                    'index' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'pj-ledger',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'fees-upload',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'charge-back',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                    'upload-receipt' => 0,
                    'submit' => 0,
                    'mark-as-credited' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'charge-back-reason-code',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'charge-back-entry-mode',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'weekly-network-ar-report',
                'actions' => [
                    'index' => 0,
                    'update' => 0,
                ]
            ],
            [
                'name' => 'balance-due-network-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'deposit-report',
                'actions' => [
                    'index' => 0,
                    'unsettle' => 0,
                ]
            ],
            [
                'name' => 'missing-bank-amount-report',
                'actions' => [
                    'index' => 0,
                    'update' => 0,
                    'manual-settlement' => 0,
                ]
            ],
            [
                'name' => 'employee',
                'actions' => [
                    'index' => 0,
                    'create' => 0,
                    'show' => 0,
                    'update' => 0,
                    'delete' => 0,
                    'inactive' => 0,
                    'documents-show' => 0,
                    'documents-delete' => 0,
                    'documents-upload' => 0,
                ]
            ],
            [
                'name' => 'payroll-report',
                'actions' => [
                    'index' => 0,
                    'update' => 0,
                ]
            ],
            [
                'name' => 'paychex-report',
                'actions' => [
                    'index' => 0,
                    'update' => 0,
                ]
            ],
            [
                'name' => 'payroll-labour-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'payroll-journal-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'network-check-report',
                'actions' => [
                    'index' => 0,
                    'update' => 0,
                ]
            ],
            [
                'name' => 'network-instant-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'mwa-report',
                'actions' => [
                    'index' => 0,
                    'update' => 0,
                ]
            ],
            [
                'name' => 'network-summary-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'network-payroll-review-report',
                'actions' => [
                    'index' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'network-hours-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'store-summary-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'employee-hours-anomaly-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'employee-payroll-info-report',
                'actions' => [
                    'index' => 0,
                ]
            ],

            [
                'name' => 'ideal-cost-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'purchase-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            // [
            //     'name' => 'sales-report',
            //     'actions' => [
            //         'index' => 0,
            //     ]
            // ],
            [
                'name' => 'ideal-cost-purchase-difference-report',
                'actions' => [
                    'index' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'ideal-cost-summary-report',
                'actions' => [
                    'index' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'food-truck-report',
                'actions' => [
                    'index' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'sales-projection-report',
                'actions' => [
                    'index' => 0,
                    'submit' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'flm-report',
                'actions' => [
                    'index' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'flm-t-report',
                'actions' => [
                    'index' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'store-wise-weekly-flm-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'network-weekly-sales-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'network-monthly-sales-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'bank-deposit-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            // [
            //     'name' => 'cash-ledger-report',
            //     'actions' => [
            //         'index' => 0,
            //     ]
            // ],
            // [
            //     'name' => 'daily-cash-report',
            //     'actions' => [
            //         'index' => 0,
            //     ]
            // ],
            // [
            //     'name' => 'cash-short-sales-report',
            //     'actions' => [
            //         'index' => 0,
            //         'area-manager' => 0,
            //         'regional-director' => 0,
            //     ]
            // ],
            // [
            //     'name' => 'cash-payout-sales-report',
            //     'actions' => [
            //         'index' => 0,
            //         'area-manager' => 0,
            //         'regional-director' => 0,
            //     ]
            // ],
            // [
            //     'name' => 'pending-bank-deposit-report',
            //     'actions' => [
            //         'index' => 0,
            //     ]
            // ],
            [
                'name' => 'company-wise-fund-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'bank-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'dtm-report',
                'actions' => [
                    'index' => 0
                ]
            ],
            [
                'name' => 'attendance-report',
                'actions' => [
                    'index' => 0,
                    'update' => 0,
                ]
            ],
            [
                'name' => 'finance-report',
                'actions' => [
                    'index' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'detailed-finance-report',
                'actions' => [
                    'index' => 0,
                    'area-manager' => 0,
                    'regional-director' => 0,
                ]
            ],
            [
                'name' => 'store-wise-finance-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'pl-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'ttm-upload',
                'actions' => [
                    'index' => 0,
                    'upload' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'ttm-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'upload-document',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'upload-portal',
                'actions' => [
                    'index' => 0,
                    'add' => 0,
                    'edit' => 0,
                    'delete' => 0,
                    'view' => 0,
                    'edit-after-approval' => 0,
                    'create-check' => 0,
                ]
            ],
            [
                'name' => 'upload-portal-customer',
                'actions' => [
                    'index' => 0,
                    'add' => 0,
                    'edit' => 0,
                    'delete' => 0,
                    'view' => 0,
                    'view-statement' => 0,
                    'send-invoice' => 0,
                    'send-statement' => 0,
                ]
            ],
            [
                'name' => 'upload-portal-invoice',
                'actions' => [
                    'index' => 0,
                    'add' => 0,
                    'edit' => 0,
                    'delete' => 0,
                    'view' => 0,
                    'mail-sent' => 0,
                ]
            ],
            [
                'name' => 'upload-portal-customer-payment',
                'actions' => [
                    'index' => 0,
                    'add' => 0,
                    'edit' => 0,
                    'delete' => 0,
                    'view' => 0,
                ]
            ],
            [
                'name' => 'upload-portal-ar-aging-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'upload-portal-ar-customer-balance-report',
                'actions' => [
                    'index' => 0,
                ]
            ],
            [
                'name' => 'upload-portal-ar-email-template',
                'actions' => [
                    'index' => 0,
                    'add' => 0,
                    'edit' => 0,
                    'delete' => 0,
                ]
            ],
            [
                'name' => 'upload-portal-ar-settings',
                'actions' => [
                    'index' => 0,
                ]
            ],
        ];
    }
}
