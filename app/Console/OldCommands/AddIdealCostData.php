<?php

namespace App\Console\Commands;

use App\Models\DataEntry\IdealCost;
use App\Models\DataEntry\IdealCostItem;
use App\Models\Settings\Company;
use App\Models\Settings\Workgroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddIdealCostData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:ideal-cost-data {--workgroup= : Workgroup name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Ideal Cost Data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workgroup = $this->option('workgroup');
        $workgroupData = Workgroup::where('name', $workgroup)->first();
        if(!$workgroupData) {
            $this->error('Workgroup not found');
            return;
        }
        $compnies = Company::where('workgroup_id', $workgroupData->id)->pluck('id','store_number')->toArray();
        if(count($compnies) == 0) {
            $this->error('No companies found for workgroup: ' . $workgroup);
            return;
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        
        $ids = IdealCostItem::whereIn('company_id', $compnies)->pluck('ideal_cost_id')->toArray();
        IdealCost::whereIn('id', $ids)->delete();
        IdealCostItem::whereIn('ideal_cost_id', $ids)->delete();
        
        $maxId = IdealCost::max('id');
        DB::statement("ALTER TABLE ideal_cost AUTO_INCREMENT = " . $maxId + 1);

        $maxId = IdealCostItem::max('id');
        DB::statement("ALTER TABLE ideal_cost_items AUTO_INCREMENT = " . $maxId + 1);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::beginTransaction();
        try {
            $today = now()->format('Y-m-d');
            if($workgroup == 'DC') {
                $idealCosts = DB::table('ideal_cost2')->get();
            } else {
                $idealCosts = DB::table('ideal_cost2')->whereBetween('entry_date', ['2025-12-22', $today])->get();
            }
            $companies = Company::get()->pluck('id', 'store_number')->toArray();

            $items =[];
            foreach ($idealCosts as $idealCost) {

                $store_data = json_decode($idealCost->json_purchase, true);
                $total_delivery = 0;
                $cost_items = [];
                foreach ($store_data as $item) {
                    if(!isset($companies[$item['store_code']])) {
                        continue;
                    }
                    $cost_items[] = [
                        'company_id' => $companies[$item['store_code']],
                        'ideal_cost' => $item['ideal_cost'],
                        'mileage' => $item['milege'],
                        'delivery' => $item['delivery'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $total_delivery += $item['delivery'];
                }

                $idealCostModel = IdealCost::insertGetId([
                    'date' => $idealCost->entry_date,
                    'total_cost' => $idealCost->total_ideal_cost,
                    'total_mileage' => $idealCost->total_milege,
                    'total_delivery' => $total_delivery,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                foreach ($cost_items as $key => $item) {
                    $cost_items[$key]['ideal_cost_id'] = $idealCostModel;
                }

                $items = array_merge($cost_items, $items);
            }
            
            IdealCostItem::insert($items);

            DB::commit();
            $this->info('Ideal Cost data imported successfully');

        } catch (\Throwable $th) {
            DB::rollBack();
            info($th);
            $this->error($th->getMessage());
        }
    }
}
