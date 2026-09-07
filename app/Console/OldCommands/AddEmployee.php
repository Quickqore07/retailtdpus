<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Payroll\EmployeeHours;
use App\Models\Payroll\EmployeeRates;
use App\Models\Role;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddEmployee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:employee-and-hours-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add employee to the database';

    /**
     * Execute the console command.
     */
    protected $companyMap = [
        '163'=>70,
        '164'=>71,
        '165'=>72,
        '166'=>73,
        '167'=>74,
        '168'=>75,
        '169'=>76,
        '133'=>77,
        '170'=>78,
        '134'=>79,
        '135'=>80,
        '171'=>81,
        '138'=>82,
        '172'=>83,
        '186'=>84,
        '173'=>85,
        '174'=>86,
        '175'=>87,
        '176'=>88,
        '177'=>89,
        '187'=>90,
        '188'=>91,
        '178'=>92,
        '179'=>93,
        '180'=>94,
        '189'=>95,
        '181'=>96,
        '190'=>97,
        '182'=>98,
        '102'=>99,
        '183'=>100,
        '184'=>101,
        '185'=>102,
        '191'=>103,
        '192'=>104,
        '193'=>105,
        '194'=>106,
        '136'=>107,
        '139'=>108,
        '195'=>109,
        '196'=>110,
        '197'=>111,
        '198'=>112,
        '137'=>113,
        '103'=>114,
        '104'=>115,
        '105'=>116,
        '106'=>117,
        '107'=>118,
        '108'=>119,
        '109'=>120,
        '110'=>121,
        '111'=>122,
        '112'=>123,
        '113'=>124,
        '140'=>125,
        '141'=>126,
        '142'=>127,
        '114'=>128,
        '115'=>129,
        '116'=>130,
        '117'=>131,
        '118'=>132,
        '119'=>133,
        '120'=>134,
        '121'=>135,
        '143'=>136,
        '122'=>137,
        '123'=>138,
        '124'=>139,
        '125'=>140,
        '126'=>141,
        '144'=>142,
        '127'=>143,
        '145'=>144,
        '128'=>145,
        '129'=>146,
        '130'=>147,
        '146'=>148,
        '147'=>149,
        '131'=>150,
        '132'=>151,
        '148'=>152,
        '149'=>153,
        '150'=>154,
        '151'=>155,
        '152'=>156,
        '153'=>157,
        '154'=>158,
        '155'=>159,
        '156'=>160,
        '157'=>161,
        '158'=>162,
        '159'=>163,
        '160'=>164,
        '161'=>165,
        '162'=>166,  
    ];

    protected $employeeRoles=[
        '5'=>33,
        '6'=>34,
        '7'=>35,
        '8'=>36,
        '9'=>37,
        '10'=>38,
        '11'=>39,
        '12'=>40,
        '13'=>41,
        '14'=>42,
        '15'=>43,
        '16'=>44,
        '17'=>45,
        '18'=>46,
        '19'=>47,
    ];

    public function handle()
    {
        try {
            
            // Backup tables once, outside transaction
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::statement('DROP TABLE IF EXISTS employees_backup');
            DB::statement('DROP TABLE IF EXISTS employee_hours_backup');
            DB::statement('DROP TABLE IF EXISTS employee_rates_backup');

            DB::statement('CREATE TABLE IF NOT EXISTS employees_backup LIKE employees');
            DB::statement('INSERT INTO employees_backup SELECT * FROM employees');

            
            DB::statement('CREATE TABLE IF NOT EXISTS employee_hours_backup LIKE employee_hours');
            DB::statement('INSERT INTO employee_hours_backup SELECT * FROM employee_hours');

            
            DB::statement('CREATE TABLE IF NOT EXISTS employee_rates_backup LIKE employee_rates');
            DB::statement('INSERT INTO employee_rates_backup SELECT * FROM employee_rates');

            $this->info('Tables backed up successfully');
            
            // // Delete old entries once, outside loop
            $employeeIds = Employee::where('workgroup_id', 2)->pluck('id')->toArray();
            EmployeeHours::whereIn('employee_id', $employeeIds)->delete();
            EmployeeRates::whereIn('employee_id', $employeeIds)->delete();
            Employee::whereIn('id', $employeeIds)->delete();

            $maxId = DB::table('employee')->max('id') ?? 0;
            DB::statement("ALTER TABLE employee AUTO_INCREMENT = " . $maxId);

            $maxId = DB::table('employee_hours')->max('id') ?? 0;
            DB::statement("ALTER TABLE employee_hours AUTO_INCREMENT = " . $maxId);

            $maxId = DB::table('employee_rates')->max('id') ?? 0;
            DB::statement("ALTER TABLE employee_rates AUTO_INCREMENT = " . $maxId);

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $chunkSize = 500;
            
            DB::beginTransaction();

            $errors = [];
            $employeeRates = [];
            $companies = Company::where('workgroup_id', 2)->pluck('id','store_number')->toArray();


            DB::table('employees')
            ->orderBy('id')
            ->chunk($chunkSize, function ($employees) use (&$errors, $companies) {
    
                $employeeInserts = [];

                $insertedEntries = DB::table('employee')
                    ->where('workgroup_id',2)
                    ->select('id', 'company_id', 'employee_id', 'pos_name')
                    ->get()
                    ->keyBy(fn($e) => $e->employee_id.'|'.trim($e->pos_name));

                // Build batch insert data
                foreach ($employees as $key => $employee) {
                    $employeeName = trim($employee->name);
                    $company = $this->companyMap[$employee->company_id] ?? $companies[$employee->company_code] ?? null;
                    $emp_key = $employee->employee_id.'|'.trim($employeeName);

                    
                    if (!$company) {
                        $errors[] = "Employee {$key} company not found: {$employee->company_id}";
                        continue;
                    }
                    $hasSSN = !empty($employee->ssn);
                    if((isset($insertedEntries[$emp_key]) || isset($employeeInserts[$emp_key])) && !$hasSSN){
                        
                        continue;
                    }else if(isset($insertedEntries[$emp_key]) && $hasSSN){
                        
                        
                       Employee::where('id', $insertedEntries[$emp_key]->id)->update([
                            'company_id' => $company,
                            'employee_id' => $employee->employee_id,
                            'pos_name' => $employeeName,
                            'active' => $employee->status =='1' ? 1 : 0,
                            'employee_type' => 'Completed',
                            'hire_date' => $employee->emp_joining,
                            'termination_date' => $employee->emp_left,
                            'ssn'=> $employee->ssn ? implode('-', explode('_', $employee->ssn)) : null,
                            'workgroup_id' => 2,
                            'email'=>$employee->emailId,
                            'phone'=>$employee->contactNumber,
                            'street'=>$employee->address,
                            'city'=>$employee->city,
                            'zip'=>$employee->zip_code,
                            'state'=>$employee->state,
                            'dob'=>$employee->dob ?$employee->dob : null,
                            'emergency_contact_name'=>$employee->relative_name,
                            'emergency_contact_phone'=>$employee->relative_number,
                            'emergency_contact_relationship'=>$employee->relation,
                            'onboarding_status'=>'verified',
                            'created_at'=>$employee->created_at,
                            'updated_at'=>$employee->updated_at,
                        ]);
                        continue;
                    }else{

                       

                    // ---------------------------------------Not good practice to add 1068 to the id, but it's the only way to avoid duplicate ids ----------------
                        $employeeInserts[$emp_key] = [
                            'company_id' => $company,
                            'employee_id' => $employee->employee_id,
                            'pos_name' => $employeeName,
                            'active' => $employee->status ='1' ? 1 : 0,
                            'employee_type' => 'Completed',
                            'hire_date' => $employee->emp_joining,
                            'termination_date' => $employee->emp_left,
                            'ssn'=> $employee->ssn ? implode('-', explode('_', $employee->ssn)) : null,
                            'workgroup_id' => 2,
                            'email'=>$employee->emailId,
                            'phone'=>$employee->contactNumber,
                            'street'=>$employee->address,
                            'city'=>$employee->city,
                            'zip'=>$employee->zip_code,
                            'state'=>$employee->state,
                            'dob'=>$employee->dob ?$employee->dob : null,
                            'emergency_contact_name'=>$employee->relative_name,
                            'emergency_contact_phone'=>$employee->relative_number,
                            'emergency_contact_relationship'=>$employee->relation,
                            'onboarding_status'=>'verified',
                            'created_by'=>1,
                            'updated_by'=>1,
                            'created_at'=>$employee->created_at,
                            'updated_at'=>$employee->updated_at,
                        ];

                    }
                   

                    
                }
                if (empty($employeeInserts)) {
                    return;
                }

                DB::table('employee')->insert($employeeInserts);

                // Get the inserted IDs (by matching company_id + date in this chunk)
                
            });
            $insertedEntries = DB::table('employee')
                ->where('workgroup_id',2)
                ->select('id', 'company_id', 'employee_id', 'pos_name','ssn')
                ->get()
                ->keyBy(fn($e) => $e->employee_id.'|'.$e->ssn );



            $insertedEntriesByName = DB::table('employee')
                ->where('workgroup_id',2)
                ->select('id', 'company_id', 'employee_id', 'pos_name','ssn')
                ->get()
                ->keyBy(fn($e) => $e->employee_id.'|'.strtolower($this->normalizeName($e->pos_name)) );


            $masterPayRates = DB::table('master_pay_rates')->get();
            $employeeRates = [];
            foreach($masterPayRates as $masterPayRate){
                $company = $companies[$masterPayRate->store_code] ?? null;
                if(!$company){
                    // info('Company not found: '.$masterPayRate->store_code);
                    continue;
                }
                $employeeId = $masterPayRate->employee_id.'|'.$masterPayRate->ssn;
                $employeeIdByName = $masterPayRate->employee_id.'|'.strtolower($this->normalizeName($masterPayRate->real_name));

               
                if(isset($insertedEntries[$employeeId])){
                    $employee = $insertedEntries[$employeeId];
                }else if(isset($insertedEntriesByName[$employeeIdByName])){
                    $employee = $insertedEntriesByName[$employeeIdByName];
                }else{
                    // info('Employee not found: '.$masterPayRate->employee_id.'|'.$masterPayRate->ssn);
                    continue;
                }
                $employeeRates[] = [
                    'employee_id' => $employee->id,
                    'role_id' => $this->employeeRoles[$masterPayRate->role_id],
                    'pay_type' => $masterPayRate->employment_type == 'HR' ? 'HR' : 'WK',
                    'rate' => $masterPayRate->rate_type == 'Salary' ? ($masterPayRate->pay_rate) : $masterPayRate->pay_rate,
                    'rate_type' => 'Payroll Regular',
                    'effective_date' => $masterPayRate->start_date,
                    'payroll_type' => 'Direct Deposit',
                    'company_id' => $company,
                ];

                if($masterPayRate->ssn && !$employee->ssn){
                    Employee::where('id', $employee->id)->update(['ssn' => $masterPayRate->ssn]);
                }
            }
            EmployeeRates::insert($employeeRates);


            $employeeRoles = EmployeeRoles::where('workgroup_id', 2)->pluck('id','name')->toArray();

            $storeEmployees = Employee::select('id', 'employee_id', 'pos_name')->where('workgroup_id', 2)->get()->groupBy('employee_id');

            $index = 0;
            $totalEmployeeHours = 0;

            $ratesMap = [];

            EmployeeRates::select('employee_id', 'role_id', 'company_id', 'effective_date')
                ->chunk(1000, function ($rates) use (&$ratesMap) {
                    foreach ($rates as $rate) {
                        $key = $rate->employee_id . '_' . $rate->role_id . '_' . $rate->company_id;
                        $ratesMap[$key][] = $rate->effective_date;
                    }
                });

            DB::table('employee_hours2')->orderBy('id')->chunk(1000, function ($employeeHours) use ($storeEmployees, $employeeRoles, $companies, &$index, &$totalEmployeeHours, &$ratesMap) {
                $index++;
                $this->info('Processing chunk '.$index);
                $employeeHoursInserts = [];
                foreach ($employeeHours as $employeeHour) {
                    $company = $companies[$employeeHour->storeId] ?? null;
                    $role = $employeeRoles[$employeeHour->employeeRole] ?? null;
                    if(!$company){
                        $this->error('Company not found: '.$employeeHour->storeId);
                        continue;
                    }
                    $employees = $storeEmployees->get($employeeHour->employeeId, collect());
                    $employee = $employees->first();

                    if (!$employee) {
                        $employee = Employee::create([
                            'company_id' => $company,
                            'employee_id' => $employeeHour->employeeId,
                            'pos_name' => $employeeHour->employeeName,
                            'active' => 1,
                            'employee_type' => 'Completed',
                            'workgroup_id' => 2,
                            'onboarding_status' => 'verified',
                            'created_by' => 1,
                            'updated_by' => 1,
                        ]);
                        
                        $storeEmployees->put($employeeHour->employeeId, collect([$employee]));
                    }else{
                        if ($employee->pos_name !== $employeeHour->employeeName) {
                            $employee->pos_name = $employeeHour->employeeName;
                            $employee->updated_by = 1;
                            $employee->saveQuietly(); // no events = faster
                        }
                    }

                    $key = $employee->id . '_' . $role . '_' . $company;
                    $payPeriod = $employeeHour->payPeriod;

                    $exists = false;
                    if (isset($ratesMap[$key])) {
                        foreach ($ratesMap[$key] as $date) {
                            if ($date <= $payPeriod) {
                                $exists = true;
                                break;
                            }
                        }
                    }

                    if (!$exists) {
                        
                        EmployeeRates::updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'role_id' => $role,
                                'company_id' => $company,
                                'effective_date' => $payPeriod,
                            ],
                            [
                                'pay_type' => $employeeHour->payType === 'HR' ? 'HR' : 'WK',
                                'rate' => $employeeHour->payRate ?? 0,
                                'rate_type' => 'Payroll Regular',
                                'payroll_type' => 'Direct Deposit',
                                'created_by' => 1,
                                'updated_by' => 1,
                            ]
                        );
        
                        // update map (IMPORTANT)
                        $ratesMap[$key][] = $payPeriod;
                    }
        
                    $employeeHoursInserts[] = [
                        'date' => $employeeHour->payPeriod,
                        'employee_id' => $employee->id,
                        'employee_name' => $employeeHour->employeeName,
                        'company_id' => $company,
                        'dev_id' => $employeeHour->devId,
                        'ssn' => $employeeHour->ssn,
                        'pay_id' => $employeeHour->payId,
                        'pay_type' => $employeeHour->payType =='HR' ? 'HR' : 'WK',
                        'total_hours' => $employeeHour->totalHours ?? 0,
                        'tips' => $employeeHour->tips ?? 0,
                        'mileage_excess' => $employeeHour->mileageExcess ?? 0,   
                        'incentive' => $employeeHour->incentivePay ?? 0,
                        'bonus' => $employeeHour->bonus ?? 0,
                        'home_store' => $employeeHour->homeStore,
                        'role_id' => $role,
                        'pay_rate' => $employeeHour->payRate ?? 0,
                        'tips_due' => $employeeHour->tipsDue ?? 0,
                        'mileage_due' => $employeeHour->mileageDue ?? 0,
                        'created_at' => $employeeHour->created_at,
                        'updated_at' => $employeeHour->updated_at,
                        'created_by' => 1,
                        'updated_by' => 1,
                    ];
                }
                if(count($employeeHoursInserts) > 0){
                    EmployeeHours::insert($employeeHoursInserts);
                }
                $totalEmployeeHours += count($employeeHoursInserts);
                $this->info('Inserted '.count($employeeHoursInserts).' employee hours' . ' Total employee hours: '.$totalEmployeeHours);
                $this->info('Chunk '.$index.' completed' );
            });


            DB::commit();
            $this->info('Employee rates added successfully');
            $this->info('Employees and employee rates added successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            info($th);
            $this->error($th->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    function normalizeName($name) {
        $name = strtoupper(trim($name));
        $parts = preg_split('/\s+/', $name);
    
        if (count($parts) >= 2) {
            return $parts[0] . ' ' . end($parts); // FIRST + LAST
        }
    
        return $name;
    }
}


