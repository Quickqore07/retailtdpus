<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\EmployeeHours;
use App\Models\Employee;
use App\Models\Payroll\EmployeeRates;
use App\Models\Payroll\PayrollCheckAmount;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EmployeeHoursController extends Controller
{
    /**
     * Display a listing of employee hours
     */
    private EmployeeService $employeeService;
    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }
    public function index(Request $request)
    {
        $this->authorize('access', 'employee-hour.index');
        $employeeHours = EmployeeHours::with(['employee', 'company', 'role'])->filter();

        return to_json([
            'collection' => $employeeHours,
        ]);
    }

    /**
     * Show the form for creating a new resource
     */
    public function create()
    {
        $this->authorize('access', 'employee-hour.create');
        $item = [
            'date' => now()->format('Y-m-d'),
            'employee_id' => null,
            'employee_name' => '',
            'company_id' => null,
            'dev_id' => 0,
            'ssn' => '',
            'pay_id' => 0,
            'pay_type' => 'HR',
            'total_hours' => 0,
            'tips' => 0,
            'mileage_excess' => 0,
            'incentive' => 0,
            'bonus' => 0,
            'home_store' => 0,
            'role_id' => null,
            'pay_rate' => 0,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    /**
     * Store a newly created resource in storage
     */
    public function store(Request $request)
    {
        $this->authorize('access', 'employee-hour.create');
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'employee_id' => 'required',
                'employee_name' => 'required|string|max:255',
                'company_id' => 'required|exists:company,id',
                'role_id' => 'required|exists:employee_roles,id',
                'pay_type' => 'required|in:HR,WK',
                'total_hours' => 'required|numeric|min:0',
                'pay_rate' => 'required|numeric|min:0',
                'tips' => 'nullable|numeric|min:0',
                'mileage_excess' => 'nullable|numeric|min:0',
                'incentive' => 'nullable|numeric|min:0',
                'bonus' => 'nullable|numeric|min:0',
                'ssn' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return to_json([
                    'saved' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }


            $employee = Employee::where('employee_id', $request->employee_id)
                ->orWhere('pos_name', $request->employee_name)
                ->orWhereHas('aliases', fn ($a) => $a->where('alias_name', $request->employee_name))
                ->first();

            $input = $request->all();
            $nameMatches = $employee && in_array($request->employee_name, $employee->allMatchNames(), true);
            if (isset($employee) && $employee->employee_id != $request->employee_id && $nameMatches) {
                $input['employee_type'] = 'Existing';
                $employee = $this->employeeService->createEmployee($input);
            } else if (isset($employee) && $employee->employee_id == $request->employee_id && !$nameMatches) {
                $input['employee_type'] = 'Existing';
                $employee = $this->employeeService->createEmployee($input);
            } else if (!isset($employee)) {
                $input['employee_type'] = 'New';
                $employee = $this->employeeService->createEmployee($input);
            }
            $input['employee_id'] = $employee['id'];

            $employee_rates = EmployeeRates::where('employee_id', $employee['id'])->get();

            if (collect($employee_rates)->where('role_id', $request->role_id)->where('pay_type', $request->pay_type)->isEmpty()) {
                $employee_rates = $this->employeeService->createEmployeeRates($input);
            }




            if (!$employee) {
                return to_json([
                    'saved' => false,
                    'message' => 'Employee not found',
                ], 404);
            }

            $employeeHours = EmployeeHours::create($input);

            DB::commit();

            return to_json([
                'id' => $employeeHours->id,
                'employeeHours' => $employeeHours,
                'saved' => true,
                'message' => 'Employee hours created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Employee hours creation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource
     */
    public function show($id)
    {
        $employeeHours = EmployeeHours::with(['employee', 'company', 'role'])->findOrFail($id);
        $check_amount = PayrollCheckAmount::where('employee_id', $employeeHours->employee->id)->where('role_id', $employeeHours->role_id)->where('eow',  Carbon::parse($employeeHours->date)->endOfWeek()->toDateString())->first();
        $employeeHours->reviewed = $check_amount ? true : false;
        return to_json([
            'model' => $employeeHours,
        ]);
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit($id)
    {
        $employeeHours = EmployeeHours::with(['employee', 'company', 'role'])->findOrFail($id);
        $employeeHours->employee_id = $employeeHours->employee->employee_id;

        $check_amount = PayrollCheckAmount::where('employee_id', $employeeHours->employee->id)->where('role_id', $employeeHours->role_id)->where('eow',  Carbon::parse($employeeHours->date)->endOfWeek()->toDateString())->first();
        $employeeHours->reviewed = $check_amount ? true : false;
        return to_json([
            'form' => $employeeHours,
        ]);
    }

    /**
     * Update the specified resource in storage
     */
    public function update(Request $request, $id)
    {
        $this->authorize('access', 'employee-hour.update');
        DB::beginTransaction();
        try {
            $employeeHours = EmployeeHours::findOrFail($id);
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'employee_id' => 'required',
                'company_id' => 'required|exists:company,id',
                'role_id' => 'required|exists:employee_roles,id',
                'total_hours' => 'required|numeric|min:0',
                'pay_rate' => 'required|numeric|min:0',
                'tips' => 'nullable|numeric|min:0',
                'mileage_excess' => 'nullable|numeric|min:0',
                'incentive' => 'nullable|numeric|min:0',
                'bonus' => 'nullable|numeric|min:0',
                'pay_type' => 'required|in:HR,WK',
            ]);

            if ($validator->fails()) {
                return to_json([
                    'saved' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $check_amount = PayrollCheckAmount::where('employee_id', $employeeHours->employee_id)->where('role_id', $employeeHours->role_id)->where('eow',  Carbon::parse($employeeHours->date)->endOfWeek()->toDateString())->first();

            if ($check_amount) {
                return to_json([
                    'saved' => false,
                    'message' => 'Employee hours has already been reviewed for this week',
                ], 400);
            }

            $employee = Employee::where('employee_id', $request->employee_id)
                ->orWhere('pos_name', $request->employee_name)
                ->orWhereHas('aliases', fn ($a) => $a->where('alias_name', $request->employee_name))
                ->first();

            $input = $request->all();
            $nameMatches = $employee && in_array($request->employee_name, $employee->allMatchNames(), true);
            if (isset($employee) && $employee->employee_id != $request->employee_id && $nameMatches) {
                $input['employee_type'] = 'Existing';
                $employee = $this->employeeService->createEmployee($input);
            } else if (isset($employee) && $employee->employee_id == $request->employee_id && !$nameMatches) {
                $input['employee_type'] = 'Existing';
                $employee = $this->employeeService->createEmployee($input);
            } else if (!isset($employee)) {
                $input['employee_type'] = 'New';
                $employee = $this->employeeService->createEmployee($input);
            }
            $input['employee_id'] = $employee['id'];

            $employee_rates = EmployeeRates::where('employee_id', $employee['id'])->get();

            if (collect($employee_rates)->where('role_id', $request->role_id)->where('pay_type', $request->pay_type)->isEmpty()) {
                $employee_rates = $this->employeeService->createEmployeeRates($input);
            }

            $employeeHours->update($input);

            DB::commit();

            return to_json([
                'message' => 'Employee hours updated successfully',
                'saved' => true,
                'id' => $employeeHours->id,
            ]);
        } catch (\Exception $e) {
            // dd($e);
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Employee hours update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy($id)
    {
        $this->authorize('access', 'employee-hour.delete');
        try {
            $employeeHours = EmployeeHours::findOrFail($id);
            $employeeHours->delete();

            return to_json([
                'message' => 'Employee hours deleted successfully',
                'deleted' => true,
            ]);
        } catch (\Exception $e) {
            return to_json([
                'deleted' => false,
                'message' => 'Employee hours deletion failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove multiple resources from storage
     */
    public function destroyMultiple(Request $request)
    {
        $this->authorize('access', 'employee-hour.delete');

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:employee_hours,id',
        ]);

        if ($validator->fails()) {
            return to_json([
                'deleted' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $ids = $request->ids;
            $deletedCount = EmployeeHours::whereIn('id', $ids)->delete();

            DB::commit();

            return to_json([
                'message' => "Successfully deleted {$deletedCount} employee hour(s)",
                'deleted' => true,
                'count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Employee hours deletion failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search employee hours
     */
    public function search()
    {
        $employeeHours = EmployeeHours::with(['employee', 'company', 'role'])->filter();

        return to_json([
            'collection' => $employeeHours,
        ]);
    }

    /**
     * Get employee hours by date range
     */
    public function getByDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'employee_id' => 'nullable|exists:employee,id',
            'company_id' => 'nullable|exists:company,id',
        ]);

        $query = EmployeeHours::with(['employee', 'company', 'role'])
            ->whereBetween('date', [$request->start_date, $request->end_date]);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        $employeeHours = $query->get();

        return to_json([
            'collection' => $employeeHours,
        ]);
    }

    /**
     * Upload employee hours
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|' . upload_max_file_size_rule(),
        ]);

        try {
            DB::beginTransaction();
            $file = $request->file('file');
            $file->store('uploads/employee-hours', 'public');
            $path = $file->getRealPath();
            $extension = $file->getClientOriginalExtension();

            $sheetData = [];

            // Parse based on file type
            if (strtolower($extension) === 'csv') {
                // Parse CSV file
                $sheetData = $this->parseCsvFile($path);
            } else {
                // Parse Excel file (xlsx, xls)
                $sheetData = $this->parseExcelFile($path);
            }

            $employees = Employee::with(['employeeRates', 'aliases'])->get();

            // Use composite key (employee_id + name) to handle duplicates
            $employeeMap = [];
            foreach ($employees as $emp) {
                $ids = $emp->allMatchIds();
                $names = $emp->allMatchNames();
                foreach ($ids as $id) {
                    foreach ($names as $name) {
                        $employeeMap[$id . '|' . $name] = $emp;
                    }
                }
            }

            $roles = EmployeeRoles::authorizedWorkgroup()->get();
            $rolesByCode = $roles->keyBy('code');


            $companies = Company::authorizedWorkgroup()->get()->keyBy('store_number');
            $formats = ['m/d/y', 'm/d/Y'];

            foreach ($formats as $format) {
                try {
                    $pay_date = Carbon::createFromFormat($format, $sheetData[0]['Pay Date'])
                        ->format('Y-m-d');
                    break; // success
                } catch (\Exception $e) {
                    // try next format
                }
            }
            if (!$pay_date) {
                return to_json([
                    'message' => 'Invalid pay date format',
                    'uploaded' => false,
                ], 400);
            }

            // Track existing employee hours using composite key
            $existingEmployeeHours = [];
            $employeeHours = EmployeeHours::with('employee')->get();
            foreach ($employeeHours as $empHour) {
                if ($empHour->employee) {
                    $compositeKey = $empHour->employee->employee_id . '|' . $empHour->employee_name . '|' . $empHour->date;
                    $existingEmployeeHours[$compositeKey] = true;
                }
            }

            $employee_hours = [];
            $employee_rates = [];
            $roles_data = [];
            $missing_employees = [];

            foreach ($sheetData as $row) {
                if (!isset($rolesByCode[$row['Role Code']]) && !isset($roles_data[$row['Role Code']])) {
                    $roles_data[$row['Role Code']] = [
                        'name' => $row['Employee Role'] ? $row['Employee Role'] : $row['Role Code'],
                        'code' => $row['Role Code'],
                        'active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if (count($roles_data) > 0) {
                EmployeeRoles::insert($roles_data);
            }

            $roles = EmployeeRoles::authorizedWorkgroup()->get();
            $rolesByCode = $roles->keyBy('code');


            $missing_companies = [];


            $employeeRatesMap = [];
            $new_employees = [];
            $existing_employees = [];

            foreach ($employees as $emp) {
                foreach ($emp->employeeRates as $rate) {
                    $employeeRatesMap[$emp->employee_id][$rate->role_id][$rate->pay_type] = $rate;
                }
            }
            foreach ($sheetData as $row) {

                if($row['Pay Type'] != 'HR' ) {
                    $row['Pay Type'] = 'WK';
                }

                $pay_date = null;
                foreach ($formats as $format) {
                    try {
                        $pay_date = Carbon::createFromFormat($format, $row['Pay Date'])
                            ->format('Y-m-d');
                        break; // success
                    } catch (\Exception $e) {
                        // try next format
                    }
                }
                if (!$pay_date) {
                    return to_json([
                        'message' => 'Invalid pay date format at row ' . $row['Row'],
                        'uploaded' => false,
                    ], 400);
                }

                $compositeKey = $row['Employee Id'] . '|' . $row['Name'] ;

                // Skip if this employee hour already exists for this date
                if (isset($existingEmployeeHours[$compositeKey . '|' . $pay_date])) {
                    continue;
                }

                if (!isset($companies[$row['Store']])) {
                    $missing_companies[] = $row['Store'];
                    continue;
                }

                $employee_type = null;

                $formats = ['m/d/y', 'm/d/Y'];



                // Check if employee exists using composite key
                $existingEmployee = $employeeMap[$compositeKey] ?? null;
                if ($existingEmployee && $existingEmployee->termination_date != null) {
                    continue;
                }

                if ($existingEmployee) {
                    // Employee exists - add hours and rates
                    if (!isset($employeeRatesMap[$existingEmployee->employee_id][$rolesByCode[$row['Role Code']]['id']][$row['Pay Type']])) {
                        $employee_rates[] = [
                            'employee_id' => $existingEmployee->id,
                            'role_id' => $rolesByCode[$row['Role Code']]['id'],
                            'pay_type' => $row['Pay Type'],
                            'rate' => $row['Pay Rate'],
                            'rate_type' => '1099 Slab',
                            'slab_first_hours'=>40,
                            'slab_rest_rate'=>$row['Pay Rate'],
                            'check_payment_type'=>'percentage',
                            'check_payment_amount'=>100,
                            'effective_date' => $pay_date,
                        ];
                    }

                    $employee_hours[] = [
                        'date' => $pay_date,
                        'employee_id' => $existingEmployee->id,
                        'employee_name' => $row['Name'],
                        'company_id' => $companies[$row['Store']]['id'],
                        'dev_id' => $row['Dev Id'],
                        'ssn' => $row['SSN'],
                        'pay_id' => $row['Pay Id'],
                        'pay_type' => $row['Pay Type'],
                        'total_hours' => $row['Total Hours'],
                        'tips' => $row['Tips'],
                        'mileage_excess' => $row['Mileage Excess'],
                        'incentive' => $row['Incentive'],
                        'bonus' => $row['Bonus'],
                        'home_store' => $row['Home Store'],
                        'role_id' => $rolesByCode[$row['Role Code']]['id'],
                        'pay_rate' => $row['Pay Rate'],
                        'tips_due' => isset($row['Tips Due']) ? $row['Tips Due'] : 0,
                        'mileage_due' => isset($row['Mileage Due']) ? $row['Mileage Due'] : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } else {
                    // Employee doesn't exist - determine if New or Existing type
                    $employee_type = 'New';
                }

                $emp_rate = [
                    'employee_id' => $row['Employee Id'],
                    'role_id' => $rolesByCode[$row['Role Code']]['id'],
                    'pay_type' => $row['Pay Type'],
                    'rate' => $row['Pay Rate'],
                    'rate_type' => '1099 Slab',
                    'slab_first_hours'=>40,
                    'slab_rest_rate'=>$row['Pay Rate'],
                    'check_payment_type'=>'percentage',
                    'check_payment_amount'=>100,
                    'effective_date' => $pay_date
                ];
                $emp_hour = [
                    'date' => $pay_date,
                    'employee_id' => $row['Employee Id'],
                    'employee_name' => $row['Name'],
                    'company_id' => $companies[$row['Store']]['id'],
                    'dev_id' => $row['Dev Id'],
                    'ssn' => $row['SSN'],
                    'pay_id' => $row['Pay Id'],
                    'pay_type' => $row['Pay Type'],
                    'total_hours' => $row['Total Hours'],
                    'tips' => $row['Tips'],
                    'mileage_excess' => $row['Mileage Excess'],
                    'incentive' => $row['Incentive'],
                    'bonus' => $row['Bonus'],
                    'home_store' => $row['Home Store'],
                    'role_id' => $rolesByCode[$row['Role Code']]['id'],
                    'pay_rate' => $row['Pay Rate'],
                    'tips_due' => isset($row['Tips Due']) ? $row['Tips Due'] : 0,
                    'mileage_due' => isset($row['Mileage Due']) ? $row['Mileage Due'] : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (isset($missing_employees[$row['Employee Id'] . '-' . $row['Name']])) {
                    $missing_employees[$row['Employee Id'] . '-' . $row['Name']]['employee_rates'][] = $emp_rate;
                    $missing_employees[$row['Employee Id'] . '-' . $row['Name']]['employee_hours'][] = $emp_hour;
                } else if ($employee_type) {
                    $missing_employees[$row['Employee Id'] . '-' . $row['Name']] = [
                        'employee_id' => $row['Employee Id'],
                        'pos_name' => $row['Name'],
                        'check_name' => $row['Name'],
                        'company_id' => $companies[$row['Store']]['id'],
                        'active' => true,
                        'employee_type' => 'Completed',
                        'ssn' => $row['SSN'],
                        'hire_date' => $pay_date,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'employee_rates' => [$emp_rate],
                        'employee_hours' => [$emp_hour],
                    ];
                    if ($employee_type == 'New') {
                        $new_employees[] = [
                            'employee_id' => $row['Employee Id'],
                            'name' => $row['Name'],
                        ];
                    }
                    if ($employee_type == 'Existing') {
                        $existing_employees[] = [
                            'employee_id' => $row['Employee Id'],
                            'name' => $row['Name'],
                        ];
                    }
                }
            }
            if (count($missing_employees) > 0) {
                Employee::insert(collect($missing_employees)->map(function ($employee) {
                    return [
                        'employee_id' => $employee['employee_id'],
                        'pos_name' => $employee['pos_name'],
                        'check_name'=> $employee['check_name'],
                        'company_id' => $employee['company_id'],
                        'active' => $employee['active'],
                        'employee_type' => $employee['employee_type'],
                        'ssn' => $employee['ssn'],
                        'hire_date' => $employee['hire_date'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray());
            }

            // Map newly created employees by composite key
            $newlyCreatedEmployees = [];
            // $newEmployees = Employee::whereIn('employee_type', ['New', 'Existing'])->where('hire_date', $pay_date)->get();
            $newEmployees = Employee::whereIn('employee_type', ['Completed'])->get();
            foreach ($newEmployees as $emp) {
                $compositeKey = $emp->employee_id . '|' . $emp->pos_name;
                $newlyCreatedEmployees[$compositeKey] = $emp;
            }

            foreach ($missing_employees as $key => $missing_employee) {
                $compositeKey = $missing_employee['employee_id'] . '|' . $missing_employee['pos_name'];
                $employee = $newlyCreatedEmployees[$compositeKey] ?? null;

                if ($employee) {
                    foreach ($missing_employee['employee_hours'] as $employee_hour) {
                        $employee_hours[] = array_merge($employee_hour, [
                            'employee_id' => $employee->id,
                        ]);
                    }
                    foreach ($missing_employee['employee_rates'] as $employee_rate) {
                        $employee_rates[] = array_merge($employee_rate, [
                            'employee_id' => $employee->id,
                        ]);
                    }
                }
            }

            if (count($employee_rates) > 0) {
                EmployeeRates::insert($employee_rates);
            }

            if (count($employee_hours) > 0) {
                foreach (array_chunk($employee_hours, 1000) as $chunk) {
                    EmployeeHours::insert($chunk);
                }
            }
            DB::commit();
            return to_json([
                'message' => 'Employee hours uploaded successfully',
                'uploaded' => true,
                'rows_processed' => count($sheetData),
                'missing_companies' => array_unique($missing_companies),
                'new_employees' => $new_employees,
                'existing_employees' => $existing_employees,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee Hours Upload Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return to_json([
                'message' => 'Employee hours upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse CSV file and return array of data
     */
    private function parseCsvFile($path)
    {
        $data = [];
        $headers = [];

        if (($handle = fopen($path, 'r')) !== false) {
            $rowIndex = 0;

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if ($rowIndex === 0) {
                    // First row as headers
                    $headers = array_map('trim', $row);
                } else {
                    // Map data with headers
                    $rowData = [];
                    foreach ($row as $index => $value) {
                        $header = $headers[$index] ?? "column_$index";
                        $rowData[$header] = trim($value);
                    }
                    $data[] = $rowData;
                }
                $rowIndex++;
            }

            fclose($handle);
        }

        return $data;
    }

    /**
     * Parse Excel file and return array of data
     * Note: This requires phpoffice/phpspreadsheet package
     */
    private function parseExcelFile($path)
    {
        // Check if PhpSpreadsheet is available
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \Exception('PhpSpreadsheet library not installed. Please run: composer require phpoffice/phpspreadsheet');
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $data = [];
            $headers = [];

            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    // First row as headers
                    $headers = array_map('trim', $row);
                } else {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Map data with headers
                    $rowData = [];
                    foreach ($row as $cellIndex => $value) {
                        $header = $headers[$cellIndex] ?? "column_$cellIndex";
                        $rowData[$header] = $value !== null ? trim((string)$value) : '';
                    }
                    $data[] = $rowData;
                }
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Excel parsing error', [
                'error' => $e->getMessage(),
                'file' => $path
            ]);
            throw new \Exception('Failed to parse Excel file: ' . $e->getMessage());
        }
    }
}
