<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\OtherDailySale;
use App\Models\DataEntry\OtherDailySaleBankDeposit;
use App\Models\DataEntry\OtherDailySaleCashReconciliation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OtherDailySaleController extends Controller
{
    private const DEFAULT_CASH_RECONCILIATIONS = [
        ['cash_payment' => 'Lottery', 'detail' => 'PA Lottery'],
        ['cash_payment' => 'Skill MAchine', 'detail' => 'Skill Machine Pay'],
        ['cash_payment' => 'Employee Pay', 'detail' => 'Mike'],
        ['cash_payment' => 'Employee Pay', 'detail' => 'John'],
        ['cash_payment' => 'Employee Pay', 'detail' => 'Patel'],
        ['cash_payment' => 'Repairs', 'detail' => null],
        ['cash_payment' => 'Store Supplies', 'detail' => null],
        ['cash_payment' => 'Cleaning', 'detail' => null],
        ['cash_payment' => 'Snow Removal', 'detail' => null],
        ['cash_payment' => 'Office Supplies', 'detail' => null],
        ['cash_payment' => 'Grocessary', 'detail' => null],
        ['cash_payment' => 'Other Expense', 'detail' => null],
    ];

    public function index(Request $request)
    {
        $this->authorize('access', 'other-daily-sales.index');

        $collection = OtherDailySale::with(['company', 'createdBy', 'updatedBy'])
            ->withCount(['cashReconciliations', 'bankDeposits'])
            ->withSum('bankDeposits as total_deposits', 'amount')
            ->where('company_id', $request->session()->get('company'))
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'other-daily-sales.create');

        return to_json([
            'form' => $this->emptyForm(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'other-daily-sales.create');

        $validated = $this->validatePayload($request);
        $totals = $this->calculateTotals($validated);
        $cashReconciliations = $this->prepareCashReconciliations($validated['cash_reconciliations'] ?? []);
        $bankDeposits = $this->prepareBankDeposits($validated['bank_deposits'] ?? []);

        DB::beginTransaction();
        try {
            $dailySale = OtherDailySale::create(array_merge(
                $this->extractBaseFields($validated),
                $totals,
                ['company_id' => $request->session()->get('company')]
            ));

            $this->insertCashReconciliations($dailySale->id, $cashReconciliations);
            $this->insertBankDeposits($dailySale->id, $bankDeposits);

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
        $this->authorize('access', 'other-daily-sales.show');

        $model = OtherDailySale::with([
            'cashReconciliations',
            'bankDeposits',
            'company',
            'createdBy',
            'updatedBy',
        ])->findOrFail($id);

        return to_json([
            'model' => $model,
        ]);
    }

    public function edit($id, Request $request)
    {
        $this->authorize('access', 'other-daily-sales.update');

        $form = OtherDailySale::with(['cashReconciliations', 'bankDeposits'])
            ->where('company_id', $request->session()->get('company'))
            ->findOrFail($id);

        $cashReconciliations = $form->cashReconciliations->map(function ($item) {
            return [
                'id' => $item->id,
                'cash_payment' => $item->cash_payment,
                'detail' => $item->detail,
                'amount' => $item->amount,
                'is_default' => (bool) $item->is_default,
            ];
        })->values();

        $cashReconciliations = collect($this->mergeDefaultCashReconciliations($cashReconciliations->all()));

        $bankDeposits = $form->bankDeposits->map(function ($item) {
            return [
                'id' => $item->id,
                'bank_name' => $item->bank_name,
                'amount' => $item->amount,
            ];
        })->values();

        if ($bankDeposits->isEmpty()) {
            $bankDeposits = collect([$this->emptyBankDeposit()]);
        }

        $form->cash_reconciliations = $cashReconciliations;
        $form->bank_deposits = $bankDeposits;

        return to_json([
            'form' => $form,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'other-daily-sales.update');

        $validated = $this->validatePayload($request, $id);
        $totals = $this->calculateTotals($validated);
        $cashReconciliations = $this->prepareCashReconciliations($validated['cash_reconciliations'] ?? []);
        $bankDeposits = $this->prepareBankDeposits($validated['bank_deposits'] ?? []);

        DB::beginTransaction();
        try {
            $dailySale = OtherDailySale::where('company_id', $request->session()->get('company'))
                ->findOrFail($id);

            $dailySale->update(array_merge(
                $this->extractBaseFields($validated),
                $totals,
                ['company_id' => $request->session()->get('company')]
            ));

            OtherDailySaleCashReconciliation::where('other_daily_sale_id', $dailySale->id)->delete();
            OtherDailySaleBankDeposit::where('other_daily_sale_id', $dailySale->id)->delete();
            $this->insertCashReconciliations($dailySale->id, $cashReconciliations);
            $this->insertBankDeposits($dailySale->id, $bankDeposits);

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
        $this->authorize('access', 'other-daily-sales.delete');

        $dailySale = OtherDailySale::findOrFail($id);
        $dailySale->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Daily Sale deleted successfully',
        ]);
    }

    private function emptyForm(): array
    {
        return [
            'date' => now()->toDateString(),
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
            'cash_reconciliations' => $this->defaultCashReconciliations(),
            'bank_deposits' => [
                $this->emptyBankDeposit(),
            ],
        ];
    }

    private function defaultCashReconciliations(): array
    {
        return collect(self::DEFAULT_CASH_RECONCILIATIONS)
            ->map(fn ($row) => [
                'cash_payment' => $row['cash_payment'],
                'detail' => $row['detail'],
                'amount' => 0,
                'is_default' => true,
            ])
            ->all();
    }

    private function mergeDefaultCashReconciliations(array $existing): array
    {
        $defaults = $this->defaultCashReconciliations();
        $matched = [];
        $custom = [];

        foreach ($existing as $row) {
            $isDefault = (bool) ($row['is_default'] ?? false);
            if (!$isDefault) {
                $custom[] = array_merge($row, ['is_default' => false]);
                continue;
            }

            $key = ($row['cash_payment'] ?? '') . '|' . ($row['detail'] ?? '');
            $matched[$key] = array_merge($row, ['is_default' => true]);
        }

        $mergedDefaults = collect($defaults)->map(function ($default) use ($matched) {
            $key = ($default['cash_payment'] ?? '') . '|' . ($default['detail'] ?? '');
            if (isset($matched[$key])) {
                return array_merge($default, [
                    'id' => $matched[$key]['id'] ?? null,
                    'amount' => $matched[$key]['amount'] ?? 0,
                ]);
            }

            return $default;
        })->all();

        return array_values(array_merge($mergedDefaults, $custom));
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

    private function emptyBankDeposit(): array
    {
        return [
            'bank_name' => null,
            'amount' => 0,
        ];
    }

    private function validatePayload(Request $request, $ignoreId = null): array
    {
        $companyId = $request->session()->get('company');

        return $request->validate([
            'date' => [
                'required',
                'date',
                Rule::unique('other_daily_sales', 'date')
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
            'cash_reconciliations' => 'nullable|array',
            'cash_reconciliations.*.cash_payment' => 'nullable|string|max:255',
            'cash_reconciliations.*.detail' => 'nullable|string|max:255',
            'cash_reconciliations.*.amount' => 'nullable|numeric',
            'cash_reconciliations.*.is_default' => 'nullable|boolean',
            'bank_deposits' => 'nullable|array',
            'bank_deposits.*.bank_name' => 'nullable|string|max:255',
            'bank_deposits.*.amount' => 'nullable|numeric',
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
        ];
    }

    private function calculateTotals(array $validated): array
    {
        $sales = (float) ($validated['sales'] ?? 0);
        $tax = (float) ($validated['tax'] ?? 0);
        $other = (float) ($validated['other'] ?? 0);
        $total = round($sales + $tax + $other + $validated['round_off'] ?? 0, 2);
        $totalPayment = collect($validated['cash_reconciliations'] ?? [])
            ->sum(fn ($row) => (float) ($row['amount'] ?? 0));
        $totalPayment = round($totalPayment, 2);


        return [
            'total' => $total,
            'total_payment' => $totalPayment,
            'cash_due' => round($total - $totalPayment, 2),
        ];
    }

    private function prepareCashReconciliations(array $rows): array
    {
        $prepared = collect($rows)
            ->map(function ($row) {
                return [
                    'cash_payment' => $row['cash_payment'] ?? null,
                    'detail' => $row['detail'] ?? null,
                    'amount' => (float) ($row['amount'] ?? 0),
                    'is_default' => (bool) ($row['is_default'] ?? false),
                ];
            })
            ->filter(function ($row) {
                if ($row['is_default']) {
                    return filled($row['cash_payment']);
                }

                return filled($row['cash_payment']) || filled($row['detail']) || $row['amount'] != 0;
            })
            ->values()
            ->all();

        return $this->mergeDefaultCashReconciliations($prepared);
    }

    private function prepareBankDeposits(array $rows): array
    {
        return collect($rows)
            ->map(function ($row) {
                return [
                    'bank_name' => $row['bank_name'] ?? null,
                    'amount' => (float) ($row['amount'] ?? 0),
                ];
            })
            ->filter(function ($row) {
                return filled($row['bank_name']) || $row['amount'] != 0;
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
        OtherDailySaleCashReconciliation::insert(
            collect($rows)->map(function ($row) use ($dailySaleId, $now) {
                return [
                    'other_daily_sale_id' => $dailySaleId,
                    'cash_payment' => $row['cash_payment'],
                    'detail' => $row['detail'],
                    'amount' => $row['amount'],
                    'is_default' => (bool) ($row['is_default'] ?? false),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all()
        );
    }

    private function insertBankDeposits(int $dailySaleId, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $now = now();
        OtherDailySaleBankDeposit::insert(
            collect($rows)->map(function ($row) use ($dailySaleId, $now) {
                return [
                    'other_daily_sale_id' => $dailySaleId,
                    'bank_name' => $row['bank_name'],
                    'amount' => $row['amount'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all()
        );
    }
}
