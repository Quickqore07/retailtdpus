<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\DailySale;
use App\Models\DataEntry\DailySaleCashReconciliation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DailySaleController extends Controller
{
    private const CASH_PAYMENT_OPTIONS = [
        'Lottery',
        'Skill MAchine',
        'Employee Pay',
        'Repairs',
        'Store Supplies',
        'Cleaning',
        'Snow Removal',
        'Office Supplies',
        'Grocessary',
        'Other Expense',
    ];

    public function index(Request $request)
    {
        $this->authorize('access', 'daily-sales.index');

        $collection = DailySale::with(['company', 'createdBy', 'updatedBy'])
            ->withCount(['cashReconciliations'])
            ->where('company_id', $request->session()->get('company'))
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('access', 'daily-sales.create');

        $date = $request->input('date', now()->toDateString());
        $form = $this->emptyForm($date);
        $form['opening_balance'] = $this->calculateOpeningBalance(
            $request->session()->get('company'),
            $date
        );

        return to_json([
            'form' => $form,
            'cash_payment_options' => self::CASH_PAYMENT_OPTIONS,
        ]);
    }

    public function openingBalance(Request $request)
    {
        $this->authorize('access', 'daily-sales.index');

        $validated = $request->validate([
            'date' => 'required|date',
            'ignore_id' => 'nullable|integer',
        ]);

        return to_json([
            'opening_balance' => $this->calculateOpeningBalance(
                $request->session()->get('company'),
                $validated['date'],
                $validated['ignore_id'] ?? null
            ),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'daily-sales.create');

        $validated = $this->validatePayload($request);
        $totals = $this->calculateTotals($validated);
        $cashReconciliations = $this->prepareCashReconciliations($validated['cash_reconciliations'] ?? []);

        DB::beginTransaction();
        try {
            $dailySale = DailySale::create(array_merge(
                $this->extractBaseFields($validated),
                $totals,
                ['company_id' => $request->session()->get('company')]
            ));

            $this->insertCashReconciliations($dailySale->id, $cashReconciliations);

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

        $model = DailySale::with([
            'cashReconciliations',
            'company',
            'createdBy',
            'updatedBy',
        ])->findOrFail($id);

        $model->opening_balance = $this->calculateOpeningBalance(
            $model->company_id,
            (string) $model->date,
            $model->id
        );
        $model->closing_balance = round(
            (float) $model->opening_balance
            - (float) $model->bank_deposits
            + (float) $model->cash_due,
            2
        );

        return to_json([
            'model' => $model,
        ]);
    }

    public function edit($id, Request $request)
    {
        $this->authorize('access', 'daily-sales.update');

        $form = DailySale::with(['cashReconciliations'])
            ->where('company_id', $request->session()->get('company'))
            ->findOrFail($id);

        $cashReconciliations = $form->cashReconciliations->map(function ($item) {
            return [
                'id' => $item->id,
                'cash_payment' => $item->cash_payment,
                'detail' => $item->detail,
                'amount' => $item->amount,
                'is_default' => false,
            ];
        })->values()->all();

        $form->cash_reconciliations = $this->padCashReconciliations($cashReconciliations);
        $form->opening_balance = $this->calculateOpeningBalance(
            $form->company_id,
            (string) $form->date,
            $form->id
        );

        return to_json([
            'form' => $form,
            'cash_payment_options' => self::CASH_PAYMENT_OPTIONS,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'daily-sales.update');

        $validated = $this->validatePayload($request, $id);
        $totals = $this->calculateTotals($validated);
        $cashReconciliations = $this->prepareCashReconciliations($validated['cash_reconciliations'] ?? []);

        DB::beginTransaction();
        try {
            $dailySale = DailySale::where('company_id', $request->session()->get('company'))
                ->findOrFail($id);

            $dailySale->update(array_merge(
                $this->extractBaseFields($validated),
                $totals,
                ['company_id' => $request->session()->get('company')]
            ));

            DailySaleCashReconciliation::where('daily_sales_id', $dailySale->id)->delete();
            $this->insertCashReconciliations($dailySale->id, $cashReconciliations);

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

        $dailySale = DailySale::findOrFail($id);
        $dailySale->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Daily Sale deleted successfully',
        ]);
    }

    private function emptyForm(string $date): array
    {
        return [
            'date' => $date,
            'sales' => 0,
            'tax' => 0,
            'other' => 0,
            'total' => 0,
            'round_off' => 0,
            'cash' => 0,
            'credit_card' => 0,
            'account' => 0,
            'check' => 0,
            'coupon' => 0,
            'other_payment' => 0,
            'total_payment' => 0,
            'cash_due' => 0,
            'bank_deposits' => 0,
            'opening_balance' => 0,
            'cash_reconciliations' => $this->padCashReconciliations([]),
        ];
    }

    private function padCashReconciliations(array $rows, int $min = 5): array
    {
        while (count($rows) < $min) {
            $rows[] = $this->emptyCashReconciliation();
        }

        return array_values($rows);
    }

    private function emptyCashReconciliation(): array
    {
        return [
            'cash_payment' => null,
            'detail' => null,
            'amount' => 0,
            'is_default' => false,
        ];
    }

    private function calculateOpeningBalance($companyId, $date, $ignoreId = null): float
    {
        if (! $companyId || ! $date) {
            return 0;
        }

        $query = DailySale::where('company_id', $companyId)
            ->whereDate('date', '<', $date);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $totals = $query->selectRaw(
            'COALESCE(SUM(cash), 0) as total_cash,
             COALESCE(SUM(total_payment), 0) as total_payment,
             COALESCE(SUM(bank_deposits), 0) as total_deposits'
        )->first();

        return round(
            (float) ($totals->total_cash ?? 0)
            - (float) ($totals->total_payment ?? 0)
            - (float) ($totals->total_deposits ?? 0),
            2
        );
    }

    private function validatePayload(Request $request, $ignoreId = null): array
    {
        $companyId = $request->session()->get('company');

        return $request->validate([
            'date' => [
                'required',
                'date',
                Rule::unique('daily_sales', 'date')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($ignoreId),
            ],
            'sales' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'other' => 'nullable|numeric',
            'round_off' => 'nullable|numeric',
            'cash' => 'nullable|numeric',
            'credit_card' => 'nullable|numeric',
            'account' => 'nullable|numeric',
            'check' => 'nullable|numeric',
            'coupon' => 'nullable|numeric',
            'other_payment' => 'nullable|numeric',
            'bank_deposits' => 'nullable|numeric',
            'cash_reconciliations' => 'nullable|array',
            'cash_reconciliations.*.cash_payment' => 'nullable|string|max:255',
            'cash_reconciliations.*.detail' => 'nullable|string|max:255',
            'cash_reconciliations.*.amount' => 'nullable|numeric',
            'cash_reconciliations.*.is_default' => 'nullable|boolean',
        ]);
    }

    private function extractBaseFields(array $validated): array
    {
        return [
            'date' => $validated['date'],
            'sales' => $validated['sales'] ?? 0,
            'tax' => $validated['tax'] ?? 0,
            'other' => $validated['other'] ?? 0,
            'round_off' => $validated['round_off'] ?? 0,
            'cash' => $validated['cash'] ?? 0,
            'credit_card' => $validated['credit_card'] ?? 0,
            'account' => $validated['account'] ?? 0,
            'check' => $validated['check'] ?? 0,
            'coupon' => $validated['coupon'] ?? 0,
            'other_payment' => $validated['other_payment'] ?? 0,
            'bank_deposits' => $validated['bank_deposits'] ?? 0,
        ];
    }

    private function calculateTotals(array $validated): array
    {
        $sales = (float) ($validated['sales'] ?? 0);
        $tax = (float) ($validated['tax'] ?? 0);
        $other = (float) ($validated['other'] ?? 0);
        $roundOff = (float) ($validated['round_off'] ?? 0);
        $cash = (float) ($validated['cash'] ?? 0);
        $total = round($sales + $tax + $other + $roundOff, 2);
        $totalPayment = round(
            collect($validated['cash_reconciliations'] ?? [])
                ->sum(fn ($row) => (float) ($row['amount'] ?? 0)),
            2
        );

        return [
            'total' => $total,
            'total_payment' => $totalPayment,
            'cash_due' => round($cash - $totalPayment, 2),
        ];
    }

    private function prepareCashReconciliations(array $rows): array
    {
        return collect($rows)
            ->map(function ($row) {
                return [
                    'cash_payment' => $row['cash_payment'] ?? null,
                    'detail' => $row['detail'] ?? null,
                    'amount' => (float) ($row['amount'] ?? 0),
                    'is_default' => false,
                ];
            })
            ->filter(function ($row) {
                return filled($row['cash_payment']) || filled($row['detail']) || $row['amount'] != 0;
            })
            ->values()
            ->all();
    }

    private function insertCashReconciliations(int $dailySaleId, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $now = now();
        DailySaleCashReconciliation::insert(
            collect($rows)->map(function ($row) use ($dailySaleId, $now) {
                return [
                    'daily_sales_id' => $dailySaleId,
                    'cash_payment' => $row['cash_payment'],
                    'detail' => $row['detail'],
                    'amount' => $row['amount'],
                    'is_default' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all()
        );
    }
}
