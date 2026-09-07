<?php

namespace App\Console\Commands;

use App\Models\DataEntry\FoodPurchase;
use App\Models\DataEntry\FoodPurchaseItems;
use App\Models\Settings\Company;
use App\Models\Settings\Workgroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddPurchaseData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:purchase-data {--workgroup= : Workgroup name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Purchase Data';


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
        
        $ids = FoodPurchaseItems::whereIn('company_id', $compnies)->pluck('food_purchase_id')->toArray();
        FoodPurchase::whereIn('id', $ids)->delete();
        FoodPurchaseItems::whereIn('food_purchase_id', $ids)->delete();

        $maxId = FoodPurchase::max('id');
        DB::statement("ALTER TABLE food_purchase AUTO_INCREMENT = " . $maxId + 1);

        $maxId = FoodPurchaseItems::max('id');
        DB::statement("ALTER TABLE food_purchase_items AUTO_INCREMENT = " . $maxId + 1);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::beginTransaction();
        try {
            $today = now()->format('Y-m-d');
            if($workgroup == 'DC') {
                $foodPurchases = DB::table('purchase')->get();
            } else {
                $foodPurchases = DB::table('purchase')->whereBetween('entry_date', ['2025-12-22', $today])->get();
            }

            $foodPurchaseItems = [];

            foreach ($foodPurchases as $foodPurchase) {
                $foodPurchaseModel = FoodPurchase::create([
                    'date' => $foodPurchase->entry_date,
                    'total_amount' => $foodPurchase->total_payment,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $items =json_decode($foodPurchase->json_purchase, true);
                foreach ($items as $item) {
                    if(!isset($compnies[$item['store_code']])){
                        info($item['store_code']);
                        continue;
                    }
                    $foodPurchaseItems[] = [
                        'food_purchase_id' => $foodPurchaseModel->id,
                        'company_id' => $compnies[$item['store_code']],
                        'food_product_purchase' => $item['food_product_purchase'],
                        'paper_supplies' => $item['paper_supplies'],
                        'smallware_supplies' => $item['smallware_supplies'],
                        'cleaning_supplies' => $item['cleaning_supplies'],
                        'pepsi' => $item['pepsi'],
                        'total_amount' => $item['amount'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            FoodPurchaseItems::insert($foodPurchaseItems);
            DB::commit();
            $this->info('Purchase data imported successfully');

        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            $this->error($th->getMessage());
        }
    }
}
