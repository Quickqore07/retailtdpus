<?php

namespace App\Console\Commands;

use App\Models\DataEntry\Drivers;
use App\Models\Settings\Company;
use App\Models\DataEntry\WcEntry;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddDriverData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:driver-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add driver data to the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('TRUNCATE TABLE drivers');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::beginTransaction();
        try {
            $companies = Company::get()->pluck('id', 'store_number')->toArray();

            $data = DB::table('driver_timesheets')->get();

            $drivers = [];


            $employees = Employee::with('aliases')->select('id', 'employee_id', 'pos_name', 'termination_date', 'workgroup_id')
            ->where('workgroup_id', 1)
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
            
            
            foreach ($data as $item) {
                if(!isset($companies[$item->store_number])){
                    continue;
                }   
                $employee_id = null;
                if(isset($employeeMap[$item->emp_id . '|' . $item->driver_name])){
                    $employee_id = $employeeMap[$item->emp_id . '|' . $item->driver_name];
                }
                if(!$employee_id && isset($allEmployeeIds[$item->emp_id])){
                    $employee_id = $allEmployeeIds[$item->emp_id];
                }
            
                $drivers[] = [
                    'company_id' => $companies[$item->store_number],
                    'date' => $item->calendar_date,
                    'driver_id' => $item->emp_id,
                    'driver_name' => $item->driver_name,
                    'employee_id' => $employee_id,
                    'time_in' => $item->time_in,
                    'time_out' => $item->time_out,
                    'driver_in_store_pay' => is_numeric($item->driver_in_store_pay) ? $item->driver_in_store_pay : 0,
                    'other_in_store_pay' => is_numeric($item->other_in_store_pay) ? $item->other_in_store_pay : 0,
                    'on_road_pay' => is_numeric($item->on_road_pay) ? $item->on_road_pay : 0,
                    'cash_tips' => is_numeric($item->cash_tips) ? $item->cash_tips : 0,
                    'cc_tips' => is_numeric($item->cc_tips) ? $item->cc_tips : 0,
                    'mileage' => is_numeric($item->mileage) ? $item->mileage : 0,
                    'all_in_pay' => is_numeric($item->all_in_pay) ? $item->all_in_pay : 0,
                    'store_hours' => is_numeric($item->store_hour) ? $item->store_hour : 0,
                    'road_hours' => is_numeric($item->road_hour) ? $item->road_hour : 0,
                    'total_hours' => is_numeric($item->total_hours_worked) ? $item->total_hours_worked : 0,
                    'avg_pay_per_hour' => is_numeric($item->avg_all_in_pay_per_hour) ? $item->avg_all_in_pay_per_hour : 0,
                    'delivery' => is_numeric($item->total_delivery_orders) ? $item->total_delivery_orders : 0,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            }

            foreach (array_chunk($drivers, 1000) as $batch) {
                Drivers::insert($batch);
            }
            DB::commit();
            $this->info('Driver data imported successfully');
            return Command::SUCCESS;
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            $this->error($th->getMessage());
            return Command::FAILURE;
        }
    }
}
