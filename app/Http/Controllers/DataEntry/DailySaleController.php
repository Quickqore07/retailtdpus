<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\AR\PjPaymentController;
use App\Http\Controllers\Controller;
use App\Models\DataEntry\BankDeposit;
use App\Models\DataEntry\DailySale;
use App\Models\DataEntry\DailySaleOtherPayment;
use App\Models\DataEntry\Shortage;
use App\Models\Settings\Company;
use App\Models\Settings\Workgroup;
use App\Services\Quickqore\QuickqoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DailySaleController extends Controller
{
    private const EXPENSE_OPTIONS = [
        'Small Maintenance',
        'Office Expense',
        'Food',
        'Supplies',
        'MISC',
    ];

    public function index(Request $request)
    {

        $collection = DailySale::with('otherPayments', 'company')
            ->withCount('otherPayments')
            ->with('createdBy', 'updatedBy')
            ->where('company_id', $request->session()->get('company'))
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'daily-sales.create');

        return to_json([
            'form' => [
                'date' => now()->toDateString(),
                'net_sales' => 0,
                'beverage_tax' => 0,
                'food_tax' => 0,
                'total_sales' => 0,
                'cash_received' => 0,
                'partial_void' => 0,
                'total_cash' => 0,
                'tips' => 0,
                'mileage' => 0,
                'total_tips_mileage' => 0,
                'dd_tips' => 0,
                'e_tips' => 0,
                'e_tips_payroll' => 0,
                'total_e_and_dd_tips' => 0,
                'cash_payment' => 0,
                'other_payments_total' => 0,
                'total_cash_payment' => 0,
                'net_cash_due' => 0,
                'cash_bag' => 0,
                'short_over' => 0,
                'other_payments' => [
                    $this->emptyOtherPayment(),
                ],
            ],
            'expense_options' => self::EXPENSE_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'daily-sales.create');

        $validated = $this->validatePayload($request);
        $totals = $this->calculateTotals($validated);
        $otherPayments = $this->prepareOtherPayments($validated['other_payments'] ?? []);

        $companies = Company::get()->pluck('store_number', 'id');
        DB::beginTransaction();
        try {
            $dailySale = DailySale::create(array_merge(
                $this->extractBaseFields($validated),
                $totals,
                ['company_id' => $request->session()->get('company')]
            ));

            $this->insertOtherPayments($dailySale->id, $otherPayments);


            /* Quickqore API */
            $workGroup = Workgroup::where('id', $request->session()->get('workgroup'))->first();
            $data = [
                'entry_date' => $dailySale->date,
                'company_code' => $companies[$dailySale->company_id],
                'tm_total' => $dailySale->total_tips_mileage,
                'tips'=>$dailySale->tips,
                'mileage' => $dailySale->mileage,
                'dd_tips' => $dailySale->dd_tips,
                'e_tips' => $dailySale->e_tips,
                'e_tips_payroll' => $dailySale->e_tips_payroll,
                'other_payment' => $dailySale->other_payments_total,
                'short_over' => $dailySale->short_over,
                'total_cash_payment' => $dailySale->total_cash_payment,
                'sales_id' => $dailySale->id,
                'workgroup_name' => $workGroup->name,
            ];
            $quickqoreService = new QuickqoreService();
            $quickqoreService->handleDailySale($data);
            
            /* End Quickqore API */



            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $dailySale->id,
                'message' => 'Daily Sale created successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'daily-sales.show');

        $model = DailySale::with(['otherPayments', 'createdBy', 'updatedBy', 'bankDeposits', 'shortages'])
            ->findOrFail($id);

        return to_json([
            'model' => $model,
            'expense_options' => self::EXPENSE_OPTIONS,
        ]);
    }

    public function edit($id, Request $request)
    {
        $this->authorize('access', 'daily-sales.update');
        $currentCompany = $request->session()->get('company');

        $form = DailySale::with('otherPayments')
            ->where('company_id', $currentCompany)
            ->findOrFail($id);

        $otherPayments = $form->otherPayments->map(function ($item) {
            return [
                'id' => $item->id,
                'expense' => $item->expense,
                'amount' => $item->amount,
            ];
        })->values();

        if ($otherPayments->isEmpty()) {
            $otherPayments = collect([$this->emptyOtherPayment()]);
        }

        $form->setRelation('otherPayments', $otherPayments);

        return to_json([
            'form' => $form,
            'expense_options' => self::EXPENSE_OPTIONS,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'daily-sales.update');

        $validated = $this->validatePayload($request);
        $totals = $this->calculateTotals($validated);
        $otherPayments = $this->prepareOtherPayments($validated['other_payments'] ?? []);

        $companies = Company::get()->pluck('store_number', 'id');
        DB::beginTransaction();
        try {
            $dailySale = DailySale::findOrFail($id);
            $dailySale->update(array_merge(
                $this->extractBaseFields($validated),
                $totals,
                ['company_id' => $request->session()->get('company')]
            ));

            DailySaleOtherPayment::where('daily_sale_id', $dailySale->id)->delete();
            $this->insertOtherPayments($dailySale->id, $otherPayments);


            /* Quickqore API */
            $workGroup = Workgroup::where('id', $request->session()->get('workgroup'))->first();
            $data = [
                'entry_date' => $dailySale->date,
                'company_code' => $companies[$dailySale->company_id],
                'tm_total' => $dailySale->total_tips_mileage,
                'tips'=>$dailySale->tips,
                'mileage' => $dailySale->mileage,
                'dd_tips' => $dailySale->dd_tips,
                'e_tips' => $dailySale->e_tips,
                'e_tips_payroll' => $dailySale->e_tips_payroll,
                'other_payment' => $dailySale->other_payments_total,
                'short_over' => $dailySale->short_over,
                'total_cash_payment' => $dailySale->total_cash_payment,
                'sales_id' => $dailySale->id,
                'workgroup_name' => $workGroup->name,
            ];
            $quickqoreService = new QuickqoreService();
            $quickqoreService->handleDailySale($data);

            /* End Quickqore API */

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $dailySale->id,
                'message' => 'Daily Sale updated successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'daily-sales.delete');

        DB::beginTransaction();
        try {
            $dailySale = DailySale::findOrFail($id);
            $bankDeposits = BankDeposit::where('daily_sale_id', $dailySale->id)->get();
            $shortages = Shortage::where('daily_sale_id', $dailySale->id)->get();

            if ($bankDeposits->count() > 0 || $shortages->count() > 0) {
                return to_json([
                    'deleted' => false,
                    'message' => 'Daily Sale has bank deposits or shortages, cannot be deleted',
                ], 400);
            }
            DailySaleOtherPayment::where('daily_sale_id', $dailySale->id)->delete();
            $dailySale->delete();

            DB::commit();
            return to_json([
                'deleted' => true,
                'id' => $id,
                'message' => 'Daily Sale deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Daily Sale deletion failed',
            ], 500);
        }
    }

    /**
     * Upload file (same API and functionality as PJ payments upload).
     */
    public function upload(Request $request)
    {
        $this->authorize('access', 'daily-sales.create');
        $request->validate([
            'file' => 'required_without:files|file|mimes:csv,txt,xlsx|' . upload_max_file_size_rule(),
            'files' => 'required_without:file|nullable|array',
            'files.*' => 'required|file|mimes:csv,txt,xlsx|' . upload_max_file_size_rule(),
            'confirmed' => 'nullable|boolean',
        ]);
        return app(PjPaymentController::class)->processUpload($request);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'net_sales' => ['required', 'numeric', 'min:0'],
            'beverage_tax' => ['required', 'numeric', 'min:0'],
            'food_tax' => ['required', 'numeric', 'min:0'],
            'cash_received' => ['required', 'numeric', 'min:0'],
            'partial_void' => ['required', 'numeric', 'min:0'],
            'tips' => ['required', 'numeric', 'min:0'],
            'mileage' => ['required', 'numeric', 'min:0'],
            'dd_tips' => ['required', 'numeric', 'min:0'],
            'e_tips' => ['required', 'numeric', 'min:0'],
            'e_tips_payroll' => ['required', 'numeric', 'min:0'],
            'cash_bag' => ['required', 'numeric', 'min:0'],
            'other_payments' => ['nullable', 'array'],
            'other_payments.*.expense' => ['required_with:other_payments.*.amount', 'nullable', Rule::in(self::EXPENSE_OPTIONS)],
            'other_payments.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function extractBaseFields(array $validated): array
    {
        return [
            'date' => $validated['date'],
            'net_sales' => (float) $validated['net_sales'],
            'beverage_tax' => (float) $validated['beverage_tax'],
            'food_tax' => (float) $validated['food_tax'],
            'cash_received' => (float) $validated['cash_received'],
            'partial_void' => (float) $validated['partial_void'],
            'tips' => (float) $validated['tips'],
            'mileage' => (float) $validated['mileage'],
            'dd_tips' => (float) $validated['dd_tips'],
            'e_tips' => (float) $validated['e_tips'],
            'e_tips_payroll' => (float) $validated['e_tips_payroll'],
            'cash_bag' => (float) $validated['cash_bag'],
        ];
    }

    private function calculateTotals(array $validated): array
    {
        $netSales = (float) $validated['net_sales'];
        $beverageTax = (float) $validated['beverage_tax'];
        $foodTax = (float) $validated['food_tax'];
        $cashReceived = (float) $validated['cash_received'];
        $partialVoid = (float) $validated['partial_void'];
        $tips = (float) $validated['tips'];
        $mileage = (float) $validated['mileage'];
        $ddTips = (float) $validated['dd_tips'];
        $eTips = (float) $validated['e_tips'];
        $eTipsPayroll = (float) $validated['e_tips_payroll'];
        $cashBag = (float) $validated['cash_bag'];
        $otherPaymentsTotal = round(collect($validated['other_payments'] ?? [])->sum(function ($row) {
            return (float) ($row['amount'] ?? 0);
        }), 2);

        $totalSales = round($netSales + $beverageTax + $foodTax, 2);
        $totalCash = round($cashReceived + $partialVoid, 2);
        $totalTipsMileage = round($tips + $mileage, 2);
        $totalEAndDdTips = round($ddTips + $eTips + $eTipsPayroll, 2);
        $cashPayment = round($totalTipsMileage - $totalEAndDdTips, 2);
        $totalCashPayment = round($cashPayment + $otherPaymentsTotal, 2);
        $netCashDue = round($totalCash - $totalCashPayment, 2);
        $shortOver = round($cashBag - $netCashDue, 2);

        return [
            'total_sales' => $totalSales,
            'total_cash' => $totalCash,
            'total_tips_mileage' => $totalTipsMileage,
            'total_e_and_dd_tips' => $totalEAndDdTips,
            'cash_payment' => $cashPayment,
            'other_payments_total' => $otherPaymentsTotal,
            'total_cash_payment' => $totalCashPayment,
            'net_cash_due' => $netCashDue,
            'short_over' => $shortOver,
        ];
    }

    private function prepareOtherPayments(array $rows): array
    {
        return collect($rows)
            ->map(function ($row) {
                return [
                    'expense' => $row['expense'] ?? null,
                    'amount' => (float) ($row['amount'] ?? 0),
                ];
            })
            ->filter(function ($row) {
                return !empty($row['expense']) || $row['amount'] > 0;
            })
            ->values()
            ->all();
    }

    private function insertOtherPayments(int $dailySaleId, array $otherPayments): void
    {
        if (empty($otherPayments)) {
            return;
        }

        $now = now();
        $insertData = collect($otherPayments)->map(function ($row) use ($dailySaleId, $now) {
            return [
                'daily_sale_id' => $dailySaleId,
                'expense' => $row['expense'],
                'amount' => $row['amount'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        DailySaleOtherPayment::insert($insertData);
    }

    private function emptyOtherPayment(): array
    {
        return [
            'expense' => null,
            'amount' => 0,
        ];
    }

    public function export(Request $request)
    {
        $this->authorize('access', 'daily-sales.index');

        $query = DailySale::with('company')
            ->where('company_id', $request->session()->get('company'));

        $collection = $query->export($request->all());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = [
            'Date',
            'Company',
            'Net Sales',
            'Beverage Tax',
            'Food Tax',
            'Total Sales',
            'Cash Received',
            'Partial Void',
            'Total Cash',
            'Tips',
            'Mileage',
            'Total Tips Mileage',
            'DD Tips',
            'E Tips',
            'E Tips Payroll',
            'Total E And DD Tips',
            'Cash Payment',
            'Other Payments Total',
            'Total Cash Payment',
            'Net Cash Due',
            'Cash Bag',
            'Short Over',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $lastCol = 'V';
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $lastCol . '1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 2;
        foreach ($collection as $item) {
            $sheet->fromArray([
                $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '',
                $item->company->name ?? '',
                $item->net_sales ?? 0,
                $item->beverage_tax ?? 0,
                $item->food_tax ?? 0,
                $item->total_sales ?? 0,
                $item->cash_received ?? 0,
                $item->partial_void ?? 0,
                $item->total_cash ?? 0,
                $item->tips ?? 0,
                $item->mileage ?? 0,
                $item->total_tips_mileage ?? 0,
                $item->dd_tips ?? 0,
                $item->e_tips ?? 0,
                $item->e_tips_payroll ?? 0,
                $item->total_e_and_dd_tips ?? 0,
                $item->cash_payment ?? 0,
                $item->other_payments_total ?? 0,
                $item->total_cash_payment ?? 0,
                $item->net_cash_due ?? 0,
                $item->cash_bag ?? 0,
                $item->short_over ?? 0,
            ], null, 'A' . $row);
            $row++;
        }
        foreach (range('A', 'V') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'daily_sales_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
