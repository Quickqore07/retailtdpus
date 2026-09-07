<?php

namespace App\Console\Commands;

use App\Models\DataEntry\BankDeposit;
use App\Models\DataEntry\DailySale;
use App\Models\DataEntry\DailySaleOtherPayment;
use App\Models\DataEntry\Shortage;
use App\Models\Settings\Company;
use App\Models\Settings\Workgroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddDailySalesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:daily-sales-data {--workgroup= : Workgroup name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Daily Sales Data';
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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        
        
        $salesIds = DailySale::whereIn('company_id', $compnies)->pluck('id')->toArray();
        DailySaleOtherPayment::whereIn('daily_sale_id', $salesIds)->delete();
        BankDeposit::whereIn('daily_sale_id', $salesIds)->delete();
        Shortage::whereIn('daily_sale_id', $salesIds)->delete();
        DailySale::whereIn('id', $salesIds)->delete();

        $maxId = DailySale::max('id');
        DB::statement("ALTER TABLE daily_sales AUTO_INCREMENT = " . $maxId + 1);

        $maxId = DailySaleOtherPayment::max('id');
        DB::statement("ALTER TABLE daily_sales_other_payments AUTO_INCREMENT = " . $maxId + 1);

        $maxId = BankDeposit::max('id');
        DB::statement("ALTER TABLE bank_deposits AUTO_INCREMENT = " . $maxId + 1);

        $maxId = Shortage::max('id');
        DB::statement("ALTER TABLE shortage AUTO_INCREMENT = " . $maxId + 1);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        DB::beginTransaction();
        try {
            $today = now()->format('Y-m-d');
            $allCompanies = Company::select('id', 'store_number')->whereIn('id', $compnies)->get()->pluck('id', 'store_number')->toArray();

            if($workgroup == 'DC') {
                $dailySales = DB::table('daily_sales2')->get();
                $bankDeposits = DB::table('bank_deposit')->get();
                $shortages = DB::table('shortage_entry')->get();
            } else {
                $dailySales = DB::table('daily_sales2')->whereBetween('entry_date', ['2025-12-22', $today])->get();
                $bankDeposits = DB::table('bank_deposit')->whereBetween('deposit_date', ['2025-12-22', $today])->get();
                $shortages = DB::table('shortage_entry')->whereBetween('deposit_date', ['2025-12-22', $today])->get();
            }
            $dailySalesData = [];
            $items =[];

            $bankDepositsData = [];
            $shortagesData = [];
            foreach ($dailySales as $dailySale) {
                if(!isset($allCompanies[$dailySale->company_code])){
                    info($dailySale->company_code);
                    continue;
                }
                $bankDeposit = $bankDeposits->where('sales_id', $dailySale->id)->all();
                foreach($bankDeposit as $bankDeposit){
                    $bankDepositsData[] = [
                        'company_id' => $allCompanies[$dailySale->company_code] ?? null,
                        'sales_date' => $dailySale->entry_date ?? null,
                        'net_sales' => $dailySale->net_sales ?? 0,
                        'date' => $bankDeposit->deposit_date ?? null,
                        'amount' => $bankDeposit->deposit_amount ?? 0,
                    ];
                }

                $shortage = $shortages->where('sales_id', $dailySale->id)->all();


                foreach($shortage as $shortage){
                    $shortagesData[] = [
                        'company_id' => $allCompanies[$dailySale->company_code] ?? null,
                        'sales_date' => $dailySale->entry_date ?? null,
                        'net_sales' => $dailySale->net_sales ?? 0,
                        'date' => $shortage->deposit_date ?? null,
                        'amount' => $shortage->amount ?? 0,
                    ];
                }
                $dailySalesData[] = [
                    'company_id'               => $allCompanies[$dailySale->company_code] ?? null,
                    'date'                     => $dailySale->entry_date ?? null,
                
                    'net_sales'                => $dailySale->net_sales ?? 0,
                    'beverage_tax'             => $dailySale->beverage_tax ?? 0,
                    'food_tax'                 => $dailySale->food_tax ?? 0,
                    'total_sales'              => $dailySale->total_sales ?? 0,
                
                    'cash_received'            => $dailySale->cash_recd ?? 0,
                    'partial_void'             => $dailySale->partial_void ?? 0,
                
                    'total_cash'               => $dailySale->cp_total ?? 0,
                
                    'tips'                     => $dailySale->tips ?? 0,
                    'mileage'                  => $dailySale->mileage ?? 0,
                    'total_tips_mileage'       => $dailySale->tm_total ?? 0,
                
                    'dd_tips'                  => $dailySale->dd_tips ?? 0,
                    'e_tips'                   => $dailySale->e_tips ?? 0,
                    'e_tips_payroll'           => $dailySale->e_tips_payroll ?? 0,
                    'total_e_and_dd_tips'      => $dailySale->de_total ?? 0,
                
                    'cash_payment'             => $dailySale->cash_payment ?? 0,
                    'other_payments_total'     => $dailySale->other_payment_total ??0,
                    'total_cash_payment'       => $dailySale->total_cash_payment ?? 0,
                
                    'net_cash_due'             => $dailySale->net_cash_due ?? 0,
                    'cash_bag'                 => $dailySale->cash_bag ?? 0,
                    'short_over'               => $dailySale->short_over ?? 0,

                    'created_by'               => 1,
                    'updated_by'               => 1,
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ];
                if($dailySale->other_payment_total > 0){
                    $items[] = [
                        'company_id' => $allCompanies[$dailySale->company_code] ?? null,
                        'date' => $dailySale->entry_date,
                        'net_sales' => $dailySale->net_sales,
                        'other_payments' => json_decode($dailySale->other_payments, true),

                    ];
                }

            }
            $chunkSize = 300;
            foreach (array_chunk($dailySalesData, $chunkSize) as $chunk) {
                DailySale::insert($chunk);
            }
            $dailySales = DailySale::select('id', 'company_id', 'date', 'net_sales')->get()->keyBy(function($item){
                return $item->company_id . '_' . $item->date . '_' . $item->net_sales;
            });
            $itemsData = [];
            foreach ($items as $item) {
                $dailySale = $dailySales[$item['company_id'] . '_' . $item['date'] . '_' . $item['net_sales']] ?? null;
                if($dailySale){
                    foreach ($item['other_payments'] as $otherPayment) {
                        if($otherPayment['value'] > 0){
                            $itemsData[] = [
                                'daily_sale_id' => $dailySale->id,
                                'expense' => $otherPayment['label'] == 'N/A' ? "MISC" : $otherPayment['label'],
                                'amount' => $otherPayment['value'],
                            ];
                        }
                    }
                }
            }

            $bankDepositToInsert = [];
            $shortageToInsert = [];
            foreach($bankDepositsData as $bankDeposit){
                $dailySale = $dailySales[$bankDeposit['company_id'] . '_' . $bankDeposit['sales_date'] . '_' . $bankDeposit['net_sales']] ?? null;
                if($dailySale){
                    $bankDepositToInsert[] = [
                        'daily_sale_id' => $dailySale->id,
                        'date' => $bankDeposit['date'],
                        'amount' => $bankDeposit['amount'],
                        'notes' => '',
                        'created_by' => 1,
                        'updated_by' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            foreach($shortagesData as $shortage){
                $dailySale = $dailySales[$shortage['company_id'] . '_' . $shortage['sales_date'] . '_' . $shortage['net_sales']] ?? null;
                if($dailySale){
                    $shortageToInsert[] = [
                        'daily_sale_id' => $dailySale->id,
                        'date' => $shortage['date'],
                        'amount' => $shortage['amount'],
                        'notes' => '',
                        'created_by' => 1,
                        'updated_by' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            $chunkSize = 300;
            foreach (array_chunk($bankDepositToInsert, $chunkSize) as $chunk) {
                BankDeposit::insert($chunk);
            }
            foreach (array_chunk($shortageToInsert, $chunkSize) as $chunk) {
                Shortage::insert($chunk);
            }
            $chunkSize = 300;
            foreach (array_chunk($itemsData, $chunkSize) as $chunk) {
                DailySaleOtherPayment::insert($chunk);
            }
            DB::commit();
            $this->info('Daily Sales data imported successfully');

        } catch (\Throwable $th) {
            DB::rollBack();
            info($th);
            $this->error($th->getMessage());
        }
    }
}
