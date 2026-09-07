<?php

namespace App\Console\Commands;

use App\Models\Settings\Company;
use App\Models\DataEntry\WcEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddWCData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:wc-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('TRUNCATE TABLE wc_entries');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::beginTransaction();
        try {
            $companies = Company::get()->pluck('id', 'store_number')->toArray();

            $data = DB::table('wc_data')->get();

            $wcEntries = [];
            foreach ($data as $item) {
                if(!isset($companies[$item->company_code])){
                    continue;
                }   
                $wcEntries[] = [
                    'company_id' => $companies[$item->company_code],
                    'year' => $item->year,
                    'eow' => $item->payroll_period,
                    'driver_pay' => $item->driver_pay,
                    'non_driver_pay' => $item->non_driver_pay,
                    'total_pay' => $item->total_pay,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            }

            foreach (array_chunk($wcEntries, 1000) as $batch) {
                WcEntry::insert($batch);
            }
            DB::commit();
            $this->info('WC data imported successfully');
            return Command::SUCCESS;
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->error($th->getMessage());
            return Command::FAILURE;
        }
    }
}
