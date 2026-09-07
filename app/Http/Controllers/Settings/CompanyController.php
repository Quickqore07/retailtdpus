<?php

namespace App\Http\Controllers\Settings;

use App\Models\Settings\Company;
use App\Models\Settings\State;
use App\Models\Settings\Region;
use App\Models\Settings\County;
use App\Models\Settings\Area;
use App\Models\Settings\Workgroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll\EmployeeHours;
use App\Models\Settings\CompanyGroup;
use App\Models\Settings\Ledger;
use App\Models\Settings\LedgerDetails;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class CompanyController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'company.index');
        $companies = Company::with(['workgroup', 'state', 'region', 'county', 'area'])->authorizedCompanies('id')->filter();

        return to_json([
            'collection' => $companies,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'company.create');
        $item = [
            'name' => '',
            'store_number' => '',
            'password' => '',
            'workgroup_id' => null,
            'workgroup' => null,
            'state_id' => null,
            'state' => null,
            'region_id' => null,
            'region' => null,
            'county_id' => null,
            'county' => null,
            'area_id' => null,
            'area' => null,
            'address' => '',
            'email' => '',
            'contact_person' => '',
            'contact_number' => '',
            'website' => '',
            'employer_identification_number' => '',
            'payroll_start_date' => null,
            'payroll_frequency' => null,
            'trash_frequency' => null,
            'tax' => null,
            'st_number' => '',
            'pin' => '',
            'payroll_percentage' => null,
            'sales_tax_percentage' => null,
            'active' => true,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'company.create');
        
        
        $request->validate([
            'name' => 'required|string|max:255',
            'store_number' => 'required|string|max:255|unique:company,store_number',
            'workgroup_id' => 'required|integer|exists:workgroup,id',
            'state_id' => 'required|integer|exists:state,id',
            'region_id' => 'nullable|integer|exists:region,id',
            'county_id' => 'nullable|integer|exists:county,id',
            'area_id' => 'nullable|integer|exists:area,id',
            'email' => 'nullable|email|max:255',
            'contact_number' => 'nullable|string|max:20',
            'payroll_start_date' => 'nullable|date',
            'payroll_frequency' => 'nullable|in:Weekly,Bi-Weekly',
            'trash_frequency' => 'nullable|in:1,2,3',
            'tax' => 'nullable|in:Monthly,Quarterly,No Tax',
            'payroll_percentage' => 'nullable|integer|min:0|max:100',
            'sales_tax_percentage' => 'nullable|integer|min:0|max:100',
            'active' => 'boolean',
        ]);
        
        $data = $request->all();
        $data['active'] = $request->active ?? true;
        
        DB::beginTransaction();
        try{
            $item = Company::create($data);
            $ledgers = Ledger::get();
            $ledgerDetails = array();
            foreach($ledgers as $key => $ledger){
                $ldetails = $ledger->ledgerDetails()->latest()->first();
                $ledgerDetails[] = [
                    'company_id' => $item->id,
                    'ledger_id' => $ledger->id,
                    'code' => $ldetails->code,
                    'account_no' =>  $ldetails->account_no,
                    'bank_name' => $ldetails->bank_name,
                    'bank_address' => $ldetails->bank_address,
                    'transition_code' =>  $ldetails->transition_code,
                    'routing' => $ldetails->routing,
                    'starting_check_number' => $ldetails->starting_check_number,
                    'default_bank' => $ldetails->default_bank,
                ];
            }
            LedgerDetails::insert($ledgerDetails);
            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $item->id,
                'message' => 'Company created successfully',
            ]);
        }catch(\Exception $e){
            DB::rollBack();
            dd($e);
            return to_json([
                'saved' => false,
                'message' => 'Company creation failed',
            ], 500);
        }
      
    }

    public function show($id)
    {
        $this->authorize('access', 'company.show');
        $item = Company::with(['workgroup', 'state', 'region', 'county', 'area'])->authorizedCompanies('id',false)->findOrFail($id);
        if(!$item){
            return to_json([
                'model' => null,
                'message' => 'Company not found',
            ], 404);
        }

        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'company.update');
        $item = Company::with(['workgroup', 'state', 'region', 'county', 'area'])->authorizedCompanies('id',false)->findOrFail($id);
        if(!$item){
            return to_json([
                'model' => null,
                'message' => 'Company not found',
            ], 404);
        }

        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'company.update');

        $request->validate([
            'name' => 'required|string|max:255',
            'store_number' => 'required|string|max:255|unique:company,store_number,' . $id,
            'workgroup_id' => 'required|integer|exists:workgroup,id',
            'state_id' => 'required|integer|exists:state,id',
            'region_id' => 'nullable|integer|exists:region,id',
            'county_id' => 'nullable|integer|exists:county,id',
            'area_id' => 'nullable|integer|exists:area,id',
            'email' => 'nullable|email|max:255',
            'contact_number' => 'nullable|string|max:20',
            'payroll_start_date' => 'nullable|date',
            'payroll_frequency' => 'nullable|in:Weekly,Bi-Weekly',
            'trash_frequency' => 'nullable|in:1,2,3',    
            'tax' => 'nullable|in:Monthly,Quarterly,No Tax',
            'payroll_percentage' => 'nullable|integer|min:0|max:100',
            'sales_tax_percentage' => 'nullable|integer|min:0|max:100',
            'active' => 'boolean',
        ]);

        $item = Company::authorizedCompanies('id',false)->findOrFail($id);
        if(!$item){
            return to_json([
                'model' => null,
                'message' => 'Company not found',
            ], 404);
        }
        
        $data = $request->all();

        $data['active'] = $request->active ?? true;

        $item->fill($data);
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Company updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'company.delete');
        $item = Company::authorizedCompanies('id',false)->findOrFail($id);
        if(!$item){
            return to_json([
                'model' => null,
                'message' => 'Company not found',
            ], 404);
        }

        if(EmployeeHours::where('company_id', $id)->count() > 0 || Employee::where('company_id', $id)->count() > 0) {
            return to_json([
                'deleted' => false,
                'message' => 'Company has employees, cannot delete'
            ]);
        }
        
        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Company deleted successfully'
        ]);
    }

    

    public function search()
    {
        $search = is_array(request('query')) ? null : request('query');
        $column = is_array(request('column')) ? 'name' : (request('column') ?? 'name');
        $employee_id = request('employee_id');
        $workgroup_id = request('workgroup_id');
        $workgroup_skip = request('workgroup_skip');
        $search_for_group = request('search_for_group');
        $group_id = request('group_id');
        
        $companies = Company::with(['workgroup', 'state', 'region', 'county', 'area'])->authorizedCompanies('company.id',!$workgroup_id && !$workgroup_skip)
            ->where(function ($query) use ($column, $search) {
                if($search && is_string($search) && is_string($column)){
                    return $query->where($column, 'like', '%' . $search . '%')->orWhere('store_number', 'like', '%' . $search . '%');
                }
                return $query;
            })
            ->when($workgroup_id, function ($query) use ($workgroup_id) {
                return $query->where('workgroup_id', $workgroup_id);
            })->when($employee_id, function ($query) use ($employee_id) {
                return $query->join('employee_rates', 'employee_rates.company_id', '=', 'company.id')
                ->where('employee_rates.employee_id', $employee_id);
            })
            ->when($search_for_group !== null, function ($query) use ($group_id) {
               $companyGroups = CompanyGroup:: when($group_id, function ($query) use ($group_id) {
                return $query->where('id', '!=', $group_id);
               })->pluck('companies')->flatten()->toArray();
               return $query->whereNotIn('id', $companyGroups);
            })
            ->selectRaw('company.id, concat(company.store_number, " - ", company.name) as name,company.store_number as store_number, company.workgroup_id')
            ->get();
            
        return to_json([
            'collection' => $companies
        ]);
    }
    public function getCompanies()
    {
        $workgroup_id = request('workgroup_id');
        $companies = Company::authorizedCompanies('id')->get();
        if ($workgroup_id) {
            $companies = $companies->where('workgroup_id', $workgroup_id);
        }
        return to_json([
            'collection' => $companies,
            'message' => 'Companies fetched successfully'
        ]);
    }
    public function getCurrentCompany(Request $request)
    {
        
        $company = Company::with(['workgroup'])->authorizedCompanies('id')->find($request->session()->get('company'));
        if(!$company){
            return to_json([
                'model' => null,
                'message' => 'Company not found',
            ], 404);
        }
        $company->name = $company->store_number . ' - ' . $company->name;
        return to_json([
            'success' => true,
            'company' => $company,
        ]);
    }

    public function switchCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer|exists:company,id',
        ]);

        $company = Company::with(['workgroup', 'state', 'region', 'county', 'area'])->authorizedCompanies('id',false)->findOrFail($request->company_id);
        if(!$company){
            return to_json([
                'model' => null,
                'message' => 'Company not found',
            ], 404);
        }
        
        // Set the company in the session
        $request->session()->put('company', $request->company_id);
        $request->session()->put('workgroup', $company->workgroup_id);


        return to_json([
            'success' => true,
            'company' => $company,
            'message' => 'Company switched successfully',
        ]);
    }

    public function downloadTemplate()
    {
        $this->authorize('access', 'company.create');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'Store Number',
            'Company Name',
            'Workgroup Name',
            'State Name',
            'Region Name',
            'County Name',
            'Area Name',
            'Email',
            'Contact Person',
            'Contact Number',
            'Address',
            'Website',
            'Employer ID Number',
            'Payroll Start Date',
            'Payroll Frequency',
            'Tax',
            'ST Number',
            'PIN',
            'Payroll Percentage',
            'Sales Tax Percentage',
            'Active'
        ];

        // Style the header row
        $headerRange = 'A1:U1';
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

        // Add sample data row
        $sampleData = [
            'ST001',
            'Sample Company',
            'Sample Workgroup',
            'Sample State',
            'Sample Region',
            'Sample County',
            'Sample Area',
            'sample@example.com',
            'John Doe',
            '123-456-7890',
            '123 Main St',
            'https://example.com',
            'EIN123456',
            '2026-01-01',
            'Weekly',
            'Monthly',
            'ST12345',
            'PIN12345',
            '10',
            '5',
            'Yes'
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        // Set column widths
        foreach (range('A', 'U') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add instructions sheet
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Instructions');
        $instructions = [
            ['COMPANY IMPORT TEMPLATE - INSTRUCTIONS'],
            [''],
            ['Column Details:'],
            ['Store Number', 'Required. Unique identifier for the company. If exists, record will be updated.'],
            ['Company Name', 'Required. Name of the company.'],
            ['Workgroup Name', 'Required. Must match existing workgroup name exactly.'],
            ['State Name', 'Required. Must match existing state name exactly.'],
            ['Region Name', 'Required. Must match existing region name exactly.'],
            ['County Name', 'Optional. Must match existing county name exactly.'],
            ['Area Name', 'Required. Must match existing area name exactly.'],
            ['Email', 'Optional. Valid email address.'],
            ['Contact Person', 'Optional. Contact person name.'],
            ['Contact Number', 'Optional. Phone number.'],
            ['Address', 'Optional. Company address.'],
            ['Website', 'Optional. Company website URL.'],
            ['Employer ID Number', 'Optional. EIN number.'],
            ['Payroll Start Date', 'Optional. Format: YYYY-MM-DD'],
            ['Payroll Frequency', 'Optional. Values: Weekly or Bi-Weekly'],
            ['Tax', 'Optional. Values: Monthly, Quarterly, or No Tax'],
            ['ST Number', 'Optional. State tax number.'],
            ['PIN', 'Optional. Personal identification number.'],
            ['Payroll Percentage', 'Optional. Number between 0-100.'],
            ['Sales Tax Percentage', 'Optional. Number between 0-100.'],
            ['Active', 'Optional. Values: Yes or No (default: Yes)'],
            [''],
            ['Important Notes:'],
            ['- Remove the sample data row before importing your data'],
            ['- State, Region, Area, and Workgroup must already exist in the system'],
            ['- Names are case-sensitive and must match exactly'],
            ['- If Store Number exists, the record will be updated; otherwise, a new record is created'],
            ['- Required fields cannot be empty'],
            ['- Date format must be YYYY-MM-DD (e.g., 2026-01-30)'],
        ];
        $instructionSheet->fromArray($instructions, null, 'A1');
        $instructionSheet->getColumnDimension('A')->setWidth(30);
        $instructionSheet->getColumnDimension('B')->setWidth(80);
        $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instructionSheet->getStyle('A3')->getFont()->setBold(true);
        $instructionSheet->getStyle('A25')->getFont()->setBold(true);

        // Set active sheet back to template
        $spreadsheet->setActiveSheetIndex(0);

        // Generate file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'company_import_template_' . date('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function export()
    {
        $this->authorize('access', 'company.index');

        $companies = Company::with(['workgroup', 'state', 'region', 'county', 'area'])->authorizedCompanies('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'Store Number',
            'Company Name',
            'Workgroup Name',
            'Payroll ID',
            'State Name',
            'Region Name',
            'County Name',
            'Area Name',
            'Email',
            'Contact Person',
            'Contact Number',
            'Address',
            'Website',
            'Employer ID Number',
            'Payroll Start Date',
            'Payroll Frequency',
            'Tax',
            'ST Number',
            'PIN',
            'Payroll Percentage',
            'Sales Tax Percentage',
            'Active'
        ];

        // Style the header row
        $headerRange = 'A1:V1';
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

        // Add company data
        $rowNumber = 2;
        foreach ($companies as $company) {
            $data = [
                $company->store_number,
                $company->name,
                $company->workgroup->name ?? '',
                $company->payroll_id ?? '',
                $company->state->name ?? '',
                $company->region->name ?? '',
                $company->county->name ?? '',
                $company->area->name ?? '',
                $company->email ?? '',
                $company->contact_person ?? '',
                $company->contact_number ?? '',
                $company->address ?? '',
                $company->website ?? '',
                $company->employer_identification_number ?? '',
                $company->payroll_start_date ? date('Y-m-d', strtotime($company->payroll_start_date)) : '',
                $company->payroll_frequency ?? '',
                $company->tax ?? '',
                $company->st_number ?? '',
                $company->pin ?? '',
                $company->payroll_percentage ?? '',
                $company->sales_tax_percentage ?? '',
                $company->active ? 'Yes' : 'No'
            ];
            $sheet->fromArray($data, null, 'A' . $rowNumber);
            $rowNumber++;
        }

        // Set column widths
        foreach (range('A', 'V') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Generate file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'companies_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        $this->authorize('access', 'company.create');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|' . upload_max_file_size_rule(),
        ]);
        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            $currentWorkgroup = $request->session()->get('workgroup');

            // Remove header row
            array_shift($rows);

            $imported = 0;
            $updated = 0;
            $errors = [];
            $rowNumber = 2; // Start from 2 (1 is header)


            $workgroups = Workgroup::get()->keyBy('name');
            $states = State::get()->keyBy('name');
            $regions = Region::get()->keyBy('name');
            $counties = County::get()->groupBy('state_id')->map(function($items) {
                return $items->keyBy('name');
            });
            $areas = Area::get()->keyBy('name');

            $companies =[];

            foreach ($rows as $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    $rowNumber++;
                    continue;
                }
                try {
                    // Extract data from row
                    $storeNumber = $row[0] ?? null;
                    $name = $row[1] ?? null;
                    $workgroupName = $row[2] ?? null;
                    $payrollId = $row[3] ?? null;
                    $stateName = $row[4] ?? null;
                    $regionName = $row[5] ?? null;
                    $countyName = $row[6] ?? null;
                    $areaName = $row[7] ?? null;
                    $email = $row[8] ?? null;
                    $contactPerson = $row[9] ?? null;
                    $contactNumber = $row[10] ?? null;
                    $address = $row[11] ?? null;
                    $website = $row[12] ?? null;
                    $employerIdNumber = $row[13] ?? null;
                    $payrollStartDate = $row[14] ?? null;
                    $payrollFrequency = $row[15] ?? null;
                    $tax = $row[16] ?? null;
                    $stNumber = $row[17] ?? null;
                    $pin = $row[18] ?? null;
                    $payrollPercentage = $row[19] ?? null;
                    $salesTaxPercentage = $row[20] ?? null;
                    $active = $row[21] ?? 'Yes';

                    // Validate required fields
                    if (empty($storeNumber) || empty($name) || empty($workgroupName) 
                    // ||empty($stateName) || empty($regionName) || empty($areaName)
                    ) {
                        $errors[] = "Row {$rowNumber}: Missing required fields (Store Number, Name, Workgroup, State, Region, or Area)";
                        $rowNumber++;
                        continue;
                    }

                    // Find related records by name
                    $workgroup = $workgroups[$workgroupName] ?? null;
                    if (!$workgroup) {
                        $errors[] = "Row {$rowNumber}: Workgroup '{$workgroupName}' not found";
                        $rowNumber++;
                        continue;
                    }

                    $state = $states[$stateName] ?? null;
                    // if (!$state) {
                    //     $errors[] = "Row {$rowNumber}: State '{$stateName}' not found";
                    //     $rowNumber++;
                    //     continue;
                    // }

                    $region = $regions[$regionName] ?? null;
                    // if (!$region || $region->state_id != $state->id) {
                    //     $errors[] = "Row {$rowNumber}: Region '{$regionName}' not found in state '{$stateName}'";
                    //     $rowNumber++;
                    //     continue;
                    // }

                    $area = $areas[$areaName] ?? null;
                    // if (!$area || $area->region_id != $region->id) {
                    //     $errors[] = "Row {$rowNumber}: Area '{$areaName}' not found in region '{$regionName}'";
                    //     $rowNumber++;
                    //     continue;
                    // }

                    $county = null;
                    if (!empty($countyName) && $state) {
                        $county = $counties[$state->id][$countyName] ?? null;
                        // if (!$county) {
                        //     $errors[] = "Row {$rowNumber}: County '{$countyName}' not found in state '{$stateName}'";
                        //     $rowNumber++;
                        //     continue;
                        // }
                    }

                    // Prepare data
                    $data = [
                        'name' => $name,
                        'store_number' => $storeNumber,
                        'workgroup_id' => $workgroup->id,
                        'payroll_id' => $payrollId,
                        'state_id' => $state ? $state->id : null,
                        'region_id' => $region ? $region->id : null,
                        'county_id' => $county ? $county->id : null,
                        'area_id' => $area ? $area->id : null,
                        'email' => $email,
                        'contact_person' => $contactPerson,
                        'contact_number' => $contactNumber,
                        'address' => $address,
                        'website' => $website,
                        'employer_identification_number' => $employerIdNumber,
                        'payroll_start_date' => $payrollStartDate,
                        'payroll_frequency' => $payrollFrequency,
                        'tax' => $tax,
                        'st_number' => $stNumber,
                        'pin' => $pin,
                        'payroll_percentage' => $payrollPercentage ? (int)$payrollPercentage : null,
                        'sales_tax_percentage' => $salesTaxPercentage ? (int)$salesTaxPercentage : null,
                        'active' => strtolower($active) === 'yes' || strtolower($active) === 'true' || $active === '1',
                    ];

                    // Validate data
                    $validator = Validator::make($data, [
                        'name' => 'required|string|max:255',
                        'store_number' => 'required|string|max:255',
                        'workgroup_id' => 'required|integer|exists:workgroup,id',
                        'payroll_id' => 'nullable|string|max:255',
                        'state_id' => 'nullable|integer|exists:state,id',
                        'region_id' => 'nullable|integer|exists:region,id',
                        'county_id' => 'nullable|integer|exists:county,id',
                        'area_id' => 'nullable|integer|exists:area,id',
                        'email' => 'nullable|email|max:255',
                        'contact_number' => 'nullable|string|max:20',
                        'payroll_start_date' => 'nullable|date',
                        'payroll_frequency' => 'nullable|in:Weekly,Bi-Weekly',
                        'tax' => 'nullable|in:Monthly,Quarterly,No Tax',
                        'payroll_percentage' => 'nullable|integer|min:0|max:100',
                        'sales_tax_percentage' => 'nullable|integer|min:0|max:100',
                        'active' => 'boolean',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                        $rowNumber++;
                        continue;
                    }

                    // Check if company exists by store_number
                    $existingCompany = Company::where('store_number', $storeNumber)->first();

                    if ($existingCompany) {
                        // Update existing company
                        $existingCompany->update($data);
                        $updated++;
                    } else {
                        // Create new company
                        $data['created_at'] = now();
                        $data['updated_at'] = now();
                        $data['created_by'] = Auth::id();
                        $data['updated_by'] = Auth::id();
                        $companies[] = $data;
                        $imported++;
                    }

                } catch (\Exception $e) {
                    dd($e);
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }

                $rowNumber++;
            }
            $ledgerDetailsArray = array();
            if(count($companies) > 0) {
                Company::insert($companies);
                $ledgers = Ledger::with('ledgerDetails')->get();
                $companies = Company::whereIn('store_number', array_column($companies, 'store_number'))->get();
                foreach($ledgers as $ledger){
                    $ledgerDetails = $ledger->ledgerDetails()->latest()->first();
                    foreach($companies as $company){
                        $ledgerDetailsArray[] = [
                            'company_id' => $company->id,
                            'ledger_id' => $ledger->id,
                            'code' => $ledgerDetails->code,
                            'account_no' => null,
                            'bank_name' => $ledgerDetails->bank_name,
                            'bank_address' => $ledgerDetails->bank_address,
                            'transition_code' => $ledgerDetails->transition_code,
                            'routing' => $ledgerDetails->routing,
                            'starting_check_number' => $ledgerDetails->starting_check_number,
                            'default_bank' => $ledgerDetails->default_bank,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ];
                    }
                }
            }
            LedgerDetails::insert($ledgerDetailsArray);
            DB::commit();
            return to_json([
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors,
                'message' => "Import completed. {$imported} companies created, {$updated} companies updated" . 
                            (count($errors) > 0 ? ", " . count($errors) . " errors occurred" : ""),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return to_json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}


