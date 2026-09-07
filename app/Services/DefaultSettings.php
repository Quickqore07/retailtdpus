<?php

namespace App\Services; 

class DefaultSettings
{

    public static function schema()
    {
        return [
            [
                'key' => 'food-purchase-due-(days)',
                'default_value'=> 6,
                'value_type'=> 'number',
                'min_value'=> 1,
                'max_value'=> 50,
                'description'=> 'Food purchase due days',
            ],
            [
                'key' => 'royalty-percentage',
                'default_value'=> 5,
                'value_type'=> 'number',
                'min_value'=> 0,
                'max_value'=> 100,
                'description'=> 'Royalty percentage',
            ],
            [
                'key' => 'royalty-payment-day',
                'default_value'=> 10,
                'value_type'=> 'number',
                'min_value'=> 1,
                'max_value'=> 30,
                'description'=> 'Royalty payment day of the month',
            ],
            [
                'key' => 'advertisement-percentage',
                'default_value'=> 7,
                'value_type'=> 'number',
                'min_value'=> 0,
                'max_value'=> 100,
                'description'=> 'Advertisement percentage',
            ],
            [
                'key' => 'advertisement-payment-day',
                'default_value'=> 24,
                'value_type'=> 'number',
                'min_value'=> 1,
                'max_value'=> 30,
                'description'=> 'Royalty payment day of the month',
            ],
            [
                'key' => 'fund-requirement-access',
                'default_value'=> '',
                'description'=> 'Fund requirement access',
            ],
            [
                'key' => 'invoice-company-name',
                'default_value'=> 'Pie Investments LLC',
                'description'=> 'Invoice company name',
            ],
            [
                'key' => 'invoice-company-address',
                'default_value'=> '8520 Tyco Rd, Ste A, Vienna, VA 22182',
                'description'=> 'Invoice company address',
            ],
            [
                'key' => 'invoice-prefix',
                'default_value'=> 'INV',
                'description'=> 'Invoice prefix',
            ],
            [
                'key' => 'invoice-year-(0/1)',
                'default_value'=> 1,
                'min_value'=> 0,
                'max_value'=> 1,
                'description'=> 'Invoice year (0/1)',
            ],
            [
                'key' => 'invoice-starting-number',
                'default_value'=> 1,
                'value_type'=> 'number',
                'min_value'=> 1,
                'max_value'=> 1000000,
                'description'=> 'Invoice starting number',
            ],
            [
                'key' => 'invoice-length',
                'default_value'=> 5,
                'value_type'=> 'number',
                'min_value'=> 1,
                'max_value'=> 10,
                'description'=> 'Invoice length',
            ],
          
            // [
            //     'key' => 'loan-payment-day',
            //     'default_value'=> 24,
            //     'value_type'=> 'number',
            //     'min_value'=> 1,
            //     'max_value'=> 31,
            //     'description'=> 'Loan payment day of the month',
            // ],
            // [
            //     'key' => 'loan-payment-amount',
            //     'default_value'=> 658434.91,
            //     'value_type'=> 'decimal',
            //     'min_value'=> 1,
            //     'max_value'=> 1000000,
            //     'description'=> 'Loan payment amount',
            // ],
            // [
            //     'key' => 'pj-online-payment-day',
            //     'default_value'=> 19,
            //     'value_type'=> 'number',
            //     'min_value'=> 1,
            //     'max_value'=> 31,
            //     'description'=> 'PJ online payment day of the month',
            // ],
            // [
            //     'key' => 'pj-online-payment-amount',
            //     'default_value'=> 1850,
            //     'value_type'=> 'decimal',
            //     'min_value'=> 1,
            //     'max_value'=> 1000000,
            //     'description'=> 'PJ online payment amount',
            // ],
            // [
            //     'key' => 'papa-call-payment-day',
            //     'default_value'=> 19,
            //     'value_type'=> 'number',
            //     'min_value'=> 1,
            //     'max_value'=> 31,
            //     'description'=> 'Papa call payment day of the month',
            // ],
            // [
            //     'key' => 'papa-call-payment-amount',
            //     'default_value'=> 477,
            //     'value_type'=> 'decimal',
            //     'min_value'=> 1,
            //     'max_value'=> 1000000,
            //     'description'=> 'Papa call payment amount',
            // ],
            // [
            //     'key' => 'payroll-dc-start-date',
            //     'default_value'=> '2026-04-27',
            //     'value_type'=> 'date',
            //     'description'=> 'Payroll DC start date',
            // ],
            // [
            //     'key' => 'payroll-dc-amount',
            //     'default_value'=> 1200000,
            //     'value_type'=> 'decimal',
            //     'min_value'=> 1,
            //     'max_value'=> 100000000,
            //     'description'=> 'Payroll DC amount',
            // ],
            // [
            //     'key' => 'payroll-pa-(1)-start-date',
            //     'default_value'=> '2026-05-08',
            //     'value_type'=> 'date',
            //     'description'=> 'Payroll PA (1) start date',
            // ],
            // [
            //     'key' => 'payroll-pa-(1)-amount',
            //     'default_value'=> 225000,
            //     'value_type'=> 'decimal',
            //     'min_value'=> 1,
            //     'max_value'=> 100000000,
            //     'description'=> 'Payroll PA (1) amount',
            // ],
            // [
            //     'key' => 'payroll-pa-(2)-start-date',
            //     'default_value'=> '2026-05-11',
            //     'value_type'=> 'date',
            //     'description'=> 'Payroll PA (2) start date',
            // ],
            // [
            //     'key' => 'payroll-pa-(2)-amount',
            //     'default_value'=> 100000,
            //     'value_type'=> 'decimal',
            //     'min_value'=> 1,
            //     'max_value'=> 100000000,
            //     'description'=> 'Payroll PA (2) amount',
            // ],
        ];
    }
}
