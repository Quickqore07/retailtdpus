<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\FoodPurchase;
use App\Models\DataEntry\FoodPurchaseItems;
use App\Models\Settings\Company;
use App\Models\Settings\Workgroup;
use App\Services\Quickqore\QuickqoreService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FoodPurchaseController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'food-purchase.index');

        $collection = FoodPurchase::with(['items' => function ($query) {
            $query->authorizedCompanies('company_id');
        }])->whereHas('items', function ($query) {
            $query->authorizedCompanies('company_id');
        })->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'food-purchase.create');


        return to_json([
            'form' => [
                'date' => now()->toDateString(),
                'items' => [
                    $this->emptyItem(),
                    $this->emptyItem(),
                    $this->emptyItem(),
                    $this->emptyItem(),
                    $this->emptyItem(),
                    $this->emptyItem(),
                    $this->emptyItem(),
                ],
                'total_amount' => 0,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'food-purchase.create');

        $validated = $this->validatePayload($request);

        DB::beginTransaction();
        $companies = Company::get()->pluck('store_number', 'id');
        try {
            $preparedItems = $this->prepareItems($validated['items']);
            $totalAmount = collect($preparedItems)->sum('total_amount');

            $purchase = FoodPurchase::create([
                'date' => $validated['date'],
                'total_amount' => $totalAmount,
            ]);

            $now = now();
            $insertItems = collect($preparedItems)->map(function ($item) use ($purchase, $now) {

                return [
                    'food_purchase_id' => $purchase->id,
                    'company_id' => $item['company_id'],
                    'food_product_purchase' => $item['food_product_purchase'],
                    'paper_supplies' => $item['paper_supplies'],
                    'smallware_supplies' => $item['smallware_supplies'],
                    'cleaning_supplies' => $item['cleaning_supplies'],
                    'pepsi' => $item['pepsi'],
                    'total_amount' => $item['total_amount'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            FoodPurchaseItems::insert($insertItems);



            /* Quickqore API */

            $qq_data=[];
            $qq_data['date'] = $purchase->date;
            $qq_data['purchase_id'] = $purchase->id;

            $qq_data['data'] = [];
            $workGroup = Workgroup::where('id', $request->session()->get('workgroup'))->first();
            $qq_data['workgroup_name'] = $workGroup->name;

            foreach ($preparedItems as $item) {
                $company = $companies[$item['company_id']];
                $qq_data['data'][] = [
                    'store_no' => $company,
                    'food_supplies' => $item['food_product_purchase'],
                    'paper_supplies' => $item['paper_supplies'],
                    'smallware_supplies' => $item['smallware_supplies'],
                    'cleaning_supplies' => $item['cleaning_supplies'],
                    'pepsi' => $item['pepsi'],
                ];
            }
            $quickqoreService = new QuickqoreService();
            $quickqoreService->handlePurchase($qq_data);
            
            /* End Quickqore API */

            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $purchase->id,
                'message' => 'Food Purchase created successfully',
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();
            dd($e);
            return to_json([
                'saved' => false,
                'message' => 'Food Purchase creation failed',
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'food-purchase.show');

        $model = FoodPurchase::findOrFail($id);
        $model->setRelation('items', EloquentCollection::make(
            $model->items()
                ->with('company')
                ->authorizedCompanies('company_id')
                ->get()
                ->sortBy('company.name')
                ->values()
                ->all()
        ));

        return to_json([
            'model' => $model,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'food-purchase.update');

        $form = FoodPurchase::with(['items.company'])

            ->findOrFail($id);

        $form->setRelation('items', EloquentCollection::make(
            $form->items()
                ->with('company')
                ->authorizedCompanies('company_id')
                ->get()
                ->sortBy('company.name')
                ->values()
                ->all()
        ));

        return to_json([
            'form' => $form,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'food-purchase.update');

        $validated = $this->validatePayload($request);
        $companies = Company::get()->pluck('store_number', 'id');
        DB::beginTransaction();
        try {
            $purchase = FoodPurchase::with(['items.company'])
                ->findOrFail($id);

            $preparedItems = $this->prepareItems($validated['items']);
            $totalAmount = collect($preparedItems)->sum('total_amount');

            $purchase->update([
                'date' => $validated['date'],
                'total_amount' => $totalAmount,
            ]);

            FoodPurchaseItems::where('food_purchase_id', $purchase->id)->delete();

            $now = now();
            $insertItems = collect($preparedItems)->map(function ($item) use ($purchase, $now) {
                return [
                    'food_purchase_id' => $purchase->id,
                    'company_id' => $item['company_id'],
                    'food_product_purchase' => $item['food_product_purchase'],
                    'paper_supplies' => $item['paper_supplies'],
                    'smallware_supplies' => $item['smallware_supplies'],
                    'cleaning_supplies' => $item['cleaning_supplies'],
                    'pepsi' => $item['pepsi'],
                    'total_amount' => $item['total_amount'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            FoodPurchaseItems::insert($insertItems);



            $qq_data=[];
            $qq_data['date'] = $purchase->date;
            $qq_data['purchase_id'] = $purchase->id;

            $qq_data['data'] = [];
            $workGroup = Workgroup::where('id', $request->session()->get('workgroup'))->first();
            $qq_data['workgroup_name'] = $workGroup->name;

            foreach ($preparedItems as $item) {
                $company = $companies[$item['company_id']];
                $qq_data['data'][] = [
                    'store_no' => $company,
                    'food_supplies' => $item['food_product_purchase'],
                    'paper_supplies' => $item['paper_supplies'],
                    'smallware_supplies' => $item['smallware_supplies'],
                    'cleaning_supplies' => $item['cleaning_supplies'],
                    'pepsi' => $item['pepsi'],
                ];
            }
            $quickqoreService = new QuickqoreService();
            $quickqoreService->handlePurchase($qq_data);
            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $purchase->id,
                'message' => 'Food Purchase updated successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e);
            return to_json([
                'saved' => false,
                'message' => 'Food Purchase update failed',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'food-purchase.delete');

        DB::beginTransaction();
        try {
            $purchase = FoodPurchase::with(['items.company'])
                ->findOrFail($id);
            FoodPurchaseItems::where('food_purchase_id', $purchase->id)->delete();
            $purchase->delete();

            DB::commit();
            return to_json([
                'deleted' => true,
                'id' => $id,
                'message' => 'Food Purchase deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Food Purchase deletion failed',
            ], 500);
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.company_id' => ['required', 'integer', 'exists:company,id'],
            'items.*.food_product_purchase' => ['required', 'numeric', 'min:0'],
            'items.*.paper_supplies' => ['required', 'numeric', 'min:0'],
            'items.*.smallware_supplies' => ['required', 'numeric', 'min:0'],
            'items.*.cleaning_supplies' => ['required', 'numeric', 'min:0'],
            'items.*.pepsi' => ['required', 'numeric', 'min:0'],
        ],
        [
            'date.required' => 'Date is required',
            'date.date' => 'Date must be a date',
            'items.min' => 'Items must have at least 1 item',
            'items.required' => 'Items must have at least 1 item',
            'items.array' => 'Items must be an array',
            'items.*.company_id.required' => 'Company is required',
            'items.*.company_id.integer' => 'Company must be an integer',
            'items.*.company_id.exists' => 'Company does not exist',
            'items.*.food_product_purchase.required' => 'Food Product Purchase is required',
            'items.*.food_product_purchase.numeric' => 'Food Product Purchase must be a number',
            'items.*.food_product_purchase.min' => 'Food Product Purchase must be greater than 0',
        ]
    );
    }

    private function prepareItems(array $items): array
    {
        return collect($items)->map(function ($item) {
            $foodProductPurchase = (float) ($item['food_product_purchase'] ?? 0);
            $paperSupplies = (float) ($item['paper_supplies'] ?? 0);
            $smallwareSupplies = (float) ($item['smallware_supplies'] ?? 0);
            $cleaningSupplies = (float) ($item['cleaning_supplies'] ?? 0);
            $pepsi = (float) ($item['pepsi'] ?? 0);

            return [
                'company_id' => (int) $item['company_id'],
                'food_product_purchase' => $foodProductPurchase,
                'paper_supplies' => $paperSupplies,
                'smallware_supplies' => $smallwareSupplies,
                'cleaning_supplies' => $cleaningSupplies,
                'pepsi' => $pepsi,
                'total_amount' => round(
                    $foodProductPurchase +
                    $paperSupplies +
                    $smallwareSupplies +
                    $cleaningSupplies +
                    $pepsi,
                    2
                ),
            ];
        })->all();
    }

    private function emptyItem(): array
    {
        return [
            'company_id' => null,
            'company' => null,
            'food_product_purchase' => 0,
            'paper_supplies' => 0,
            'smallware_supplies' => 0,
            'cleaning_supplies' => 0,
            'pepsi' => 0,
            'total_amount' => 0,
        ];
    }

    public function export(Request $request)
    {
        $this->authorize('access', 'food-purchase.index');

        $query = FoodPurchase::with('items.company');
        $collection = $query->export($request->all());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Date', 'Company', 'Food Product Purchase', 'Paper Supply', 'Smallware Supplies', 'Cleaning Supplies', 'Pepsi', 'Row Total'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:H1')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 2;
        foreach ($collection as $purchase) {
            $purchaseDate = $purchase->date ? \Carbon\Carbon::parse($purchase->date)->format('Y-m-d') : '';
            foreach ($purchase->items as $item) {
                $sheet->fromArray([
                    $purchaseDate,
                    $item->company->name ?? '',
                    $item->food_product_purchase ?? 0,
                    $item->paper_supplies ?? 0,
                    $item->smallware_supplies ?? 0,
                    $item->cleaning_supplies ?? 0,
                    $item->pepsi ?? 0,
                    $item->total_amount ?? 0,
                ], null, 'A' . $row);
                $row++;
            }
        }
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'food_purchases_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
