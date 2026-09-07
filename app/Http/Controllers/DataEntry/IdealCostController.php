<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\IdealCost;
use App\Models\DataEntry\IdealCostItem;
use App\Models\Settings\Company;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class IdealCostController extends Controller
{
    protected $companyMapping = [
        'bear pj llc' => 15,
        'bechtelsville pj llc' => 36,
        'bensalem pj llc' => 33,
        'berwyn pj llc' => 41,
        'broad st pj llc' => 40,
        'brookhaven pj llc' => 59,
        'cc philadelphia pj llc' => 37,
        'deptford pj llc' => 32,
        'dover pj llc' => 16,
        'doylestown pj llc' => 6,
        'east brunswick pj llc' => 61,
        'em newark pj llc' => 11,
        'englishtown pj llc' => 68,
        'exton evb llc' => 26,
        'feasterville pj llc' => 58,
        'folsom pj llc' => 57,
        'freehold pj llc' => 63,
        'hillsborough pj llc' => 62,
        'hockessin pj llc' => 14,
        'king of prussia pj llc' => 39,
        'lancaster pike pj llc' => 12,
        'lansdowne pj llc' => 7,
        'levittown pj llc' => 3,
        'limerick pj llc' => 65,
        'maple shade pj llc' => 1,
        'marsh road pj llc' => 13,
        'medford pj llc' => 44,
        'middeltown pj llc' => 35, // typo preserved
        'milford pj llc' => 50,
        'millsboro pj llc' => 54,
        'mount holly pj llc' => 19,
        'mount laurel pj llc' => 30,
        'ne philadelphia pj llc' => 29,
        'new castle pj llc' => 9,
        'newark pj llc' => 17,
        'norristown rp llc' => 28,
        'north wales pj llc' => 22,
        'ocean view pj llc' => 34,
        'ogletown pj llc' => 10,
        'pennsauken pj llc' => 48,
        'philadelphia pj llc' => 24,
    
        'pie investments edison llc' => 51,
        'pie investments franklin park llc' => 47,
        'pie investments haddon ave llc' => 5,
        'pie investments highland park llc' => 43,
        'pie investments kearny llc' => 45,
        'pie investments north brunswick llc' => 38,
        'pie investments pine hill llc' => 21,
        'pie investments plainfield llc' => 46,
        'pie investments somerdale llc' => 20,
        'pie investments union ave llc' => 42,
        'pie investments williamstown llc' => 49,
    
        'plymouth meeting pj llc' => 56,
        'pottstown sr llc' => 31,
        'rehoboth pj llc' => 52,
        'seaford pj llc' => 53,
        'soudertown pj llc' => 60,
        'thorndale lh llc' => 55,
        'vineland pj llc' => 8,
        'wadsworth pj llc' => 67,
        'warminster yr llc' => 18,
        'welsh pj llc' => 64,
        'west chester pj llc' => 25,
        'west philadelphia pj llc' => 66,
        'west windsor pj llc' => 27,
        'willingboro pj llc' => 4,
        'willow grove pj llc' => 2,
        'wrightstown pj llc' => 23,
    ];

    public function index()
    {
        $this->authorize('access', 'ideal-cost.index');

        $collection = IdealCost::with(['items'=> function ($query) {
            $query->authorizedCompanies('company_id');
        }])
            ->withCount('items')
            ->whereHas('items', function ($query) {
                $query->authorizedCompanies('company_id');
            })
            ->select('id', 'date', 'total_cost', 'total_mileage', 'total_delivery','created_at', 'updated_at')
            ->with('createdBy', 'updatedBy')
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'ideal-cost.create');
        $companies = Company::authorizedCompanies('id')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id')->get();
        $items = $companies->map(function ($company) {
            return [
                'company_id' => $company->id,
                'company' => $company,
                'ideal_cost' => 0,
                'mileage' => 0,
                'delivery' => 0,
            ];
        });

        return to_json([
            'form' => [
                'date' => now()->toDateString(),
                'items' => $items,
                'total_cost' => 0,
                'total_mileage' => 0,
                'total_delivery' => 0,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'ideal-cost.create');

        $validated = $this->validatePayload($request);

        DB::beginTransaction();
        try {
            $preparedItems = $this->prepareItems($validated['items']);
            $totals = $this->calculateTotals($preparedItems);

            $idealCost = IdealCost::create([
                'date' => $validated['date'],
                'total_cost' => $totals['total_cost'],
                'total_mileage' => $totals['total_mileage'],
                'total_delivery' => $totals['total_delivery'],
            ]);

            $now = now();
            $insertItems = collect($preparedItems)->map(function ($item) use ($idealCost, $now) {
                return [
                    'ideal_cost_id' => $idealCost->id,
                    'company_id' => $item['company_id'],
                    'ideal_cost' => $item['ideal_cost'],
                    'mileage' => $item['mileage'],
                    'delivery' => $item['delivery'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            IdealCostItem::insert($insertItems);

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $idealCost->id,
                'message' => 'Ideal Cost created successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Ideal Cost creation failed',
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'ideal-cost.show');

        $model = IdealCost::with(['createdBy', 'updatedBy'])
            ->findOrFail($id);

        $items = $model->items()
            ->with('company')
            ->authorizedCompanies('company_id')
            ->get()
            ->sortBy('company.name');
        $model->setRelation('items', $items->values());

        return to_json([
            'model' => $model,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'ideal-cost.update');
        $companies = Company::authorizedCompanies('id')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id')->get();


        $form = IdealCost::with(['items.company'])
            ->findOrFail($id);

        $itemsByCompany = collect($form->items)->keyBy('company_id');

        $items = $companies->map(function ($company) use ($itemsByCompany) {
            $item = $itemsByCompany->get($company->id);
            return [
                'company_id' => $company->id,
                'company'    => $company,
                'ideal_cost' => $item->ideal_cost ?? 0,
                'mileage'    => $item->mileage ?? 0,
                'delivery'   => $item->delivery ?? 0,
            ];
        });
        $form->setRelation('items', $items);

        return to_json([
            'form' => $form,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'ideal-cost.update');

        $validated = $this->validatePayload($request);

        DB::beginTransaction();
        try {
            $idealCost = IdealCost::with(['items.company'])
                ->findOrFail($id);

            $preparedItems = $this->prepareItems($validated['items']);
            $totals = $this->calculateTotals($preparedItems);

            $idealCost->update([
                'date' => $validated['date'],
                'total_cost' => $totals['total_cost'],
                'total_mileage' => $totals['total_mileage'],
                'total_delivery' => $totals['total_delivery'],
            ]);

            IdealCostItem::where('ideal_cost_id', $idealCost->id)->delete();

            $now = now();
            $insertItems = collect($preparedItems)->map(function ($item) use ($idealCost, $now) {
                return [
                    'ideal_cost_id' => $idealCost->id,
                    'company_id' => $item['company_id'],
                    'ideal_cost' => $item['ideal_cost'],
                    'mileage' => $item['mileage'],
                    'delivery' => $item['delivery'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            IdealCostItem::insert($insertItems);

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $idealCost->id,
                'message' => 'Ideal Cost updated successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Ideal Cost update failed',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'ideal-cost.delete');

        DB::beginTransaction();
        try {
            $idealCost = IdealCost::with(['items.company'])
                ->findOrFail($id);
            IdealCostItem::where('ideal_cost_id', $idealCost->id)->delete();
            $idealCost->delete();

            DB::commit();
            return to_json([
                'deleted' => true,
                'id' => $id,
                'message' => 'Ideal Cost deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Ideal Cost deletion failed',
            ], 500);
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.company_id' => ['required', 'integer', 'exists:company,id'],
            'items.*.ideal_cost' => ['required', 'numeric', 'min:0'],
            'items.*.mileage' => ['required', 'numeric', 'min:0'],
            'items.*.delivery' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function prepareItems(array $items): array
    {
        return collect($items)->map(function ($item) {
            if ($item['ideal_cost'] == 0 && $item['mileage'] == 0 && $item['delivery'] == 0) {
                return null;
            }
            return [
                'company_id' => (int) $item['company_id'],
                'ideal_cost' => (float) ($item['ideal_cost'] ?? 0),
                'mileage' => (float) ($item['mileage'] ?? 0),
                'delivery' => (float) ($item['delivery'] ?? 0),
            ];
        })->filter()->all();
    }

    private function calculateTotals(array $items): array
    {
        return [
            'total_cost' => round(collect($items)->sum('ideal_cost'), 2),
            'total_mileage' => round(collect($items)->sum('mileage'), 2),
            'total_delivery' => round(collect($items)->sum('delivery'), 2),
        ];
    }

    public function upload(Request $request)
    {
        $this->authorize('access', 'ideal-cost.create');

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|' . upload_max_file_size_rule(),
        ]);
        $importType = $request->import_type ?? 'ideal-cost';
        if($importType == 'delivery'){
            return $this->parseDeliveryFile($request->file('file'));
        }
        
        $file = $request->file('file');
        $file->store('uploads/ideal-costs', 'public');
        $path = $file->getRealPath();
        $extension = $file->getClientOriginalExtension();
        $checkNormalFormat = $this->checkNormalFormat($path);
        if($checkNormalFormat){
            return $this->parseNormalFormat($checkNormalFormat);
        }


        DB::beginTransaction();
        try {

            $sheetData = [];
            if (strtolower($extension) === 'csv') {
                $sheetData = $this->parseCsvFile($path);
                 
            } else {
                $sheetData = $this->parseExcelFile($path);
            }
            if(count($sheetData['data']) == 0){
                return to_json([
                    'message' => 'No data found in file',
                    'success' => false,
                ], 400);
            }
            $companies = Company::selectRaw('id, lower(name) as name')->get()->pluck('id', 'name')->toArray();
            $compniesByStoreNumber = Company::pluck('id', 'store_number')->toArray();
            $idealCost = IdealCost::where('date', $sheetData['date']->format('Y-m-d'))->first();
            $items = [];


            $missingCompanies = [];
            $userId =Auth::user()->id;
            $newIds = [];
            $updatedIds = [];
            if($idealCost){
                $idealCost->update([
                    'total_cost' => $sheetData['totalCost'],
                    'total_mileage' => $sheetData['totalMileage'],
                    'total_delivery' => 0,
                    'updated_at' => now(),
                    'updated_by' => $userId,
                ]);
                IdealCostItem::where('ideal_cost_id', $idealCost->id)->delete();
                $updatedIds[] = $idealCost->id;

            }else{
                $idealCost = IdealCost::create([
                    'date' => $sheetData['date'],
                    'total_cost' => $sheetData['totalCost'],
                    'total_mileage' => $sheetData['totalMileage'],
                    'total_delivery' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                $newIds[] = $idealCost->id;
            }

            foreach ($sheetData['data'] as $row) {
                $companyName = strtolower($row['company_name']);
                if(!isset($companies[$companyName]) && !isset($compniesByStoreNumber[$companyName]) ){
                    $missingCompanies[] = $companyName;
                }
                if((isset($companies[$companyName]) || isset($compniesByStoreNumber[$companyName])) && $row['ideal_cost'] > 0 && $row['mileage'] > 0 ) {
                    $companyId = isset($companies[$companyName]) ? $companies[$companyName] : $compniesByStoreNumber[$companyName];
                    $items[] = [
                        'ideal_cost_id' => $idealCost->id,
                        'company_id' => $companyId,
                        'ideal_cost' => (float) $row['ideal_cost'],
                        'mileage' => (float) $row['mileage'],
                        'delivery' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            IdealCostItem::insert($items);
            $messages = [];
            if(!empty($missingCompanies)){
                $messages[] = 'The following companies are missing from the list: '.implode(', ', $missingCompanies);
            }

            $allIdealCostIds = array_values(array_unique(array_merge($newIds, $updatedIds)));
            if (count($allIdealCostIds) > 0) {
                ActivityLogService::logBulkImport(
                    'ideal-cost',
                    [
                        'ids' => $allIdealCostIds,
                        'created_ids' => array_values(array_unique($newIds)),
                        'updated_ids' => array_values(array_unique($updatedIds)),
                        'date' => $sheetData['date'],
                    ],
                    "Ideal Cost bulk upload for {$sheetData['date']}"
                );
            }

            DB::commit();
            return to_json([
                'message' => $idealCost ? 'Ideal Cost updated successfully' : 'Ideal Cost uploaded successfully',
                'data' => $sheetData,
                'messages' => $messages,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'message' =>  $e->getMessage()
            ], 500);
        }
    }
    private function parseNormalFormat($checkNormalFormat)
    {

        DB::beginTransaction();
        try{
            $sheet = $checkNormalFormat['sheet'];
            $rowLimit = $checkNormalFormat['row_limit'];
            $currentWorkgroupId = session('workgroup');
            $companies = Company::where('workgroup_id', $currentWorkgroupId)->pluck('id', 'store_number')->toArray();
            $dateFormats = ['m/d/Y', 'm-d-Y', 'm/d/y', 'm-d-y'];
            $data = [];
            $userId = Auth::user()->id;
            $now = now();

            $normalizeDate = function ($rawDate) use ($dateFormats) {
                foreach ($dateFormats as $dateFormat) {
                    try {
                        return Carbon::createFromFormat($dateFormat, $rawDate)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        continue;
                    }
                }

                return null;
            };
            $messages = [];

            for ($row = 2; $row <= $rowLimit; $row++) {
                $rawDate = $sheet->getCell('A' . $row)->getFormattedValue();
                $company = $sheet->getCell('B' . $row)->getFormattedValue();
                $idealCost = (float) $sheet->getCell('C' . $row)->getFormattedValue();
                $mileage = (float) $sheet->getCell('D' . $row)->getFormattedValue();
                $delivery = (float) $sheet->getCell('E' . $row)->getFormattedValue();
                $company = explode(' - ', $company);
                $company = $company[0];
                if (!isset($companies[$company])) {
                    $messages[] = 'Company '.$company.' not found in the list';
                    continue;
                }
                $date = $normalizeDate($rawDate);
                if (!$date) {
                    $messages[] = 'Invalid date format in row '.$row;
                    continue;
                }

                if (!isset($data[$date])) {
                    $data[$date] = [
                        'date' => $date,
                        'ideal_cost' => 0,
                        'mileage' => 0,
                        'delivery' => 0,
                        'data' => [],
                    ];
                }
                if($idealCost == 0 && $mileage == 0 && $delivery == 0){
                    continue;
                }
                $data[$date]['data'][] = [
                    'ideal_cost' => $idealCost,
                    'mileage' => $mileage,
                    'delivery' => $delivery,
                    'company_id' => $companies[$company],
                ];
                $data[$date]['ideal_cost'] += $idealCost;
                $data[$date]['mileage'] += $mileage;
                $data[$date]['delivery'] += $delivery;
            }

            foreach ($data as $date => $item) {
                $idealCostIds = IdealCost::where('date', $date)->whereHas('items', function ($query) use ($item) {
                    $query->whereIn('company_id', array_column($item['data'], 'company_id'));
                })->pluck('id')->toArray();


                if (count($idealCostIds) > 0) {
                    IdealCostItem::whereIn('ideal_cost_id', $idealCostIds)->delete();
                    IdealCost::whereIn('id', $idealCostIds)->delete();        
                } 
                $idealCost = IdealCost::create([
                    'date' => $date,
                    'total_cost' => $item['ideal_cost'],
                    'total_mileage' => $item['mileage'],
                    'total_delivery' => $item['delivery'],
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $insertItems = collect($item['data'])->map(function ($item) use ($idealCost, $now, $userId) {
                    return [
                        'ideal_cost_id' => $idealCost->id,
                        'company_id' => $item['company_id'],
                        'ideal_cost' => $item['ideal_cost'],
                        'mileage' => $item['mileage'],
                        'delivery' => $item['delivery'],
                    ];
                })->all();

                IdealCostItem::insert($insertItems);
            }

            $Ids = IdealCost::whereIn('id', array_keys($data))->pluck('id')->toArray();
            if (count($Ids) > 0) {
                ActivityLogService::logBulkImport(
                    'ideal-cost',
                    [
                        'ids' => $Ids,
                    ],
                    "Ideal Cost bulk upload from normal format for ".implode(', ', array_keys($data))
                );
            }

            DB::commit();
            return to_json([
                'message' => 'Ideal Cost uploaded successfully',
                'success' => true,
                'messages' => $messages,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'message' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }
    private function parseCsvFile($path)
    {
        $data = [];
        $starting=false;
        $rowIndex=0;
        $totalCost=0;
        $totalMileage=0;
        $date = [];
        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if($rowIndex === 2){
                    preg_match('/Y(\d+)\sW(\d+)/', $row[1], $matches);

                    $year = $matches[1];
                    $week = $matches[2];
            
                    $date = Carbon::now()->setISODate($year, $week)->endOfWeek();
                    if(!$date->isValid()){
                        throw new \Exception('Invalid date format in row '.$rowIndex);
                    }
                }
                else if ($rowIndex === 3 && $row[0] == 'Row Labels' && $row[1] == 'Ideal Cost USD' && $row[2] == 'Mileage Cost USD' && $row[3] == 'Dispatch Fee USD') {
                    $starting=true;
                } else if ($starting && !Str::contains($row[0], 'Total')) {
                    $data[] = [
                        'company_name' => strtolower($row[0]),
                        'ideal_cost' => (float)str_replace([',', '$', ' '], '', $row[1]) ,
                        'mileage' => (float)str_replace([',', '$', ' '], '', $row[3]),
                    ];
                    $totalCost += (float)str_replace([',', '$', ' '], '', $row[1]);
                    $totalMileage += (float)str_replace([',', '$', ' '], '', $row[3]);
                }
                $rowIndex++;
            }
            fclose($handle);
        }
        return [
            'date' => $date,
            'data' => $data,
            'totalCost' => $totalCost,
            'totalMileage' => $totalMileage,
        ];
    }
    private function parseExcelFile($path)
    {
        $data = [];
        $starting=false;
        $rowIndex=0;
        $totalCost=0;
        $totalMileage=0;
        $date = [];
        $excelFile = IOFactory::load($path);
        $sheet = $excelFile->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        foreach ($rows as $row) {
            $label = (string) ($row[0] ?? '');
            $cost = (string) ($row[1] ?? '');
            $mileage = (string) ($row[2] ?? '');
            $dispatch = (string) ($row[3] ?? '');
            if($rowIndex === 1){
                preg_match('/Y(\d+)\sW(\d+)/', $cost, $matches);
                if (count($matches) < 3) {
                    throw new \Exception('Invalid date format in row '.$rowIndex);
                }

                $year = $matches[1];
                $week = $matches[2];
        
                $date = Carbon::now()->setISODate($year, $week)->endOfWeek();
                if(!$date->isValid()){
                    throw new \Exception('Invalid date format in row '.$rowIndex);
                }
            }
            else if ($rowIndex === 2 && $label == 'Row Labels' && $cost == 'Ideal Cost USD' && $mileage == 'Mileage Cost USD' && $dispatch == 'Dispatch Fee USD') {
                $starting=true;
            } else if ($starting && !Str::contains($label, 'Total') && $label !== '') {
                $data[] = [
                    'company_name' => strtolower($label),
                    'ideal_cost' => (float)str_replace([',', '$', ' '], '', $cost) ,
                    'mileage' => (float)str_replace([',', '$', ' '], '', $dispatch),
                ];
                $totalCost += (float)str_replace([',', '$', ' '], '', $cost);
                $totalMileage += (float)str_replace([',', '$', ' '], '', $dispatch);
            }
            $rowIndex++;
        }
        return [
            'date' => $date,
            'data' => $data,
            'totalCost' => $totalCost,
            'totalMileage' => $totalMileage,
        ];
    }
    private function parseDeliveryFile($path)
    {
        DB::beginTransaction();
        try
        {    
            $excelfile = IOFactory::load($path);
            $sheet     = $excelfile->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $row_range = range(2, $row_limit);
            $companies = Company::pluck('id', 'store_number')->toArray();

            $data = [];
            if($sheet->getCell('A1')->getFormattedValue() != 'Level 1' && $sheet->getCell('B1')->getFormattedValue() != 'Timeline' && $sheet->getCell('A3')->getFormattedValue() != 'TY Dispatched Delivery Orders'){
                throw new \Exception('Invalid file format');
            }

            foreach ($row_range as $row) {
                $date = $sheet->getCell('B' . $row)->getFormattedValue();
                $company = $sheet->getCell('A' . $row)->getFormattedValue();
                if($company=='Grand Total') continue;
                if(!isset($companies[$company])){
                    // throw new \Exception('Company not found in row '.$row);
                    continue;
                }
                $companyId = $companies[$company];
                if(!$date)continue;
                preg_match('/Y(\d{4}) W(\d{2})/', $date, $matches);
                $year = $matches[1];
                $week = $matches[2];
                
                $date = Carbon::now()->setISODate($year, $week)->endOfWeek();
                if(!$date->isValid()){
                    throw new \Exception('Invalid date format in row '.$row);
                }
                $key = $date->format('Y-m-d').'-'.$companyId;
                $data[$key] = [
                    'date' => $date->format('Y-m-d'),
                    'company_id' => $companyId,
                    'delivery' => (float) $sheet->getCell('C' . $row)->getFormattedValue(),
                ];
            }

            $idealCostRecords = IdealCost::whereIn('date', array_column($data, 'date'))->with('items')->get();
            foreach ($idealCostRecords as $cost) {
                $dateStr = Carbon::parse($cost->date)->format('Y-m-d');
                $totalDelivery = 0;
                $cost->items->each(function ($idealCostItem) use ($data, $dateStr, &$totalDelivery) {
                    $key = $dateStr . '-' . $idealCostItem->company_id;
                    if (isset($data[$key])) {
                        $idealCostItem->update(['delivery' => $data[$key]['delivery']]);
                    }
                    $totalDelivery += (float)$idealCostItem->delivery;
                });
                $cost->update(['total_delivery' => $totalDelivery]);
            }
            $ids = $idealCostRecords->pluck('id')->toArray();
            if (count($ids) > 0) {
                ActivityLogService::logBulkImport(
                    'ideal-cost',
                    [
                        'ids' => $ids,
                    ],
                    "Delivery bulk upload for ".implode(', ', array_column($data, 'date'))
                );
            }
            DB::commit();
            return to_json([
                'message' => 'Delivery file uploaded successfully',
                'success' => true,
            ]);

        } catch (\Throwable $e) {
            dd($e);
            DB::rollBack();
            return to_json([
                'message' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    private function checkNormalFormat($path)
    {
        try
        {    
            $excelfile = IOFactory::load($path);
            $sheet     = $excelfile->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();

            if($sheet->getCell('A1')->getFormattedValue() != 'Date' && $sheet->getCell('B1')->getFormattedValue() != 'Company' && $sheet->getCell('C1')->getFormattedValue() != 'Ideal Cost' && $sheet->getCell('D1')->getFormattedValue() != 'Mileage' && $sheet->getCell('E1')->getFormattedValue() != 'Delivery'){
                return false;
            }

            return [
                'sheet'=> $sheet,
                'row_limit' => $row_limit
            ];
            return to_json([
                'message' => 'Delivery file uploaded successfully',
                'success' => true,
            ]);

        } catch (\Throwable $e) {
            dd($e);
            DB::rollBack();
            return to_json([
                'message' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $this->authorize('access', 'ideal-cost.index');

        $query = IdealCost::with('items.company');

        $request->query->set('page', 1);
        $request->query->set('limit', 1000);

        $collection = $query->whereHas('items', function ($query) {
            $query->authorizedCompanies('company_id');
        })->export($request->all());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Date', 'Company', 'Ideal Cost', 'Mileage', 'Delivery'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:E1')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 2;
        foreach ($collection as $idealCost) {
            $costDate = $idealCost->date ? \Carbon\Carbon::parse($idealCost->date)->format('m-d-Y') : '';
            foreach ($idealCost->items as $item) {
                $sheet->fromArray([
                    $costDate,
                    $item->company->name ?? '',
                    $item->ideal_cost ?? 0,
                    $item->mileage ?? 0,
                    $item->delivery ?? 0,
                ], null, 'A' . $row);
                $row++;
            }
        }
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'ideal_costs_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
