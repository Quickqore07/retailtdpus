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

class AddPendingEmployee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:pending-employee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add pending employee to the database';

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

    protected $pendingEmployeeIds = [];



    public function handle()
    {
        try {
            
            // Backup tables once, outside transaction
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            // DB::statement('DROP TABLE IF EXISTS employees_backup');
            // DB::statement('DROP TABLE IF EXISTS employee_rates_backup');
            // DB::statement('DROP TABLE IF EXISTS employee_hours_backup');

            // DB::statement('CREATE TABLE IF NOT EXISTS employees_backup LIKE employees');
            // DB::statement('INSERT INTO employees_backup SELECT * FROM employees');
            
            // DB::statement('CREATE TABLE IF NOT EXISTS employee_rates_backup LIKE employee_rates');
            // DB::statement('INSERT INTO employee_rates_backup SELECT * FROM employee_rates');

            // DB::statement('CREATE TABLE IF NOT EXISTS employee_hours_backup LIKE employee_hours');
            // DB::statement('INSERT INTO employee_hours_backup SELECT * FROM employee_hours');


            $ids = DB::table('onboarding_creation_id as o')
                ->leftJoin('employee as e', 'o.employee_code', '=', 'e.employee_id')
                ->groupBy('o.id')
                ->havingRaw('COUNT(e.id) = 0')
                ->select('o.*')
                ->pluck('o.employee_code')->toArray();
            
            $this->pendingEmployeeIds = $ids;

            $this->info('Tables backed up successfully');
            
            // // Delete old entries once, outside loop
            $employeeIds = Employee::whereIn('employee_id', $this->pendingEmployeeIds)->pluck('id')->toArray();
            EmployeeRates::whereIn('employee_id', $employeeIds)->delete();
            EmployeeHours::whereIn('employee_id', $employeeIds)->delete();
            Employee::whereIn('id', $employeeIds)->delete();

            $maxId = DB::table('employee')->max('id') ?? 0;
            DB::statement("ALTER TABLE employee AUTO_INCREMENT = " . $maxId);

            $maxId = DB::table('employee_rates')->max('id') ?? 0;
            DB::statement("ALTER TABLE employee_rates AUTO_INCREMENT = " . $maxId);

            $maxId = DB::table('employee_hours')->max('id') ?? 0;
            DB::statement("ALTER TABLE employee_hours AUTO_INCREMENT = " . $maxId);

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $chunkSize = 500;
            
            DB::beginTransaction();

            $companies = Company::where('workgroup_id', 2)->pluck('id','store_number')->toArray();
            $employeeRolesMap = EmployeeRoles::where('workgroup_id', 2)->pluck('id','name')->toArray();

            $now = Carbon::now();

            DB::table('onboarding_creation_id')
            ->orderBy('id')
            ->whereIn('employee_code', $this->pendingEmployeeIds)
            ->chunk($chunkSize, function ($employees) use (&$errors, $companies) {
                $employeeInserts = [];
                foreach ($employees as $employee) {
                    $company = is_numeric($employee->company_id) && isset($this->companyMap[$employee->company_id]) ? $this->companyMap[$employee->company_id] : $companies[$employee->company_id] ?? null;
                    if(!$company){
                        continue;
                    }
                    $jsonData = json_decode($employee->json_data, true);
                    $startDate = isset($jsonData[0]['start_date']) ? $jsonData[0]['start_date'] : null;
                    $employeeInserts[$employee->employee_code] = [
                        'employee_id' => $employee->employee_code,
                        'workgroup_id' => 2,
                        'hire_date' => $startDate,
                        'company_id' => $company,
                        'pos_name' => $employee->real_name,
                        'email' => $employee->email,
                        'created_at' =>$employee->created_at,
                        'updated_at' =>$employee->updated_at,
                        'employee_type' => 'Completed',
                        'onboarding_status' => 'verified',
                        'created_by' => 1,
                        'updated_by' => 1,
                    ];
                }
                $this->info('Inserting '.count($employeeInserts).' employees');
                if(count($employeeInserts) > 0){
                    DB::table('employee')->insert($employeeInserts);
                }


                $insertedEntries = DB::table('employee')
                    ->whereIn('employee_id', array_keys($employeeInserts))
                    ->select('id', 'company_id', 'employee_id', 'pos_name')
                    ->get()
                    ->keyBy(fn($e) => $e->employee_id);


                $employeeRates = [];
                foreach ($employees as $employee) {
                    $key = $employee->employee_code;
                    $db_employee = isset($insertedEntries[$key]) ? $insertedEntries[$key] : null;

                    if(!$db_employee){
                        continue;
                    }
                    $jsonData = json_decode($employee->json_data, true);

                    foreach ($jsonData as $payRate) {
                        if(!$payRate['start_date'] ){
                            continue;
                        }
                        $employeeRates[] = [
                            'employee_id' => $db_employee->id,
                            'role_id' => $this->employeeRoles[$payRate['role_id']],
                            'pay_type' => $payRate['pay_info'] == 'Hourly' ? 'HR' : 'WK',
                            'rate' => $payRate['pay_rate'] ?? 0,
                            'rate_type' => 'Payroll Regular',
                            'effective_date' => $payRate['start_date'],
                            'payroll_type' => 'Direct Deposit',
                            'company_id' => $db_employee->company_id,
                        ];
                    }
                 
                }
                EmployeeRates::insert($employeeRates);

                // Get the inserted IDs (by matching company_id + date in this chunk)
                
            });

            $insertedEntries = DB::table('employee')
                ->whereIn('employee_id', $this->pendingEmployeeIds)
                ->select('id', 'company_id', 'employee_id', 'pos_name')
                ->get()
                ->keyBy(fn($e) => $e->employee_id);

            $employeeHours = DB::table('employee_hours2')->whereIn('employeeId', $this->pendingEmployeeIds)->get();

            $employeeHoursInserts = [];
            foreach ($employeeHours as $employeeHour) {
                $key = $employeeHour->employeeId;
                $employee = isset($insertedEntries[$key]) ? $insertedEntries[$key] : null;
                $company = is_numeric($employeeHour->storeId) && isset($this->companyMap[$employeeHour->storeId]) ? $this->companyMap[$employeeHour->storeId] : $companies[$employeeHour->storeId] ?? null;
                if(!$employee || !$company){
                    continue;
                }
                if(!$employeeRolesMap[$employeeHour->employeeRole]){
                    info('Employee role not found: '.$employeeHour->employeeRole);
                    continue;
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
                    'role_id' => $employeeRolesMap[$employeeHour->employeeRole] ?? null,
                    'pay_rate' => $employeeHour->payRate ?? 0,
                    'tips_due' => $employeeHour->tipsDue ?? 0,
                    'mileage_due' => $employeeHour->mileageDue ?? 0,
                    'created_at' => $employeeHour->created_at,
                    'updated_at' => $employeeHour->updated_at,
                    'created_by' => 1,
                    'updated_by' => 1,
                ];
            }
            EmployeeHours::insert($employeeHoursInserts);

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

