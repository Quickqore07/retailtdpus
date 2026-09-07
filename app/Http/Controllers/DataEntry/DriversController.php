<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\Drivers;
use App\Models\Employee;
use App\Models\Settings\Company;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DriversController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'driver.index');
        // $currentCompany = $request->session()->get('company');

        $collection = Drivers::join('company', 'drivers.company_id', '=', 'company.id')
            // ->where('drivers.company_id', $currentCompany)
            ->authorizedCompanies('drivers.company_id')
            ->select('drivers.*')
            ->with('company', 'createdBy', 'updatedBy')
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'driver.delete');

        DB::beginTransaction();
        try {
            $driver = Drivers::authorizedCompanies('company_id')->findOrFail($id);
            $driver->delete();

            DB::commit();
            return to_json([
                'deleted' => true,
                'id' => $id,
                'message' => 'Driver deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Driver deletion failed',
            ], 500);
        }
    }

    public function destroyMultiple(Request $request)
    {
        $this->authorize('access', 'driver.delete-multiple');

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:drivers,id',
        ]);

        DB::beginTransaction();
        try {
            $ids = Drivers::authorizedCompanies('company_id')
                ->whereIn('id', $request->ids)
                ->pluck('id')
                ->toArray();

            Drivers::whereIn('id', $ids)->delete();

            DB::commit();
            return to_json([
                'deleted' => true,
                'message' => 'Drivers deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Driver deletion failed',
            ], 500);
        }
    }
    public function upload(Request $request){
        $this->authorize('access', 'driver.create');

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|' . upload_max_file_size_rule(),
        ]);

        $authorized_companies = authorizedCompanies();
        if(count($authorized_companies) == 0){
            return to_json([
                'message' => 'No authorized companies found',
                'uploaded' => false,
            ], 400);
        }
        DB::beginTransaction();
        try {

            $excelfile = IOFactory::load($request->file('file'));
            $sheet     = $excelfile->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $row_range = range(2, $row_limit);

            $companies = Company::whereIn('id', $authorized_companies)->pluck('id', 'store_number')->toArray();

            $employees = Employee::with('aliases')->select('id', 'employee_id', 'pos_name', 'termination_date', 'workgroup_id')
            ->authorizedWorkgroup()
            ->get();

            $allEmployeeIds = [];

            $employeeMap = [];
            foreach ($employees as $emp) {
                $ids = $emp->allMatchIds();
                $names = $emp->allMatchNames();
                foreach ($ids as $id) {
                    $allEmployeeIds[$id] = $emp->id;
                    foreach ($names as $name) {
                        $employeeMap[$id . '|' . $name] = $emp->id;
                    }
                }
            }
            
            $sheetData = [];
            $currenCompany = null;
            $currenDriver = null;
            $driverId = null;
            $driverName = null;
            $messages = [];

            $createdBy = Auth::id();
            $createdAt = now();

            $dates = [];
            foreach ($row_range as $row) {
                $cell_d = $sheet->getCell('D' . $row)->getFormattedValue();
                if(!$cell_d || str_contains($cell_d, 'Total')) continue;
                $dates[] = Carbon::createFromFormat('m/d/Y', $cell_d)->format('Y-m-d');
            }
            $dates = array_unique($dates);
            $drivers = Drivers::whereIn('date', $dates)->select('id', 'date', 'company_id', 'driver_id')->get()->keyBy(function($item){
                return $item->date . '|' . $item->company_id . '|' . $item->driver_id;
            });

            foreach ($row_range as $row) {
                $cell_a = $sheet->getCell('A' . $row)->getFormattedValue();
                $cell_b = $sheet->getCell('B' . $row)->getFormattedValue();
                $cell_c = $sheet->getCell('C' . $row)->getFormattedValue();
                $cell_d = $sheet->getCell('D' . $row)->getFormattedValue();

                if(str_contains($cell_d, 'Total')){
                    $driverId = null;
                    $driverName = null;
                    $currenDriver = null;
                    continue;
                }

                if(str_contains($cell_b, 'Total')){
                    $currenCompany = null;
                    continue;
                }
                if(str_contains($cell_a, 'Total')){
                    break;
                }
                
                if($cell_a) {
                    $currenCompany = isset($companies[$cell_a]) ? $companies[$cell_a] : null;
                    if(!$currenCompany){
                       $messages[] = "Company {$cell_a} not found in row ".$row + 2;
                        continue;
                    }
                }
                if(($cell_c || $cell_b) && $currenCompany) {
                    $driverId = $cell_b;
                    $driverName = $cell_c;
                    $currenDriver = isset($employeeMap[$cell_b . '|' . $cell_c]) ? $employeeMap[$cell_b . '|' . $cell_c] : null;
                    if(!$currenDriver && isset($allEmployeeIds[$cell_b])){
                        $currenDriver = $allEmployeeIds[$cell_b];
                    }
                    if(!$currenDriver){
                        $messages[] = "Driver {$cell_b} not found in row ".$row + 2;
                    }
                }
                if($currenCompany && ($driverName || $driverId)){
                    $date = Carbon::createFromFormat('m/d/Y', $cell_d)->format('Y-m-d');


                    $timeIn = str_replace([',', '$'], '', $sheet->getCell('E' . $row)->getFormattedValue());
                    $timeOut = str_replace([',', '$'], '', $sheet->getCell('F' . $row)->getFormattedValue());
                    $driverInStorePay = str_replace([',', '$'], '', $sheet->getCell('G' . $row)->getFormattedValue());
                    $otherInStorePay = str_replace([',', '$'], '', $sheet->getCell('H' . $row)->getFormattedValue());
                    $onRoadPay = str_replace([',', '$'], '', $sheet->getCell('I' . $row)->getFormattedValue());
                    $cashTips = str_replace([',', '$'], '', $sheet->getCell('J' . $row)->getFormattedValue());
                    $ccTips = str_replace([',', '$'], '', $sheet->getCell('K' . $row)->getFormattedValue());
                    $mileage = str_replace([',', '$'], '', $sheet->getCell('L' . $row)->getFormattedValue());
                    $allInPay = str_replace([',', '$'], '', $sheet->getCell('M' . $row)->getFormattedValue());
                    $storeHours = str_replace([',', '$'], '', $sheet->getCell('N' . $row)->getFormattedValue());
                    $roadHours = str_replace([',', '$'], '', $sheet->getCell('O' . $row)->getFormattedValue());
                    $totalHours = str_replace([',', '$'], '', $sheet->getCell('P' . $row)->getFormattedValue());
                    $avgPayPerHour = str_replace([',', '$'], '', $sheet->getCell('Q' . $row)->getFormattedValue());
                    $delivery = str_replace([',', '$'], '', $sheet->getCell('R' . $row)->getFormattedValue());


                    $key = $date . '|' . $currenCompany . '|' . $driverId;
                    if(isset($drivers[$key])){
                        $driver = $drivers[$key];
                        Drivers::where('id', $driver->id)->update([
                            'time_in' => $timeIn ?? null,
                            'time_out' => $timeOut ?? null,
                            'driver_in_store_pay' => is_numeric($driverInStorePay) ? $driverInStorePay : 0,
                            'other_in_store_pay' => is_numeric($otherInStorePay) ? $otherInStorePay : 0,
                            'on_road_pay' => is_numeric($onRoadPay) ? $onRoadPay : 0,
                            'cash_tips' => is_numeric($cashTips) ? $cashTips : 0,
                            'cc_tips' => is_numeric($ccTips) ? $ccTips : 0,
                            'mileage' => is_numeric($mileage) ? $mileage : 0,
                            'all_in_pay' => is_numeric($allInPay) ? $allInPay : 0,
                            'store_hours' => is_numeric($storeHours) ? $storeHours : 0,
                            'road_hours' => is_numeric($roadHours) ? $roadHours : 0,
                            'total_hours' => is_numeric($totalHours) ? $totalHours : 0,
                            'avg_pay_per_hour' => is_numeric($avgPayPerHour) ? $avgPayPerHour : 0,
                            'delivery' => is_numeric($delivery) ? $delivery : 0,
                            'updated_by' => $createdBy,
                            'updated_at' => $createdAt,
                        ]);
                        $messages[] = "Driver {$driverId} already exists in row ".$row +1 . " data is updated";
                        continue;
                    }
                    $sheetData[] = [
                        'company_id' => $currenCompany,
                        'driver_id' => $driverId,
                        'driver_name' => $driverName,
                        'employee_id' => $currenDriver,
                        'date' => $date,
                        'time_in' => $timeIn ?? null,
                        'time_out' => $timeOut ?? null,
                        'driver_in_store_pay' => is_numeric($driverInStorePay) ? $driverInStorePay : 0,
                        'other_in_store_pay' => is_numeric($otherInStorePay) ? $otherInStorePay : 0,
                        'on_road_pay' => is_numeric($onRoadPay) ? $onRoadPay : 0,
                        'cash_tips' => is_numeric($cashTips) ? $cashTips : 0,
                        'cc_tips' => is_numeric($ccTips) ? $ccTips : 0,
                        'mileage' => is_numeric($mileage) ? $mileage : 0,
                        'all_in_pay' => is_numeric($allInPay) ? $allInPay : 0,
                        'store_hours' => is_numeric($storeHours) ? $storeHours : 0,
                        'road_hours' => is_numeric($roadHours) ? $roadHours : 0,
                        'total_hours' => is_numeric($totalHours) ? $totalHours : 0,
                        'avg_pay_per_hour' => is_numeric($avgPayPerHour) ? $avgPayPerHour : 0,
                        'delivery' => is_numeric($delivery) ? $delivery : 0,
                        'created_by' => $createdBy,
                        'updated_by' => $createdBy,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];
                }
            }


            $maxIdBefore = Drivers::max('id') ?? 0;
            if(count($sheetData) > 0){
                foreach(array_chunk($sheetData, 1000) as $chunk){
                    Drivers::insert($chunk);
                }
            }
            
            $insertedIds = Drivers::where('id', '>', $maxIdBefore)->pluck('id')->toArray();
            ActivityLogService::logBulkImport('drivers', ['ids' => $insertedIds], "Dates: ".implode(', ', array_unique(array_column($sheetData, 'date'))));
            DB::commit();
            return to_json([
                'message' => 'Driver upload successfully',
                'uploaded' => true,
                'messages' => $messages,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            info($e);
            return to_json([
                'message' => 'Driver upload failed',
                'uploaded' => false,
                'messages' => $messages,
            ], 500);
        }
        
        
    }
}
