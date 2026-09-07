<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Payroll\EmployeeRates;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Models\Settings\EmployeeSubRoles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddEmployeeRateInDE extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:employee-rate-in-de';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add employee rate in DE';

    protected $oldStoreNumbers = [
        'Old - 1317' =>'1317' ,
        'Old - 1318' =>'1318' ,
        'Old - 1319' =>'1319' ,
        'Old - 1320' =>'1320' ,
        'Old - 1322' =>'1322' ,
        'Old - 1323' =>'1323' ,
        'Old - 1324' =>'1324' ,
        'Old - 1325' =>'1325' ,
        'Old - 1326' =>'1326' ,
        'Old - 3013' =>'3013' ,
        'Old - 3017' =>'3017' ,
        'Old - 3947' =>'3947' ,
        'Old - 4204' =>'4204' ,
        'Old - 4236' =>'4236' ,
        'Old - 4672' =>'4672' ,
    ];

    public function handle()
    {
        try {
            
            // Backup tables once, outside transaction
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            DB::statement('DROP TABLE IF EXISTS employee_backup');
            DB::statement('DROP TABLE IF EXISTS employee_rates_backup');
            DB::statement('DROP TABLE IF EXISTS employee_roles_backup');
            DB::statement('DROP TABLE IF EXISTS employee_sub_roles_backup');

            DB::statement('CREATE TABLE IF NOT EXISTS employee_backup LIKE employee');
            DB::statement('INSERT INTO employee_backup SELECT * FROM employee');

            DB::statement('CREATE TABLE IF NOT EXISTS employee_rates_backup LIKE employee_rates');
            DB::statement('INSERT INTO employee_rates_backup SELECT * FROM employee_rates');


            DB::statement('CREATE TABLE IF NOT EXISTS employee_roles_backup LIKE employee_roles');
            DB::statement('INSERT INTO employee_roles_backup SELECT * FROM employee_roles');

            DB::statement('CREATE TABLE IF NOT EXISTS employee_sub_roles_backup LIKE employee_sub_role');
            DB::statement('INSERT INTO employee_sub_roles_backup SELECT * FROM employee_sub_role');

            $this->info('Tables backed up successfully');

          
            
            $employeeIds = EmployeeRates::whereHas('company', function($query) {
                $query->whereIn('store_number', array_values($this->oldStoreNumbers));
            })->pluck('employee_id')->toArray();

            if(count($employeeIds) > 0){
                Employee::whereIn('id', $employeeIds)->delete();
                EmployeeRates::whereIn('employee_id', $employeeIds)->delete();
            }
           

            

            $ids = EmployeeRoles::where('workgroup_id', 3)->pluck('id')->toArray();
            EmployeeSubRoles::whereIn('role_id', $ids)->delete();
            EmployeeRoles::whereIn('id', $ids)->delete();


            

            $maxId = DB::table('employee')->max('id') ?? 0;
            DB::statement("ALTER TABLE employee AUTO_INCREMENT = " . $maxId);

            $maxId = DB::table('employee_rates')->max('id') ?? 0;
            DB::statement("ALTER TABLE employee_rates AUTO_INCREMENT = " . $maxId);

            $maxId = DB::table('employee_roles')->max('id') ?? 0;
            DB::statement("ALTER TABLE employee_roles AUTO_INCREMENT = " . $maxId);

            $maxId = DB::table('employee_sub_role')->max('id') ?? 0;
            DB::statement("ALTER TABLE employee_sub_role AUTO_INCREMENT = " . $maxId);


            
            DB::beginTransaction();

            info('Starting to add employee rates');
            
            $ratesToInsert = [];
            $companies = Company::pluck('id','store_number')->toArray();

           
            $employeeRoles = EmployeeRoles::with('subRoles')->where('workgroup_id', 1)->get();
            $employeeRolesMap=[];
            $subRolesToAdd=[];
            foreach($employeeRoles as $employeeRole) {
                $empRole = new EmployeeRoles();
                $empRole->name = $employeeRole->name;
                $empRole->code = $employeeRole->code;
                $empRole->active = $employeeRole->active;
                $empRole->tipped = $employeeRole->tipped;
                $empRole->workgroup_id = 3;
                $empRole->created_by = 1;
                $empRole->updated_by = 1;
                $empRole->save();
                $employeeRolesMap[$employeeRole->id] = $empRole->id;
                foreach($employeeRole->subRoles as $subRole) {
                    $subRolesToAdd[] = [
                        'code' => $subRole->code,
                        'role_id' => $empRole->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

           EmployeeSubRoles::insert($subRolesToAdd);


            foreach ($this->oldStoreNumbers as $oldStoreNumber => $newStoreNumber) {

                $oldCompanyId = $companies[$oldStoreNumber] ?? null;
                $newCompanyId = $companies[$newStoreNumber] ?? null;
                if (! $oldCompanyId) {
                    $this->error('Old company not found for store: '.$oldStoreNumber);
                    continue;
                }
                if (! $newCompanyId) {
                    $this->error('Company not found: '.$newStoreNumber);
                    continue;
                }

                $backupRates = DB::table('employee_rates')
                    ->where('company_id', $oldCompanyId)
                    ->get();


                $oldEmployeeIds = $backupRates->pluck('employee_id')->unique()->values();
                $backupEmployees = DB::table('employee')
                    ->whereIn('id', $oldEmployeeIds)
                    ->get();


                $employeeIdMap = [];
                foreach ($backupEmployees as $row) {
                    $data = (array) $row;
                    unset($data['id']);
                    $data['company_id'] = $newCompanyId;
                    $data['workgroup_id'] = 3;
                    $newId = DB::table('employee')->insertGetId($data);
                    $employeeIdMap[$row->id] = $newId;
                }

                foreach ($backupRates as $employeeRate) {
                    $newEmployeeId = $employeeIdMap[$employeeRate->employee_id] ?? null;
                    if (! $newEmployeeId) {
                        $this->warn('Skipping rate id '.$employeeRate->id.': employee '.$employeeRate->employee_id.' not copied for '.$oldStoreNumber);
                        continue;
                    }
                    $newRoleId = $employeeRolesMap[$employeeRate->role_id] ?? null;
                    if (! $newRoleId) {
                        $this->error('Role map missing for role_id '.$employeeRate->role_id.' (rate id '.$employeeRate->id.')');
                        continue;
                    }

                    $ratesToInsert[] = [
                        'employee_id' => $newEmployeeId,
                        'role_id' => $newRoleId,
                        'company_id' => $newCompanyId,
                        'effective_date' => $employeeRate->effective_date,
                        'rate' => $employeeRate->rate,
                        'payroll_hours' => $employeeRate->payroll_hours,
                        'payroll_hours_type' => $employeeRate->payroll_hours_type,
                        'pay_type' => $employeeRate->pay_type,
                        'rate_type' => $employeeRate->rate_type,
                        'slab_first_hours' => $employeeRate->slab_first_hours,
                        'slab_rest_rate' => $employeeRate->slab_rest_rate,
                        'payroll_rate' => $employeeRate->payroll_rate,
                        'ten99_rate' => $employeeRate->ten99_rate,
                        'check_payment_type' => $employeeRate->check_payment_type,
                        'check_payment_amount' => $employeeRate->check_payment_amount,
                        'till_date' => $employeeRate->till_date,
                        'payroll_type' => $employeeRate->payroll_type,
                        'created_at' => $employeeRate->created_at,
                        'updated_at' => $employeeRate->updated_at,
                        'created_by' => $employeeRate->created_by,
                        'updated_by' => $employeeRate->updated_by,
                    ];
                }

            }

            foreach (array_chunk($ratesToInsert, 500) as $chunk) {
                EmployeeRates::insert($chunk);
            }

          
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
}


