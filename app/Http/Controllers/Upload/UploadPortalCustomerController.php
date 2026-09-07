<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\AR\CustomerPayment;
use App\Models\AR\CustomerPaymentItem;
use App\Models\AR\SalesInvoice;
use App\Models\Settings\Setting;
use App\Models\Upload\UploadPortalCustomer;
use App\Models\User;
use App\Services\AR\ArDocumentEmailComposer;
use App\Services\MailService;
use App\Services\Quickqore\QuickqoreInvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UploadPortalCustomerController extends Controller
{
    private const INVOICE_CONDITIONS = [
        'of current month',
        'of the following month',
        'day(s) after the invoice date',
        'day(s) after the end of the invoice month',
    ];

    /** Column order for CSV export / expected import header (name is the upsert key). */
    private const CUSTOMER_CSV_COLUMNS = [
        'name',
        'email',
        'mobile',
        'fax',
        'primary_person_name',
        'email_notes',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'city',
        'state',
        'country',
        'zip_code',
        'bank_name',
        'account_number',
        'routing_number',
        'credit_card_number',
        'card_expiry',
        'name_on_card',
        'financial_zip_code',
        'invoice_due',
        'invoice_condition',
    ];

    private function getAuthenticatedUploadUser()
    {
        $user = Auth::guard('upload-portal')->user();
        if (!$user) {
            return null;
        }
        return User::with('role')->find($user->id);
    }

    private function ensurePermission($user, string $permission): bool
    {
        return Gate::forUser($user)->allows('sp-access', $permission)
            || Gate::forUser($user)->allows('access', $permission);
    }

    private function canViewCustomerStatement($user): bool
    {
        return $this->ensurePermission($user, 'upload-portal-customer.view')
            || $this->ensurePermission($user, 'upload-portal-customer.index')
            || $this->ensurePermission($user, 'upload-portal-customer.view-statement');
    }

    public function index(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (
            ! $this->ensurePermission($user, 'upload-portal-customer.index')
            && ! $this->ensurePermission($user, 'upload-portal-customer.view-statement')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view customers',
            ], 403);
        }

        $query = UploadPortalCustomer::query()
            ->with(['createdBy', 'updatedBy'])
            ->withSum([
                'salesInvoices as ar_invoice_amount_sum' => function ($q) {
                    $q->where('status', '!=', 'cancelled');
                },
            ], 'amount')
            ->withSum('customerPayments as ar_payment_amount_sum', 'total_amount');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('primary_person_name', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 25);
        $customers = $query->orderByDesc('id')->paginate($perPage);

        $customers->getCollection()->transform(function (UploadPortalCustomer $customer) {
            $invoices = (float) ($customer->ar_invoice_amount_sum ?? 0);
            $payments = (float) ($customer->ar_payment_amount_sum ?? 0);
            $customer->setAttribute('outstanding_balance', round($invoices - $payments, 2));
            $customer->setAttribute('credit_balance', CustomerPaymentController::computeCustomerCreditBalance((int) $customer->id));
            $customer->makeHidden(['ar_invoice_amount_sum', 'ar_payment_amount_sum']);

            return $customer;
        });

        return response()->json([
            'success' => true,
            'customers' => $customers,
            'invoice_conditions' => self::INVOICE_CONDITIONS,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-customer.index')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to export customers',
            ], 403);
        }

        $query = UploadPortalCustomer::query()->orderBy('name');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('primary_person_name', 'like', "%{$search}%");
            });
        }

        $filename = 'upload-portal-customers-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, self::CUSTOMER_CSV_COLUMNS);
            foreach ($query->cursor() as $customer) {
                $row = [];
                foreach (self::CUSTOMER_CSV_COLUMNS as $col) {
                    $val = $customer->{$col};
                    if ($val === null) {
                        $row[] = '';
                    } elseif (is_numeric($val)) {
                        $row[] = (string) $val;
                    } else {
                        $row[] = (string) $val;
                    }
                }
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importCsv(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|' . upload_max_file_size_rule(),
        ]);

        $canAdd = $this->ensurePermission($user, 'upload-portal-customer.add');
        $canEdit = $this->ensurePermission($user, 'upload-portal-customer.edit');
        if (! $canAdd && ! $canEdit) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to import customers',
            ], 403);
        }

        $path = $request->file('file')->getRealPath();
        if ($path === false || ! is_readable($path)) {
            return response()->json(['success' => false, 'message' => 'Could not read uploaded file'], 422);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return response()->json(['success' => false, 'message' => 'Could not open uploaded file'], 422);
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            return response()->json(['success' => false, 'message' => 'The file is empty'], 422);
        }
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
        $headerRow = str_getcsv($firstLine);
        $headerMap = [];
        foreach ($headerRow as $i => $h) {
            $key = strtolower(trim((string) $h));
            if ($key !== '') {
                $headerMap[$i] = $key;
            }
        }
        $expected = array_map(static fn (string $c): string => strtolower($c), self::CUSTOMER_CSV_COLUMNS);
        $headerValues = array_values($headerMap);
        $headerFieldSet = [];
        foreach ($headerValues as $field) {
            if (in_array($field, $expected, true)) {
                $headerFieldSet[$field] = true;
            }
        }
        if (! isset($headerFieldSet['name'])) {
            fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'Invalid CSV: header row must include a "name" column (export uses name as the row key).',
            ], 422);
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $rowNum = 1;

        while (($line = fgets($handle)) !== false) {
            $rowNum++;
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cells = str_getcsv($line);
            $assoc = [];
            foreach ($headerMap as $colIndex => $field) {
                if (! in_array($field, $expected, true)) {
                    continue;
                }
                $assoc[$field] = isset($cells[$colIndex]) ? trim((string) $cells[$colIndex]) : '';
            }
            if (! isset($assoc['name']) || $assoc['name'] === '') {
                $errors[] = ['row' => $rowNum, 'message' => 'Name is required.'];

                continue;
            }
            $payload = [];
            foreach (self::CUSTOMER_CSV_COLUMNS as $col) {
                $lc = strtolower($col);
                if (! isset($headerFieldSet[$lc])) {
                    continue;
                }
                $raw = $assoc[$lc] ?? '';
                if ($raw === '') {
                    $payload[$col] = $col === 'invoice_due' ? 0 : null;

                    continue;
                }
                if ($col === 'invoice_due') {
                    $payload[$col] = (int) $raw;

                    continue;
                }
                $payload[$col] = $raw;
            }

            $rules = array_intersect_key($this->customerValidationRules(), array_flip(array_keys($payload)));
            $validator = Validator::make($payload, $rules);
            if ($validator->fails()) {
                $errors[] = [
                    'row' => $rowNum,
                    'message' => $validator->errors()->first(),
                ];

                continue;
            }
            $validated = $validator->validated();

            $existing = UploadPortalCustomer::query()
                ->where('name', $validated['name'])
                ->first();

            try {
                if ($existing) {
                    if (! $canEdit) {
                        $errors[] = [
                            'row' => $rowNum,
                            'message' => 'Customer "'.$validated['name'].'" already exists; edit permission is required to update.',
                        ];

                        continue;
                    }
                    $validated['updated_by'] = $user->id;
                    $existing->fill($validated)->save();
                    $updated++;
                } else {
                    if (! $canAdd) {
                        $errors[] = [
                            'row' => $rowNum,
                            'message' => 'Customer "'.$validated['name'].'" does not exist; add permission is required to create.',
                        ];

                        continue;
                    }
                    $validated['created_by'] = $user->id;
                    $validated['updated_by'] = $user->id;
                    UploadPortalCustomer::create($validated);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'message' => 'Save failed: '.$e->getMessage(),
                ];
            }
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'message' => sprintf('Import finished: %d created, %d updated.', $created, $updated),
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ]);
    }

    public function show($id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (
            ! $this->ensurePermission($user, 'upload-portal-customer.view')
            && ! $this->ensurePermission($user, 'upload-portal-customer.index')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this customer',
            ], 403);
        }

        $customer = UploadPortalCustomer::query()
            ->with(['createdBy', 'updatedBy'])
            ->withSum([
                'salesInvoices as ar_invoice_amount_sum' => function ($q) {
                    $q->where('status', '!=', 'cancelled');
                },
            ], 'amount')
            ->withSum('customerPayments as ar_payment_amount_sum', 'total_amount')
            ->find($id);

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $invoices = (float) ($customer->ar_invoice_amount_sum ?? 0);
        $payments = (float) ($customer->ar_payment_amount_sum ?? 0);
        $customer->setAttribute('outstanding_balance', round($invoices - $payments, 2));
        $customer->setAttribute('credit_balance', CustomerPaymentController::computeCustomerCreditBalance((int) $customer->id));
        $customer->makeHidden(['ar_invoice_amount_sum', 'ar_payment_amount_sum']);

        return response()->json([
            'success' => true,
            'customer' => $customer,
        ]);
    }

    public function statement(Request $request, $id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->canViewCustomerStatement($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this customer',
            ], 403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();


        $payload = $this->buildCustomerStatementPayload($id, $start, $end);
        if ($payload === null) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        return response()->json([
            'success' => true,
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
            'opening_balance' => $payload['opening_balance'],
            'closing_balance' => $payload['closing_balance'],
            'credit_balance' => $payload['credit_balance'] ?? 0,
            'lines' => $payload['lines'],
        ]);
    }

    public function statements(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->canViewCustomerStatement($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view account statements',
            ], 403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'include_zero' => 'nullable|boolean',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();
        $includeZero = filter_var($request->input('include_zero', false), FILTER_VALIDATE_BOOLEAN);

        $statements = $this->buildAllCustomerStatementsPayload($start, $end, $includeZero);

        return response()->json([
            'success' => true,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'statements' => $statements,
        ]);
    }

    public function statementsPdf(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->canViewCustomerStatement($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view account statements',
            ], 403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'include_zero' => 'nullable|boolean',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();
        $includeZero = filter_var($request->input('include_zero', false), FILTER_VALIDATE_BOOLEAN);

        $summaries = $this->buildAllCustomerStatementsPayload($start, $end, $includeZero);
        if ($summaries === []) {
            return response()->json([
                'success' => false,
                'message' => $includeZero
                    ? 'No customers found for this date range.'
                    : 'No customers with activity in this date range.',
            ], 404);
        }

        $statementPages = [];
        foreach ($summaries as $summary) {
            $payload = $this->buildCustomerStatementPayload($summary['customer_id'], $start, $end);
            if ($payload !== null) {
                $statementPages[] = $this->arAccountStatementViewData($payload);
            }
        }

        if ($statementPages === []) {
            return response()->json([
                'success' => false,
                'message' => 'Could not generate statements PDF',
            ], 404);
        }

        $filename = sprintf(
            'account-statements-all-%s-to-%s.pdf',
            $start->toDateString(),
            $end->toDateString()
        );

        $pdf = Pdf::loadView('pdfs.upload-portal-ar-all-account-statements', [
            'statements' => $statementPages,
        ]);

        return $pdf->download($filename);
    }

    public function statementPdf(Request $request, $id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->canViewCustomerStatement($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this customer',
            ], 403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $payload = $this->buildCustomerStatementPayload($id, $start, $end);
        if ($payload === null) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $customer = $payload['customer'];
        $fileSafe = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $customer->name);
        $fileSafe = trim($fileSafe, '-') ?: 'customer';
        $filename = sprintf(
            'account-statement-%s-%s-to-%s.pdf',
            $fileSafe,
            $payload['start_date'],
            $payload['end_date']
        );

        $pdf = Pdf::loadView('pdfs.upload-portal-ar-account-statement', $this->arAccountStatementViewData($payload));

        return $pdf->download($filename);
    }

    public function statementEmailCompose(Request $request, $id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-customer.send-statement')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to email account statements',
            ], 403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $payload = $this->buildCustomerStatementPayload($id, $start, $end);
        if ($payload === null) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $customer = $payload['customer'];
        $values = ArDocumentEmailComposer::statementPlaceholderValues($payload);
        $templateId = ArDocumentEmailComposer::resolveTemplateId(null, 'statement');
        $preview = ArDocumentEmailComposer::preview($templateId, 'statement', $values);

        return response()->json([
            'success' => true,
            'to_email' => trim((string) ($customer->email ?? '')),
            'templates' => ArDocumentEmailComposer::templatesList('statement'),
            'template_id' => $templateId,
            'subject' => $preview['subject'],
            'body' => $preview['body'],
        ]);
    }

    public function statementEmailPreview(Request $request, $id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-customer.send-statement')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to email account statements',
            ], 403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'template_id' => 'sometimes|nullable|integer|exists:ar_email_templates,id',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $payload = $this->buildCustomerStatementPayload($id, $start, $end);
        if ($payload === null) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $values = ArDocumentEmailComposer::statementPlaceholderValues($payload);
        $tid = isset($validated['template_id']) ? (int) $validated['template_id'] : null;
        $preview = ArDocumentEmailComposer::preview($tid, 'statement', $values);

        return response()->json([
            'success' => true,
            'subject' => $preview['subject'],
            'body' => $preview['body'],
        ]);
    }

    public function statementEmailSend(Request $request, $id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-customer.send-statement')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to email account statements',
            ], 403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'to_email' => 'required|email|max:255',
            'subject' => 'required|string|max:500',
            'body' => 'nullable|string|max:65535',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $payload = $this->buildCustomerStatementPayload($id, $start, $end);
        if ($payload === null) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $customer = $payload['customer'];
        $fileSafe = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $customer->name);
        $fileSafe = trim($fileSafe, '-') ?: 'customer';
        $attachName = sprintf(
            'account-statement-%s-%s-to-%s.pdf',
            $fileSafe,
            $payload['start_date'],
            $payload['end_date']
        );

        $pdf = Pdf::loadView('pdfs.upload-portal-ar-account-statement', $this->arAccountStatementViewData($payload));
        $tmp = tempnam(sys_get_temp_dir(), 'stmt-pdf-');
        if ($tmp === false) {
            return response()->json([
                'success' => false,
                'message' => 'Could not generate statement PDF',
            ], 500);
        }
        $pdfPath = $tmp.'.pdf';
        if (! @rename($tmp, $pdfPath)) {
            @unlink($tmp);

            return response()->json([
                'success' => false,
                'message' => 'Could not generate statement PDF',
            ], 500);
        }
        file_put_contents($pdfPath, $pdf->output());

        $html = $this->plainStatementEmailHtml($validated['body'] ?? '');
        $attachments = [['path' => $pdfPath, 'name' => $attachName]];

        try {
            $result = MailService::sendMail(
                $validated['to_email'],
                $validated['subject'],
                $html,
                [],
                null,
                $attachments
            );
        } finally {
            if (is_file($pdfPath)) {
                @unlink($pdfPath);
            }
        }

        if (! $this->brevoStatementMailSucceeded($result)) {
            $msg = is_array($result) ? ($result['message'] ?? json_encode($result)) : (string) $result;

            return response()->json([
                'success' => false,
                'message' => 'Email could not be sent'.($msg ? ': '.$msg : ''),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Statement emailed successfully',
        ]);
    }

    /**
     * @param  array{
     *     customer: UploadPortalCustomer,
     *     start_date: string,
     *     end_date: string,
     *     opening_balance: float,
     *     closing_balance: float,
     *     lines: array<int, mixed>
     * }  $payload
     * @return array<string, mixed>
     */
    private function arAccountStatementViewData(array $payload): array
    {
        $customer = $payload['customer'];
        $settings = Setting::whereIn('key', ['invoice-company-name', 'invoice-company-address'])->pluck('value', 'key')->toArray();
        $companyName = isset($settings['invoice-company-name']) ? $settings['invoice-company-name'] : '';
        $companyAddress = isset($settings['invoice-company-address']) ? $settings['invoice-company-address'] : '';
        $end = Carbon::parse($payload['end_date'])->endOfDay();

        return [
            'customerName' => $customer->documentDisplayName(),
            'customerToLines' => $this->buildStatementRecipientLines($customer),
            'startDate' => $payload['start_date'],
            'endDate' => $payload['end_date'],
            'openingBalance' => $payload['opening_balance'],
            'closingBalance' => $payload['closing_balance'],
            'lines' => $payload['lines'],
            'generatedAt' => now()->format('m/d/Y g:i A'),
            'companyLegalName' => $companyName,
            'companyAddressLines' => $companyAddress,
            'balanceOver90DaysPastDue' => $this->computeBalanceOver90DaysPastDue((int) $customer->id, $end),
        ];
    }

    private function plainStatementEmailHtml(string $body): string
    {
        $escaped = trim($body) === ''
            ? "\u{00A0}"
            : htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<div style="font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;color:#111">'
            .nl2br($escaped, false)
            .'</div>';
    }

    private function brevoStatementMailSucceeded(mixed $result): bool
    {
        return is_array($result) && isset($result['messageId']);
    }

    /**
     * @return array{
     *     customer: UploadPortalCustomer,
     *     start_date: string,
     *     end_date: string,
     *     opening_balance: float,
     *     closing_balance: float,
     *     lines: array<int, array{date: string, kind: string, transaction: string, amount: float, balance: float}>
     * }|null
     */
    private function buildCustomerStatementPayload(string|int $customerId, Carbon $start, Carbon $end): ?array
    {
        $customer = UploadPortalCustomer::query()->find($customerId);
        if (! $customer) {
            return null;
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $invoices = SalesInvoice::query()
            ->where('customer_id', $customerId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get(['id', 'invoice_date', 'invoice_number', 'amount']);

        $paidSums = $this->sumPaidPerInvoiceIds($invoices->pluck('id')->all());
        $creditBalance = CustomerPaymentController::computeCustomerCreditBalance((int) $customerId);
        $statementLines = $this->buildStatementLinesFromOutstandingInvoices($invoices, $paidSums, $creditBalance);

        return [
            'customer' => $customer,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => 0.0,
            'closing_balance' => $statementLines['closing_balance'],
            'credit_balance' => $statementLines['credit_balance'],
            'lines' => $statementLines['lines'],
        ];
    }

    /**
     * @return list<array{
     *     customer_id: int,
     *     customer_name: string,
     *     opening_balance: float,
     *     closing_balance: float,
     *     lines: array<int, array{date: string, kind: string, transaction: string, amount: float, balance: float}>
     * }>
     */
    private function buildAllCustomerStatementsPayload(Carbon $start, Carbon $end, bool $includeZero = false): array
    {
        $customers = UploadPortalCustomer::query()->orderBy('name')->get(['id', 'name']);
        if ($customers->isEmpty()) {
            return [];
        }

        $customerIds = $customers->pluck('id')->all();

        $invoicesByCustomer = SalesInvoice::query()
            ->whereIn('customer_id', $customerIds)
            ->where('status', '!=', 'cancelled')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get(['id', 'customer_id', 'invoice_date', 'invoice_number', 'amount'])
            ->groupBy('customer_id');

        $allInvoiceIds = $invoicesByCustomer->flatten(1)->pluck('id')->all();
        $paidSums = $this->sumPaidPerInvoiceIds($allInvoiceIds);

        $creditByCustomer = CustomerPayment::query()
            ->whereIn('customer_id', $customerIds)
            ->groupBy('customer_id')
            ->selectRaw('customer_id, SUM(unapplied_amount) as total')
            ->pluck('total', 'customer_id');

        $statements = [];
        foreach ($customers as $customer) {
            $invoices = $invoicesByCustomer->get($customer->id, collect());
            $creditBalance = round((float) ($creditByCustomer[$customer->id] ?? 0), 2);
            $statementLines = $this->buildStatementLinesFromOutstandingInvoices($invoices, $paidSums, $creditBalance);

            if (! $includeZero && $statementLines['lines'] === []) {
                continue;
            }

            $statements[] = [
                'customer_id' => (int) $customer->id,
                'customer_name' => $customer->name,
                'opening_balance' => 0.0,
                'closing_balance' => $statementLines['closing_balance'],
                'credit_balance' => $statementLines['credit_balance'],
                'lines' => $statementLines['lines'],
            ];
        }

        return $statements;
    }

    /**
     * @param  iterable<int, SalesInvoice>  $invoices
     * @param  \Illuminate\Support\Collection<int|string, mixed>  $paidSums
     * @return array{
     *     lines: array<int, array{date: string, kind: string, transaction: string, amount: float, balance: float}>,
     *     closing_balance: float,
     *     credit_balance: float
     * }
     */
    private function buildStatementLinesFromOutstandingInvoices(iterable $invoices, $paidSums, float $creditBalance): array
    {
        $creditBalance = round(max(0, $creditBalance), 2);
        $balance = 0.0;
        $lines = [];

        foreach ($invoices as $inv) {
            $paid = (float) ($paidSums[$inv->id] ?? 0);
            $due = round(max(0, (float) $inv->amount - $paid), 2);
            if ($due <= 0.009) {
                continue;
            }

            $balance = round($balance + $due, 2);
            $lines[] = [
                'date' => $inv->invoice_date->format('Y-m-d'),
                'kind' => 'invoice',
                'transaction' => sprintf('INV #%s', $inv->invoice_number),
                'amount' => $due,
                'balance' => $balance,
            ];
        }

        if ($creditBalance > 0.009) {
            $balance = round($balance - $creditBalance, 2);
            $lines[] = [
                'date' => '',
                'kind' => 'credit',
                'transaction' => 'Customer credit on account',
                'amount' => -$creditBalance,
                'balance' => $balance,
            ];
        }

        return [
            'lines' => $lines,
            'closing_balance' => $balance,
            'credit_balance' => $creditBalance,
        ];
    }

    /**
     * @param  array<int>  $invoiceIds
     * @return \Illuminate\Support\Collection<int|string, mixed>
     */
    private function sumPaidPerInvoiceIds(array $invoiceIds)
    {
        if ($invoiceIds === []) {
            return collect();
        }

        return CustomerPaymentItem::query()
            ->whereIn('sales_invoice_id', $invoiceIds)
            ->selectRaw('sales_invoice_id, SUM(amount_applied + discount) as s')
            ->groupBy('sales_invoice_id')
            ->pluck('s', 'sales_invoice_id');
    }

    /**
     * @return list<string>
     */
    private function buildStatementRecipientLines(UploadPortalCustomer $customer): array
    {
        $cityState = collect([$customer->city, $customer->state])->filter()->implode(', ');
        $zipPart = $customer->zip_code ? ' '.$customer->zip_code : '';
        $cityLine = trim($cityState.$zipPart);

        return array_values(array_filter([
            $customer->address_line_1,
            $customer->address_line_2,
            $customer->address_line_3,
            $cityLine !== '' ? $cityLine : null,
        ], static fn ($v) => $v !== null && $v !== ''));
    }

    private function computeBalanceOver90DaysPastDue(int $customerId, Carbon $asOf): float
    {
        $invoices = SalesInvoice::query()
            ->where('customer_id', $customerId)
            ->where('status', '!=', 'cancelled')
            ->with('paymentItems')
            ->get(['id', 'invoice_date', 'due_date', 'amount']);

        $total = 0.0;
        foreach ($invoices as $inv) {
            $applied = (float) $inv->paymentItems->sum(static function ($item): float {
                return (float) $item->amount_applied + (float) $item->discount;
            });
            $balance = round((float) $inv->amount - $applied, 2);
            if ($balance <= 0) {
                continue;
            }
            $anchor = $inv->due_date ?? $inv->invoice_date;
            if (! $anchor) {
                continue;
            }
            if ($anchor->isAfter($asOf)) {
                continue;
            }
            if ($anchor->copy()->addDays(90)->isAfter($asOf)) {
                continue;
            }
            $total += $balance;
        }

        return round($total, 2);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCustomer($request);
        try{
            $user = $this->getAuthenticatedUploadUser();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
            }

            if (!$this->ensurePermission($user, 'upload-portal-customer.add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to add customers',
                ], 403);
            }


            $validated['created_by'] = $user->id;
            $validated['updated_by'] = $user->id;

            $customer = UploadPortalCustomer::create($validated);
            $this->syncCustomerToQuickqore($customer);

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully',
                'customer' => $customer->fresh()->load(['createdBy', 'updatedBy']),
            ]);
        }catch(\Throwable $e){
            return response()->json([
                'success' => false,
                'message' => $e instanceof \InvalidArgumentException ? $e->getMessage() : 'Failed to create customer',
                'error' => $e instanceof \InvalidArgumentException ? $e->getMessage() : 'Failed to create customer',
            ], $e instanceof \InvalidArgumentException ? 422 : 500);
        }
    }

    public function update(Request $request, $id)
    {
        try{

            $user = $this->getAuthenticatedUploadUser();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
            }

            if (!$this->ensurePermission($user, 'upload-portal-customer.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to edit customers',
                ], 403);
            }

            $customer = UploadPortalCustomer::find($id);
            if (!$customer) {
                return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
            }

            $validated = $this->validateCustomer($request);
            $validated['updated_by'] = $user->id;

            $customer->fill($validated)->save();
            $this->syncCustomerToQuickqore($customer);

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'customer' => $customer->fresh()->load(['createdBy', 'updatedBy']),
            ]);
        }catch(\Throwable $e){
            return response()->json([
                'success' => false,
                'message' => $e instanceof \InvalidArgumentException ? $e->getMessage() : 'Failed to update customer',
                'error' => $e instanceof \InvalidArgumentException ? $e->getMessage() : 'Failed to update customer: '.$e->getMessage(),
            ], $e instanceof \InvalidArgumentException ? 422 : 500);
        }
    }

    public function destroy($id)
    {
        try{

            $user = $this->getAuthenticatedUploadUser();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
            }

            if (!$this->ensurePermission($user, 'upload-portal-customer.delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete customers',
                ], 403);
            }

            $customer = UploadPortalCustomer::find($id);
            $salesInvoices = SalesInvoice::where('customer_id', $id)->get();
            if ($salesInvoices->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer has sales invoices and cannot be deleted',
                ], 400);
            }
            if (!$customer) {
                return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
            }

            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully',
            ]);
        }catch(\Throwable $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer',
                'error' => 'Failed to delete customer: '.$e->getMessage(),
            ], 500);
        }
    }

    private function validateCustomer(Request $request): array
    {
        return $request->validate($this->customerValidationRules());
    }

    private function syncCustomerToQuickqore(UploadPortalCustomer $customer): void
    {
        $storeNo = $this->storeNoFromCustomerName($customer->name);
        if ($storeNo === null || $storeNo === '') {
            throw new \InvalidArgumentException('Unable to determine store number for Quickqore customer sync. Use a name like "Customer - 700".');
        }

        $payload = [
            'tdpus_id' => $customer->id,
            'qq_id' => $customer->qq_id ? (int) $customer->qq_id : null,
            'qq_company_id' => $customer->qq_company_id ? (int) $customer->qq_company_id : null,
            'store_no' => $storeNo,
            'name' => UploadPortalCustomer::stripInternalNameSuffix($customer->name),
            'email' => $customer->email,
            'mobile' => $customer->mobile,
            'fax' => $customer->fax,
            'primary_person_name' => $customer->primary_person_name,
            'email_notes' => $customer->email_notes,
            'address_line_1' => $customer->address_line_1,
            'address_line_2' => $customer->address_line_2,
            'address_line_3' => $customer->address_line_3,
            'city' => $customer->city,
            'state' => $customer->state,
            'country' => $customer->country,
            'zip_code' => $customer->zip_code,
            'bank_name' => $customer->bank_name,
            'account_number' => $customer->account_number,
            'routing_number' => $customer->routing_number,
            'credit_card_number' => $customer->credit_card_number,
            'card_expiry' => $customer->card_expiry,
            'name_on_card' => $customer->name_on_card,
            'financial_zip_code' => $customer->financial_zip_code,
            'cvv' => $customer->cvv,
            'card_type' => $customer->card_type,
            'invoice_due' => $customer->invoice_due,
            'invoice_condition' => $customer->invoice_condition,
        ];

        $service = new QuickqoreInvoiceService();
        try {
            if ($customer->qq_id) {
                try {
                    $response = $service->updateArCustomer($payload);
                } catch (\RuntimeException $e) {
                    if (! str_contains(strtolower($e->getMessage()), 'not found')) {
                        throw $e;
                    }
                    $response = $service->storeArCustomer($payload);
                }
            } else {
                $response = $service->storeArCustomer($payload);
            }
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }

        $dirty = false;
        $qqId = (int) ($response['qq_id'] ?? $response['id'] ?? 0);
        if ($qqId > 0 && ! $customer->qq_id) {
            $customer->qq_id = $qqId;
            $dirty = true;
        }

        $qqCompanyId = (int) ($response['qq_company_id'] ?? $response['company_id'] ?? 0);
        if ($qqCompanyId > 0 && ! $customer->qq_company_id) {
            $customer->qq_company_id = $qqCompanyId;
            $dirty = true;
        }

        if ($dirty) {
            $customer->save();
        }
    }

    private function storeNoFromCustomerName(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        if (preg_match('/\s-\s*(\d+)\s*$/', $name, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function customerValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'primary_person_name' => 'nullable|string|max:255',
            'email_notes' => 'nullable|string|max:65535',

            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'address_line_3' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:32',

            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'routing_number' => 'nullable|string|max:255',
            'credit_card_number' => 'nullable|string|max:255',
            'card_expiry' => 'nullable|string|max:16',
            'name_on_card' => 'nullable|string|max:255',
            'financial_zip_code' => 'nullable|string|max:32',
            'cvv' => 'nullable|numeric|min:0|max:9999',
            'card_type' => 'nullable|string|max:100',
            'invoice_due' => 'nullable|integer|min:0|max:366',
            'invoice_condition' => ['nullable', 'string', Rule::in(self::INVOICE_CONDITIONS)],
        ];
    }
}
