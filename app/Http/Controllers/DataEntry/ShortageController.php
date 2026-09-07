<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\OldDailySale;
use App\Models\DataEntry\Shortage;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ShortageController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'shortage.index');

        $collection = OldDailySale::query()
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
        $this->authorize('access', 'shortage.create');
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
        $this->authorize('access', 'shortage.create');

        $validated = $request->validate([
            'daily_sale_id' => 'required|exists:daily_sales,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $shortageValidation = $this->validateShortageAmountAgainstCashBag((int) $validated['daily_sale_id'], (float) $validated['amount']);
        if ($shortageValidation !== true) {
            return to_json([
                'saved' => false,
                'message' => $shortageValidation,
            ], 422);
        }

        try {
            Shortage::create($request->all());
        } catch (\Throwable $e) {
            return to_json([
                'saved' => false,
                'message' => 'Shortage creation failed',
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'shortage.show');
    
        $dailySale = OldDailySale::query()
            ->with('company')
            ->withSum('bankDeposits as bank_deposit_total', 'amount')
            ->withSum('shortages as shortage_total', 'amount')
            ->findOrFail($id);
    
        $dailySale->remaining_amount =
            $dailySale->cash_bag
            - (($dailySale->bank_deposit_total ?? 0) + ($dailySale->shortage_total ?? 0));
    
        $shortages = Shortage::query()
            ->with('createdBy', 'updatedBy')
            ->where('daily_sale_id', $id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    
        return to_json([
            'model' => [
                'daily_sale'   => $dailySale,
                'shortages'=> $shortages,
            ],
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'shortage.update');
        $shortage = Shortage::find($id);
        return to_json([
            'shortage' => $shortage,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'shortage.update');
        $validated = $request->validate([
            'shortage_id' => 'nullable|integer|exists:shortage,id',
            'daily_sale_id' => 'required|exists:daily_sales,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $shortageId = (int) ($validated['shortage_id'] ?? $id);
        $shortage = Shortage::find($shortageId);
        if (!$shortage) {
            return to_json([
                'saved' => false,
                'message' => 'Shortage not found.',
            ], 404);
        }

        if ((int) $shortage->daily_sale_id !== (int) $validated['daily_sale_id']) {
            return to_json([
                'saved' => false,
                'message' => 'Selected shortage does not belong to this daily sale.',
            ], 422);
        }

        $shortageValidation = $this->validateShortageAmountAgainstCashBag((int) $validated['daily_sale_id'], (float) $validated['amount'], $shortageId);
        if ($shortageValidation !== true) {
            return to_json([
                'saved' => false,
                'message' => $shortageValidation,
            ], 422);
        }

        try {
            $shortage->update($request->all());
        } catch (\Throwable $e) {
            return to_json([
                'saved' => false,
                'message' => 'Shortage update failed',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'shortage.delete');
        try {
            $shortage = Shortage::find($id);
            $shortage->delete();
        } catch (\Throwable $e) {
            return to_json([
                'deleted' => false,
                'message' => 'Shortage deletion failed',
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $this->authorize('access', 'shortage.index');

        $companyId = $request->session()->get('company');
        $collection = Shortage::query()
            ->with('dailySale.company')
            ->whereHas('dailySale', fn ($q) => $q->where('company_id', $companyId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Sales Date', 'Sales Cash Bag', 'Shortage Date', 'Shortage Amount'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:D1')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 2;
        foreach ($collection as $shortage) {
            $dailySale = $shortage->dailySale;
            $sheet->fromArray([
                $dailySale->date ? \Carbon\Carbon::parse($dailySale->date)->format('Y-m-d') : '',
                $dailySale->cash_bag ?? 0,
                $shortage->date ? \Carbon\Carbon::parse($shortage->date)->format('Y-m-d') : '',
                $shortage->amount ?? 0,
            ], null, 'A' . $row);
            $row++;
        }
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'shortages_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    private function validateShortageAmountAgainstCashBag(int $dailySaleId, float $amount, $shortageId = null)
    {
        $dailySale = OldDailySale::query()
        ->select('id', 'cash_bag')
        ->withSum('bankDeposits as bank_deposit_total', 'amount')
        ->withSum([
            'shortages as shortage_total' => function ($query) use ($shortageId) {
                if ($shortageId) {
                    $query->where('id', '!=', $shortageId);
                }
            }
        ], 'amount')
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
