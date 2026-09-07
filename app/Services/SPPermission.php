<?php

namespace App\Services;

class SPPermission
{

    public static function schema()
    {
        return [
            [
                'name' => 'check-printing-(paychex)',
                'actions' => [
                    'print' => 0,
                    'review' => 0,
                    'update' => 0,
                    "import-quickqore" => 0,
                ]
            ],
            [
                'name' => 'onboarding',
                'actions' => [
                    'review' => 0,
                ]
            ],
            [
                'name' => 'user',
                'actions' => [
                    'password' => 0,
                ]
            ],
            [
                'name' => 'upload-portal',
                'actions' => [
                    'add' => 0,
                    'edit' => 0,
                    'delete' => 0,
                    'view' => 0,
                    'approve' => 0,
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
