<?php

namespace App\Services\AR;

/**
 * Tokens for AR invoice / statement email subject and body.
 * Use the token column verbatim in stored templates (e.g. {{invoice_number}}).
 */
class ArEmailTemplatePlaceholders
{
    public const TOKEN_INVOICE_NUMBER = '{{invoice_number}}';

    public const TOKEN_CUSTOMER_NAME = '{{customer_name}}';

    public const TOKEN_EMAIL_NOTES = '{{email_notes}}';

    public const TOKEN_DUE_DAYS = '{{due_days}}';

    public const TOKEN_DUE_DATE = '{{due_date}}';

    public const TOKEN_INVOICE_DATE = '{{invoice_date}}';

    public const TOKEN_INVOICE_AMOUNT = '{{invoice_amount}}';

    public const TOKEN_STATEMENT_START = '{{statement_start}}';

    public const TOKEN_STATEMENT_END = '{{statement_end}}';

    public const TOKEN_OPENING_BALANCE = '{{opening_balance}}';

    public const TOKEN_CLOSING_BALANCE = '{{closing_balance}}';

    /**
     * @return array<int, array{key: string, token: string, label: string}>
     */
    public static function definitions(): array
    {
        return array_merge(self::definitionsForTemplateType('invoice'), self::uniqueStatementDefinitions());
    }

    /**
     * Statement-only rows (not already in invoice list).
     *
     * @return array<int, array{key: string, token: string, label: string}>
     */
    private static function uniqueStatementDefinitions(): array
    {
        return [
            ['key' => 'statement_start', 'token' => self::TOKEN_STATEMENT_START, 'label' => 'Statement start date'],
            ['key' => 'statement_end', 'token' => self::TOKEN_STATEMENT_END, 'label' => 'Statement end date'],
            ['key' => 'opening_balance', 'token' => self::TOKEN_OPENING_BALANCE, 'label' => 'Opening balance'],
            ['key' => 'closing_balance', 'token' => self::TOKEN_CLOSING_BALANCE, 'label' => 'Closing balance'],
        ];
    }

    /**
     * @return array<int, array{key: string, token: string, label: string}>
     */
    public static function definitionsForTemplateType(string $templateType): array
    {
        $invoice = [
            ['key' => 'invoice_number', 'token' => self::TOKEN_INVOICE_NUMBER, 'label' => 'Invoice number'],
            ['key' => 'customer_name', 'token' => self::TOKEN_CUSTOMER_NAME, 'label' => 'Customer name'],
            ['key' => 'email_notes', 'token' => self::TOKEN_EMAIL_NOTES, 'label' => 'Customer email notes'],
            ['key' => 'due_days', 'token' => self::TOKEN_DUE_DAYS, 'label' => 'Due days (signed vs today)'],
            ['key' => 'due_date', 'token' => self::TOKEN_DUE_DATE, 'label' => 'Due date'],
            ['key' => 'invoice_date', 'token' => self::TOKEN_INVOICE_DATE, 'label' => 'Invoice date'],
            ['key' => 'invoice_amount', 'token' => self::TOKEN_INVOICE_AMOUNT, 'label' => 'Invoice amount'],
        ];

        if ($templateType === 'statement') {
            return array_merge(
                [
                    ['key' => 'customer_name', 'token' => self::TOKEN_CUSTOMER_NAME, 'label' => 'Customer name'],
                    ['key' => 'email_notes', 'token' => self::TOKEN_EMAIL_NOTES, 'label' => 'Customer email notes'],
                ],
                self::uniqueStatementDefinitions()
            );
        }

        return $invoice;
    }

    /**
     * @param  array<string, string|int|float|null>  $values
     */
    public static function replace(string $text, array $values): string
    {
        $map = [
            self::TOKEN_INVOICE_NUMBER => (string) ($values['invoice_number'] ?? ''),
            self::TOKEN_CUSTOMER_NAME => (string) ($values['customer_name'] ?? ''),
            self::TOKEN_EMAIL_NOTES => (string) ($values['email_notes'] ?? ''),
            self::TOKEN_DUE_DAYS => (string) ($values['due_days'] ?? ''),
            self::TOKEN_DUE_DATE => (string) ($values['due_date'] ?? ''),
            self::TOKEN_INVOICE_DATE => (string) ($values['invoice_date'] ?? ''),
            self::TOKEN_INVOICE_AMOUNT => (string) ($values['invoice_amount'] ?? ''),
            self::TOKEN_STATEMENT_START => (string) ($values['statement_start'] ?? ''),
            self::TOKEN_STATEMENT_END => (string) ($values['statement_end'] ?? ''),
            self::TOKEN_OPENING_BALANCE => (string) ($values['opening_balance'] ?? ''),
            self::TOKEN_CLOSING_BALANCE => (string) ($values['closing_balance'] ?? ''),
        ];

        return str_replace(array_keys($map), array_values($map), $text);
    }
}
