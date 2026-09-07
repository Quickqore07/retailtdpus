<?php

namespace App\Console\Commands;

use App\Http\Controllers\AR\PjPaymentController;
use App\Models\AR\PjPayment;
use App\Models\AR\PjPaymentItem;
use App\Models\LedgerVouchers;
use App\Models\Settings\Company;
use App\Models\Settings\LedgerDetails;
use App\Models\Settings\Workgroup;
use App\Services\ActivityLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddPJPaymentData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:pj-payment-data {--workgroup= : Workgroup name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add PJ Payment Data';

    protected $companyMapping = [
        'maple shade pj llc' => 1,
        'willow grove pj llc' => 2,
        'levittown pj llc' => 3,
        'willingboro pj llc' => 4,
        'pie investments haddon ave llc' => 5,
        'doylestown pj llc' => 6,
        'lansdowne pj llc' => 7,
        'vineland pj llc' => 8,
        'new castle pj llc' => 9,
        'ogletown pj llc' => 10,
        'em newark pj llc' => 11,
        'lancaster pike pj llc' => 12,
        'marsh road pj llc' => 13,
        'hockessin pj llc' => 14,
        'bear pj llc' => 15,
        'dover pj llc' => 16,
        'newark pj llc' => 17,
        'warminster yr llc' => 18,
        'mount holly pj llc' => 19,
        'pie investments somerdale llc' => 20,
        'pie investments pine hill llc' => 21,
        'north wales pj llc' => 22,
        'wrightstown pj llc' => 23,
        'philadelphia pj llc' => 24,
        'west chester pj llc' => 25,
        'exton evb llc' => 26,
        'west windsor pj llc' => 27,
        'norristown rp llc' => 28,
        'ne philadelphia pj llc' => 29,
        'mount laurel pj llc' => 30,
        'pottstown sr llc' => 31,
        'deptford pj llc' => 32,
        'bensalem pj llc' => 33,
        'ocean view pj llc' => 34,
        'middeltown pj llc' => 35,
        'bechtelsville pj llc' => 36,
        'cc philadelphia pj llc' => 37,
        'pie north brunswick' => 38,
        'king of prussia pj llc' => 39,
        'broad st pj llc' => 40,
        'berwyn pj llc' => 41,
        'pie investments union ave llc' => 42,
        'pie investments highland park llc' => 43,
        'medford pj llc' => 44,
        'pie investments kearny llc' => 45,
        'pie investments plainfield llc' => 46,
        'pie investments franklin park llc' => 47,
        'pennsauken pj llc' => 48,
        'pie inv williamstown llc' => 49,
        'milford pj llc' => 50,
        'pie investments edison llc' => 51,
        'rehoboth pj llc' => 52,
        'seaford pj llc' => 53,
        'millsboro pj llc' => 54,
        'thorndale lh llc' => 55,
        'plymouth meeting pj llc' => 56,
        'folsom pj llc' => 57,
        'feasterville pj llc' => 58,
        'brookhaven pj llc' => 59,
        'souderton pj llc' => 60,
        'east brunswick pj llc' => 61,
        'hillsborough pj llc' => 62,
        'freehold pj llc' => 63,
        'welsh pj llc' => 64,
        'limerick pj llc' => 65,
        'west philadelphia pj llc' => 66,
        'wadsworth pj llc' => 67,
        'englishtown pj llc' => 68,
        's broad street pj llc' => 69,
    ];

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

        $pjIds = PjPayment::whereIn('company_id', $compnies)->pluck('id')->toArray();
        PjPaymentItem::whereIn('pj_payment_id', $pjIds)->delete();
        LedgerVouchers::whereIn('voucher_id', $pjIds)->where('voucher_type', 'pj_payments')->delete();
        PjPayment::whereIn('id', $pjIds)->delete();

        $maxId = PjPayment::max('id');
        DB::statement("ALTER TABLE pj_payments AUTO_INCREMENT = " . $maxId + 1);

        $maxId = PjPaymentItem::max('id');
        DB::statement("ALTER TABLE pj_payments_items AUTO_INCREMENT = " . $maxId + 1);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::beginTransaction();
        try {
            $masterCard           = ['Mastercard', 'PayPal MC', 'Venmo', 'Visa Debit', 'Discover', 'Visa', 'JCB', 'Mastercard Debit', 'Other Debit'];
            $EZCater            = ['Postmates', 'EZCater', 'EZCater Manual'];
            $ledgerMapping = PjPaymentController::ledgerMapping ?? [];

            $ledgerDetails = LedgerDetails::select('company_id', 'code', 'ledger_id')->get();
            $companyWiseCodes = [];
            foreach ($ledgerDetails as $ledgerDetail) {
                $companyWiseCodes[$ledgerDetail->company_id][$ledgerDetail->code] = $ledgerDetail->ledger_id;
            }

            $createdBy = 1;
            $createdPaymentIds = [];
            $lastProcessedDate = null;
            $currentDate = null;
            $currentCompany = null;
            $currentGroupItems = [];

            $companies = Company::all()->pluck('id','store_number')->toArray();
            $persistPaymentGroup = function ($date, $company, $groupItems) use (&$createdPaymentIds, &$lastProcessedDate, $createdBy, $companyWiseCodes, $companies) {
                if (!$date || !$company || empty($groupItems)) {
                    return;
                }

                $companyId = $this->companyMapping[strtolower($company)] ?? null;

                if (!$companyId) {
                    $companyId = $companies[$company] ?? null;
                    if(!$companyId) return;
                }

                $totalAmount = collect($groupItems)->sum('amount');
                $now = now();
                $pjPaymentId = DB::table('pj_payments')->insertGetId([
                    'company_id' => $companyId,
                    'date' => $date,
                    'total_amount' => $totalAmount,
                    'is_imported' => true,
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $createdPaymentIds[] = $pjPaymentId;
                $lastProcessedDate = $date;

                $paymentItems = [];
                $ledgerVouchers = [];
                foreach ($groupItems as $groupItem) {
                    if ((float) $groupItem['amount'] == 0.0) {
                        continue;
                    }
                    if (!isset($companyWiseCodes[$companyId][$groupItem['ledger_code']])) {
                        continue;
                    }
                    $ledgerId = $companyWiseCodes[$companyId][$groupItem['ledger_code']];

                    $paymentItems[] = [
                        'pj_payment_id' => $pjPaymentId,
                        'ledger_id' => $ledgerId,
                        'name' => $groupItem['name'],
                        'amount' => $groupItem['amount'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $ledgerVouchers[] = [
                        'company_id' => $companyId,
                        'ledger_id' => $ledgerId,
                        'opp_ledger_id' => 0,
                        'amount' => -1 *$groupItem['amount'],
                        'debit' => $groupItem['amount'],
                        'credit' => 0,
                        'voucher_id' => $pjPaymentId,
                        'voucher_items_id' => null,
                        'voucher_type' => 'pj_payments',
                        'dbtable' => 'pj_payments',
                        'check_number' => null,
                        'description' => null,
                        'date' => $date,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($paymentItems)) {
                    PjPaymentItem::insert($paymentItems);
                }
                if (!empty($ledgerVouchers)) {
                    LedgerVouchers::insert($ledgerVouchers);
                }
            };
            $today = now()->format('Y-m-d');
            $totalRows = 0;
            DB::table('pj_payment_sales')
                ->where(function($query) use ($workgroup, $today) {
                    if($workgroup != 'DC') {
                        $query->whereBetween('report_date', ['2025-12-22', $today]);
                    }
                })
                ->orderBy('report_date')
                ->orderBy('company')
                ->chunk(10000, function ($rows) use (&$currentDate, &$currentCompany, &$currentGroupItems, $masterCard, $EZCater, $ledgerMapping, $persistPaymentGroup, &$totalRows) {
                    foreach ($rows as $data) {
                        $rowDate = $data->report_date;
                        $rowCompany = $data->company;

                        if ($currentDate === null) {
                            $currentDate = $rowDate;
                            $currentCompany = $rowCompany;
                        }

                        if ($rowDate !== $currentDate || $rowCompany !== $currentCompany) {
                            $persistPaymentGroup($currentDate, $currentCompany, $currentGroupItems);
                            $currentDate = $rowDate;
                            $currentCompany = $rowCompany;
                            $currentGroupItems = [];
                        }

                        if ($data->payment_type == 'TOTAL' || !$data->payment_type) {
                            continue;
                        }

                        if (in_array($data->payment_type, $masterCard)) {
                            $paymentType = 'Visa / Master';
                        } elseif (in_array($data->payment_type, $EZCater)) {
                            $paymentType = 'EZ Cater';
                        } else {
                            $paymentType = $data->payment_type;
                        }

                        if(isset($currentGroupItems[$paymentType])){
                            $currentGroupItems[$paymentType]['amount'] = $currentGroupItems[$paymentType]['amount'] + $data->payment_amount + $data->tips_amount;
                        }else{
                            $currentGroupItems[$paymentType] = [
                                'name' => $paymentType,
                                'amount' => $data->payment_amount + $data->tips_amount,
                                'ledger_code' => $ledgerMapping[$paymentType] ?? null,
                            ];
                        }
                    }
                    $totalRows += $rows->count();
                    $this->info('Processed ' . $rows->count() . ' rows' . ' Total rows: ' . $totalRows);
                });

            // Persist the final group collected after the last chunk row.
            $persistPaymentGroup($currentDate, $currentCompany, $currentGroupItems);

            $allPaymentIds = array_values(array_unique($createdPaymentIds));
            if (count($allPaymentIds) > 0) {
                ActivityLogService::logBulkImport(
                    'pj_payments',
                    [
                        'ids' => $allPaymentIds,
                        'created_ids' => array_values(array_unique($createdPaymentIds)),
                        'updated_ids' => [],
                        'date' => $lastProcessedDate,
                    ],
                    "PJ payment bulk upload for {$lastProcessedDate}"
                );
            }

            DB::commit();
            $this->info('PJ Payment data imported successfully');

        } catch (\Throwable $th) {
            DB::rollBack();
            info($th);
            $this->error($th->getMessage());
        }
    }
}
