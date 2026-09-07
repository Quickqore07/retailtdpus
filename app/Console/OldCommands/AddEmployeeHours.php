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

class AddEmployeeHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:employee-hours';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add employee hours to the database';

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
            
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $this->info('Tables backed up successfully');

            EmployeeHours::join('employee', 'employee_hours.employee_id', '=', 'employee.id')
            ->where('employee.workgroup_id', 2)
            ->delete();
            
            
            DB::beginTransaction();

            $companies = Company::where('workgroup_id', 2)->pluck('id','store_number')->toArray();
            $employeeRoles = EmployeeRoles::where('workgroup_id', 2)->pluck('id','name')->toArray();

            $now = Carbon::now();

            
            $ratesMap = [];
            EmployeeRates::select('employee_id', 'role_id', 'company_id', 'effective_date')
                ->join('employee', 'employee_rates.employee_id', '=', 'employee.id')
                ->where('employee.workgroup_id', 2)
                ->select('employee_rates.employee_id', 'employee_rates.role_id', 'employee_rates.company_id', 'employee_rates.effective_date')
                ->chunk(1000, function ($rates) use (&$ratesMap) {
                    foreach ($rates as $rate) {
                        $key = $rate->employee_id . '_' . $rate->role_id . '_' . $rate->company_id;
                        $ratesMap[$key][] = $rate->effective_date;
                    }
                });


            $storeEmployees = Employee::where('workgroup_id', 2)->select('id', 'employee_id', 'pos_name')->get()->groupBy('employee_id');
            $totalEmployeeHours =0;
            
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

