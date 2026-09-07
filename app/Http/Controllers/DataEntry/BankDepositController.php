<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\BankDeposit;
use App\Models\DataEntry\DailySale;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class BankDepositController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'bank-deposit.index');

        $collection = DailySale::query()
        ->where('company_id', $request->session()->get('company'))
        ->with('company')
        ->withSum('bankDeposits as bank_deposit_total', 'amount')
        ->withSum('shortages as shortage_total', 'amount')
        ->withMax('bankDeposits as deposite_date', 'date')
        ->withMax('shortages as shortage_date', 'date')
        ->filter();
    
        $collection->each(function ($item) {
            $item->difference = $item->cash_bag
                - (($item->bank_deposit_total ?? 0) + ($item->shortage_total ?? 0));
        });

        return to_json([
            'collection' => $collection,
        ]);
    }
    public function create()
    {
        $this->authorize('access', 'bank-deposit.create');
        return to_json([
            'form' => [
                'daily_sale_id' => null,
                'date' => now()->toDateString(),
                'amount' => 0,
                'notes' => null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'bank-deposit.create');

        $validated = $request->validate([
            'daily_sale_id' => 'required|exists:daily_sales,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        $depositValidation = $this->validateDepositAmountAgainstCashBag((int) $validated['daily_sale_id'], (float) $validated['amount']);
        if ($depositValidation !== true) {
            return to_json([
                'saved' => false,
                'message' => $depositValidation,
            ], 422);
        }

        try {
            BankDeposit::create($request->all());
            return to_json([
                'saved' => true,
                'message' => 'Bank Deposite created successfully',
            ]);
        } catch (\Throwable $e) {
            return to_json([
                'saved' => false,
                'message' => 'Bank Deposite creation failed',
            ], 500);
        }
    }
    public function show($id)
    {
        $this->authorize('access', 'bank-deposit.show');
    
        $dailySale = DailySale::query()
            ->with('company')
            ->withSum('bankDeposits as bank_deposit_total', 'amount')
            ->withSum('shortages as shortage_total', 'amount')
            ->findOrFail($id);
    
        $dailySale->remaining_amount =
            $dailySale->cash_bag
            - (($dailySale->bank_deposit_total ?? 0) + ($dailySale->shortage_total ?? 0));
    
        $bankDeposits = BankDeposit::query()
            ->with('createdBy', 'updatedBy')
            ->where('daily_sale_id', $id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    
        return to_json([
            'model' => [
                'daily_sale'   => $dailySale,
                'bank_deposits'=> $bankDeposits,
            ],
        ]);
    }
    public function edit($id){
        $this->authorize('access', 'bank-deposit.update');
        $bankDeposit = BankDeposit::find($id);
        return to_json([
            'bankDeposit' => $bankDeposit,
        ]);
    }
    public function update(Request $request, $id){
        $this->authorize('access', 'bank-deposit.update');
        $validated = $request->validate([
            'deposit_id' => 'nullable|integer|exists:bank_deposits,id',
            'daily_sale_id' => 'required|exists:daily_sales,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $depositId = (int) ($validated['deposit_id'] ?? $id);
        $bankDeposit = BankDeposit::find($depositId);
        if (!$bankDeposit) {
            return to_json([
                'saved' => false,
                'message' => 'Bank deposit not found.',
            ], 404);
        }

        if ((int) $bankDeposit->daily_sale_id !== (int) $validated['daily_sale_id']) {
            return to_json([
                'saved' => false,
                'message' => 'Selected bank deposit does not belong to this daily sale.',
            ], 422);
        }

        $depositValidation = $this->validateDepositAmountAgainstCashBag((int) $validated['daily_sale_id'], (float) $validated['amount'], $depositId);
        if ($depositValidation !== true) {
            return to_json([
                'saved' => false,
                'message' => $depositValidation,
            ], 422);
        }
        try {
            $bankDeposit->update($request->all());
            return to_json([
                'saved' => true,
                'message' => 'Bank Deposite updated successfully',
            ]);
        } catch (\Throwable $e) {
            return to_json([
                'saved' => false,
                'message' => 'Bank Deposite update failed',
            ], 500);
        }
    }
    public function destroy($id){
        $this->authorize('access', 'bank-deposit.delete');
        try {
            $bankDeposit = BankDeposit::find($id);
            $bankDeposit->delete();
            return to_json([
                'deleted' => true,
                'message' => 'Bank Deposit deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return to_json([
                'deleted' => false,
                'message' => 'Bank Deposit deletion failed',
                ], 500);
        }
    }

    public function export(Request $request)
    {
        $this->authorize('access', 'bank-deposit.index');

        $companyId = $request->session()->get('company');
        $collection = BankDeposit::query()
            ->with('dailySale.company')
            ->whereHas('dailySale', fn ($q) => $q->where('company_id', $companyId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Sales Date', 'Sales Cash Bag', 'Deposit Date', 'Deposit Amount'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:D1')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 2;
        foreach ($collection as $deposit) {
            $dailySale = $deposit->dailySale;
            $sheet->fromArray([
                $dailySale->date ? \Carbon\Carbon::parse($dailySale->date)->format('Y-m-d') : '',
                $dailySale->cash_bag ?? 0,
                $deposit->date ? \Carbon\Carbon::parse($deposit->date)->format('Y-m-d') : '',
                $deposit->amount ?? 0,
            ], null, 'A' . $row);
            $row++;
        }
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'bank_deposits_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    private function validateDepositAmountAgainstCashBag(int $dailySaleId, float $amount, $depositId = null)
    {
        $dailySale = DailySale::query()
        ->select('id', 'cash_bag')
    
        ->withSum([
            'bankDeposits as bank_deposit_total' => function ($query) use ($depositId) {
                if ($depositId) {
                    $query->where('id', '!=', $depositId);
                }
            }
        ], 'amount')
    
        ->withSum('shortages as shortage_total', 'amount')
    
        ->find($dailySaleId);
        
        if (! $dailySale) {
            return 'Daily sale not found.';
        }
        
        $remainingAmount = (float) $dailySale->cash_bag
            - ((float) ($dailySale->bank_deposit_total ?? 0)
            + (float) ($dailySale->shortage_total ?? 0));
        
        if ($remainingAmount < 0) {
            return 'Remaining amount already negative.';
        }

        return true;
    }
}