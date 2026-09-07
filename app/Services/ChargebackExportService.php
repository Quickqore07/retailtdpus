<?php

namespace App\Services;

use App\Models\ChargeBack\Chargeback;
use App\Models\ChargeBack\ChargebackEntryMode;
use App\Models\ChargeBack\ChargebackReasonCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChargebackExportService
{
    public const CHARGEBACK_TABS = [
        'chargebacks',
        'sales-receipts',
        'expired',
        'reimbursed',
    ];

    public function exportChargebacks(Request $request): BinaryFileResponse
    {
        $tab = $request->input('tab', 'chargebacks');

        if (!in_array($tab, self::CHARGEBACK_TABS, true)) {
            abort(422, 'Invalid export tab.');
        }

        $query = $this->buildChargebackQuery($request);

        if ($tab === 'chargebacks') {
            $items = $query->export($request->all());
        } else {
            $items = $this->applyTabScope($query, $tab)
                ->orderBy(
                    $request->input('sort_column', 'chargebacks.processor_due_date'),
                    $request->input('sort_direction', 'desc')
                )
                ->get();
        }

        if ($items->isEmpty()) {
            abort(422, 'No data to export.');
        }

        $reasonLabels = ChargebackReasonCode::labelsMap();

        return $this->downloadSpreadsheet(
            $this->chargebackHeaders($tab),
            $this->chargebackRows($tab, $items, $reasonLabels),
            $this->chargebackFilename($tab)
        );
    }

    public function exportReasonCodes(Request $request): BinaryFileResponse
    {
        $items = ChargebackReasonCode::query()->export($request->all());

        if ($items->isEmpty()) {
            abort(422, 'No data to export.');
        }

        $headers = ['Code', 'Description', 'Created At', 'Updated At'];
        $rows = $items->map(fn ($item) => [
            $item->code ?? '',
            $item->description ?? '',
            $this->formatDate($item->created_at),
            $this->formatDate($item->updated_at),
        ])->all();

        return $this->downloadSpreadsheet(
            $headers,
            $rows,
            'chargeback_reason_codes_export_' . date('Y-m-d_His') . '.xlsx'
        );
    }

    public function exportEntryModes(Request $request): BinaryFileResponse
    {
        $items = ChargebackEntryMode::query()->export($request->all());

        if ($items->isEmpty()) {
            abort(422, 'No data to export.');
        }

        $headers = ['Name', 'Created At', 'Updated At'];
        $rows = $items->map(fn ($item) => [
            $item->name ?? '',
            $this->formatDate($item->created_at),
            $this->formatDate($item->updated_at),
        ])->all();

        return $this->downloadSpreadsheet(
            $headers,
            $rows,
            'chargeback_entry_modes_export_' . date('Y-m-d_His') . '.xlsx'
        );
    }

    public function buildChargebackQuery(Request $request): Builder
    {
        $companyIds = $this->resolveCompanyIdsForUser(
            $request->input('user_id') ? (int) $request->input('user_id') : null
        );

        return Chargeback::join('company', 'chargebacks.company_id', '=', 'company.id')
            ->authorizedCompanies('company_id', false)
            ->when($companyIds !== null, function ($query) use ($companyIds) {
                if (empty($companyIds)) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->whereIn('chargebacks.company_id', $companyIds);
            })
            ->select('chargebacks.*')
            ->with(
                'company',
                'salesReceiptUploadedBy:id,name',
                'submittedBy:id,name',
                'markedAsReceivedBy:id,name',
            );
    }

    protected function applyTabScope(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'sales-receipts' => $query
                ->whereNotNull('chargebacks.sales_receipt_document_id')
                ->where('chargebacks.submitted', false),
            'expired' => $query
                ->where('chargebacks.submitted', false)
                ->where('chargebacks.marked_as_received', false)
                ->whereNull('chargebacks.sales_receipt_document_id')
                ->whereNotNull('chargebacks.processor_due_date')
                ->whereDate('chargebacks.processor_due_date', '<', Carbon::today()),
            'reimbursed' => $query->where('chargebacks.submitted', true),
            default => $query,
        };
    }

    protected function resolveCompanyIdsForUser(?int $userId): ?array
    {
        if (!$userId) {
            return null;
        }

        $user = User::find($userId);

        if (!$user) {
            return [];
        }

        return $user->getCompaniesArrayAttribute();
    }

    protected function chargebackHeaders(string $tab): array
    {
        return match ($tab) {
            'sales-receipts' => [
                'Case #',
                'Reference #',
                'Store',
                'Amount',
                'Receipt Uploaded',
                'Receipt Uploaded By',
                'Due Date',
                'Status',
                'Status Detail',
            ],
            'expired' => [
                'Case #',
                'Reference #',
                'Store',
                'Amount',
                'Due Date',
                'Status',
            ],
            'reimbursed' => [
                'Case #',
                'Reference #',
                'Store',
                'Amount',
                'Submitted By',
                'Credited By',
                'Submitted At',
                'Credited At',
                'Credited Date',
                'Due Date',
                'Status',
            ],
            default => [
                'Case #',
                'Reference #',
                'Store',
                'Reason Code',
                'Reason',
                'Amount',
                'Card Network',
                'Card Last Four',
                'Received',
                'Due',
                'Receipt Uploaded',
                'Receipt Status',
                'Status',
                'Status Detail',
            ],
        };
    }

    protected function chargebackRows(string $tab, Collection $items, array $reasonLabels): array
    {
        return $items->map(function (Chargeback $item) use ($tab, $reasonLabels) {
            $status = $item->display_status;

            return match ($tab) {
                'sales-receipts' => [
                    $item->case_number ?? '',
                    $item->reference_number ?? '',
                    data_get($item, 'company.name', ''),
                    (float) ($item->amount ?? 0),
                    $this->formatDate($item->upload_sales_receipt_date),
                    data_get($item, 'salesReceiptUploadedBy.name', ''),
                    $this->formatDate($item->processor_due_date),
                    data_get($status, 'label', ''),
                    data_get($status, 'sublabel', ''),
                ],
                'expired' => [
                    $item->case_number ?? '',
                    $item->reference_number ?? '',
                    data_get($item, 'company.name', ''),
                    (float) ($item->amount ?? 0),
                    $this->formatDate($item->processor_due_date),
                    data_get($status, 'label', 'Expired'),
                ],
                'reimbursed' => [
                    $item->case_number ?? '',
                    $item->reference_number ?? '',
                    data_get($item, 'company.name', ''),
                    (float) ($item->amount ?? 0),
                    data_get($item, 'submittedBy.name', ''),
                    data_get($item, 'markedAsReceivedBy.name', ''),
                    $this->formatDate($item->submitted_at),
                    $this->formatDate($item->marked_as_received_at),
                    $this->formatDate($item->credited_date),
                    $this->formatDate($item->processor_due_date),
                    data_get($status, 'label', ''),
                ],
                default => [
                    $item->case_number ?? '',
                    $item->reference_number ?? '',
                    data_get($item, 'company.name', ''),
                    $item->reason_code ?? '',
                    $reasonLabels[$item->reason_code] ?? ($item->reason_code ?? ''),
                    (float) ($item->amount ?? 0),
                    $item->card_network ?? '',
                    $item->card_last_four ?? '',
                    $this->formatDate($item->chargeback_received_date),
                    $this->formatDate($item->processor_due_date),
                    $this->formatDate($item->upload_sales_receipt_date),
                    $this->receiptStatusLabel($item),
                    data_get($status, 'label', ''),
                    data_get($status, 'sublabel', ''),
                ],
            };
        })->all();
    }

    protected function receiptStatusLabel(Chargeback $item): string
    {
        if ($item->upload_sales_receipt_date && $item->processor_due_date) {
            if (Carbon::parse($item->upload_sales_receipt_date)->gt(Carbon::parse($item->processor_due_date))) {
                return 'after due date';
            }

            return 'Uploaded on time';
        }

        return '';
    }

    protected function formatDate($value): string
    {
        if (!$value) {
            return '';
        }

        return Carbon::parse($value)->format('M j, Y');
    }

    protected function chargebackFilename(string $tab): string
    {
        $prefix = match ($tab) {
            'sales-receipts' => 'chargeback_sales_receipts_export',
            'expired' => 'chargeback_expired_export',
            'reimbursed' => 'chargeback_reimbursed_export',
            default => 'chargebacks_export',
        };

        return $prefix . '_' . date('Y-m-d_His') . '.xlsx';
    }

    protected function downloadSpreadsheet(array $headers, array $rows, string $filename): BinaryFileResponse
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));

        $sheet->fromArray($headers, null, 'A1');
        $headerRange = 'A1:' . $lastCol . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 2;
        foreach ($rows as $values) {
            $sheet->fromArray($values, null, 'A' . $row);
            $row++;
        }

        for ($i = 1; $i <= count($headers); $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
