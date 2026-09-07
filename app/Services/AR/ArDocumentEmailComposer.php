<?php

namespace App\Services\AR;

use App\Models\AR\ArEmailTemplate;
use App\Models\AR\SalesInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ArDocumentEmailComposer
{
    /**
     * @return array<string, string>
     */
    public static function invoicePlaceholderValues(SalesInvoice $invoice): array
    {
        $invoice->loadMissing(['customer']);

        $paid = (float) DB::table('customer_payment_items')
            ->where('sales_invoice_id', $invoice->id)
            ->selectRaw('COALESCE(SUM(amount_applied + discount),0) as s')
            ->value('s');

        $dueDateStr = $invoice->due_date
            ? $invoice->due_date->format('m/d/Y')
            : '';

        $invDateStr = $invoice->invoice_date
            ? $invoice->invoice_date->format('m/d/Y')
            : '';

        $dueDays = '';
        if ($invoice->due_date) {
            $due = $invoice->due_date->copy()->startOfDay();
            $today = now()->startOfDay();
            $dueDays = (string) ((int) $today->diffInDays($due, false));
        }

        $customerName = (string) ($invoice->customer?->documentDisplayName() ?? '');
        $emailNotes = trim((string) ($invoice->customer?->email_notes ?? ''));

        return [
            'invoice_number' => (string) $invoice->invoice_number,
            'customer_name' => $customerName,
            'email_notes' => $emailNotes,
            'due_days' => $dueDays,
            'due_date' => $dueDateStr,
            'invoice_date' => $invDateStr,
            'invoice_amount' => '$'.number_format((float) $invoice->amount, 2),
            'statement_start' => '',
            'statement_end' => '',
            'opening_balance' => '',
            'closing_balance' => '',
        ];
    }

    /**
     * @param  array{
     *     customer: \App\Models\Upload\UploadPortalCustomer,
     *     start_date: string,
     *     end_date: string,
     *     opening_balance: float,
     *     closing_balance: float
     * }  $payload
     * @return array<string, string>
     */
    public static function statementPlaceholderValues(array $payload): array
    {
        $customer = $payload['customer'];
        $fmtMoney = static fn (float $n): string => '$'.number_format($n, 2);
        $fmtDate = static fn (string $ymd): string => Carbon::parse($ymd)->format('m/d/Y');

        return [
            'invoice_number' => '',
            'customer_name' => $customer->documentDisplayName(),
            'email_notes' => trim((string) ($customer->email_notes ?? '')),
            'due_days' => '',
            'due_date' => '',
            'invoice_date' => '',
            'invoice_amount' => '',
            'statement_start' => $fmtDate($payload['start_date']),
            'statement_end' => $fmtDate($payload['end_date']),
            'opening_balance' => $fmtMoney((float) $payload['opening_balance']),
            'closing_balance' => $fmtMoney((float) $payload['closing_balance']),
        ];
    }

    /**
     * @return list<array{id: int, name: string, is_default: bool}>
     */
    public static function templatesList(string $templateType): array
    {
        return ArEmailTemplate::query()
            ->where('template_type', $templateType)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default'])
            ->map(static fn ($t) => [
                'id' => (int) $t->id,
                'name' => (string) $t->name,
                'is_default' => (bool) $t->is_default,
            ])
            ->values()
            ->all();
    }

    public static function resolveTemplateId(?int $requestedId, string $templateType): ?int
    {
        if ($requestedId) {
            $exists = ArEmailTemplate::query()
                ->where('id', $requestedId)
                ->where('template_type', $templateType)
                ->exists();
            if ($exists) {
                return $requestedId;
            }
        }

        $def = ArEmailTemplate::query()
            ->where('template_type', $templateType)
            ->where('is_default', true)
            ->value('id');
        if ($def) {
            return (int) $def;
        }

        $first = ArEmailTemplate::query()->where('template_type', $templateType)->orderBy('name')->value('id');

        return $first ? (int) $first : null;
    }

    /**
     * @param  array<string, string>  $values
     * @return array{subject: string, body: string}
     */
    public static function preview(?int $templateId, string $templateType, array $values): array
    {
        $id = self::resolveTemplateId($templateId, $templateType);
        $template = $id ? ArEmailTemplate::query()->find($id) : null;
        if (! $template) {
            return [
                'subject' => $templateType === 'invoice' ? 'Invoice' : 'Account statement',
                'body' => self::appendCustomerEmailNotes('', (string) ($values['email_notes'] ?? '')),
            ];
        }

        $body = ArEmailTemplatePlaceholders::replace((string) $template->body, $values);
        $body = self::appendCustomerEmailNotes($body, (string) ($values['email_notes'] ?? ''));

        return [
            'subject' => ArEmailTemplatePlaceholders::replace((string) $template->subject, $values),
            'body' => $body,
        ];
    }

    /**
     * Append customer-specific email notes when they are not already present in the body.
     */
    public static function appendCustomerEmailNotes(string $body, ?string $emailNotes): string
    {
        $notes = trim((string) $emailNotes);
        if ($notes === '') {
            return $body;
        }

        if ($body !== '' && str_contains($body, $notes)) {
            return $body;
        }

        $body = rtrim($body);

        return $body === '' ? $notes : $body."\n\n".$notes;
    }
}
