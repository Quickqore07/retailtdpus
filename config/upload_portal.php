<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AR account statement (PDF) branding
    |--------------------------------------------------------------------------
    |
    | Used by the upload portal customer account statement PDF. Set env vars
    | to match your company letterhead; when unset, the app name is used for
    | the legal name and other blocks are omitted.
    |
    */
    'ar_account_statement' => [
        'company_legal_name' => env('AR_STATEMENT_COMPANY_LEGAL_NAME','Pie Investments LLC'),
        'company_dba' => env('AR_STATEMENT_COMPANY_DBA'),
        'company_address_lines' => array_values(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n/', (string) env('AR_STATEMENT_COMPANY_ADDRESS', '8520 Tyco Rd, Ste A, Vienna, VA 22182'))
        ))),
        'footer_contact' => env('AR_STATEMENT_FOOTER_CONTACT', ''),
    ],

];
