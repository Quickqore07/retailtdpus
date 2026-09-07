<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\PayrollJournal;
use App\Models\Settings\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PayrollJournalController extends Controller
{
    /**
     * Spreadsheet columns C onward, in the same order as database columns after company_id / eow.
     */
    private const UPLOAD_COLUMNS = [
        'bonus'=>['field' => 'bonus', 'column' => 'C'],
        'cs: accident' => ['field' => 'cs_accident', 'column' => 'D'],
        'cs: chtermlife' => ['field' => 'cs_term_life', 'column' => 'D'],
        'cs: critillness' => ['field' => 'crit_illness', 'column' => 'D'],
        'cs: dental a' => ['field' => 'dental_a', 'column' => 'D'],
        'cs: dental b' => ['field' => 'dental_b', 'column' => 'D'],
        'cs: dental d' => ['field' => 'dental_d', 'column' => 'D'],
        'cs: eetermlife' => ['field' => 'ee_term_life', 'column' => 'D'],
        'cs: hospitalind' => ['field' => 'hospital_ind', 'column' => 'D'],
        'cs: id theft' => ['field' => 'id_theft', 'column' => 'D'],
        'cs: legal' => ['field' => 'legal', 'column' => 'D'],
        'cs: lifeltc' => ['field' => 'life_ltc', 'column' => 'D'],
        'cs: pet well' => ['field' => 'pet_Well', 'column' => 'D'],
        'cs: sptermlife' => ['field' => 'sp_term_life', 'column' => 'D'],
        'cs: std' => ['field' => 'std', 'column' => 'D'],
        'cs: vision' => ['field' => 'vision', 'column' => 'D'],
        'cash tips' => ['field' => 'cash_tips', 'column' => 'E'],
        'charge tips' => ['field' => 'charge_tips', 'column' => 'C'],
        'charge tips reimb' => ['field' => 'charge_tips_reimb', 'column' => 'C'],
        'dental' => ['field' => 'dental', 'column' => 'D'],
        'direct deposit debit' => ['field' => 'direct_deposit_debit', 'column' => 'D'],
        'hourly' => ['hours' =>'B' ,'wages'=>'C'],
        'Medical' => ['field' => 'medical', 'column' => 'D'],
        'mileage reimb' => ['field' => 'mileage_reimb', 'column' => 'C'],
        'min wage adjust' => ['field' => 'min_wage_adjust', 'column' => 'C'],
        'min wage adjust anne' => ['field' => 'min_wage_adjust_anne', 'column' => 'C'],
        'min wage adjust-anne' => ['field' => 'min_wage_adjust_anne', 'column' => 'C'],
        'min wage adjust bcit' => ['field' => 'min_wage_adjust_bcit', 'column' => 'C'],
        'min wage adjust balt' => ['field' => 'min_wage_adjust_balt', 'column' => 'C'],
        'min wage adjust calv' => ['field' => 'min_wage_adjust_calv', 'column' => 'C'],
        'min wage adjust carr' => ['field' => 'min_wage_adjust_carr', 'column' => 'C'],
        'min wage adjust char' => ['field' => 'min_wage_adjust_char', 'column' => 'C'],
        'min wage adjust fred' => ['field' => 'min_wage_adjust_fred', 'column' => 'C'],
        'min wage adjust harf' => ['field' => 'min_wage_adjust_harf', 'column' => 'C'],
        'min wage adjust howa' => ['field' => 'min_wage_adjust_howa', 'column' => 'C'],
        'min wage adjust mont' => ['field' => 'min_wage_adjust_mont', 'column' => 'C'],
        'min wage adjust prin' => ['field' => 'min_wage_adjust_prin', 'column' => 'C'],
        'min wage adjust stma' => ['field' => 'min_wage_adjust_stma', 'column' => 'C'],
        'overtime' => ['overtime' => 'B', 'overtime_wages' => 'C'],
        'px garnishment' => ['field' => 'px_garnishment', 'column' => 'D'],
        'px garnishment 2' => ['field' => 'px_garnishment_2', 'column' => 'D'],
        'retro pretax premium' => ['field' => 'retro_pretax_premium', 'column' => 'D'],
        'salary' => ['field' => 'salary', 'column' => 'C'],
        'sick' => ['field' => 'sick', 'column' => 'C'],
        'term life pretax' => ['field' => 'term_life_pretax', 'column' => 'D'],
        'vacation' => ['field' => 'vacation', 'column' => 'C'],
    ];

    public function index()
    {
        $this->authorize('access', 'payroll-journal.index');

        $collection = PayrollJournal::
            authorizedCompanies('company_id')
            ->groupBy('eow','created_at')
            ->selectRaw('eow, created_at, sum(total_earnings) as total_earnings,sum(er_withholdings) as er_withholdings,sum(charge_tips_reimb) as total_tips,sum(mileage_reimb) as total_mileage,sum(bonus) as total_bonus,sum(total_earnings) + sum(er_withholdings) - sum(charge_tips_reimb) - sum(mileage_reimb) - sum(bonus) as ctc')
            ->with('company')
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function upload(Request $request)
    {
        $this->authorize('access', 'payroll-journal.create');

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|' . upload_max_file_size_rule(),
            'date' => 'required|date',
        ]);

        $authorizedCompanies = authorizedCompanies();
        if (count($authorizedCompanies) === 0) {
            return to_json([
                'message' => 'No authorized companies found',
                'uploaded' => false,
            ], 400);
        }

        DB::beginTransaction();
        try {   
            $spreadsheet = IOFactory::load($request->file('file'));
            $sheet = $spreadsheet->getActiveSheet();
            $lastRow = $sheet->getHighestDataRow();

            $rows = [];
            $messages = [];
            $now = now();
            $userId = Auth::id();
            $eow = $validated['date'];

            $currentWorkgroupId = $request->session()->get('workgroup');
            $companies = Company::where('workgroup_id', $currentWorkgroupId)->pluck('id', 'payroll_id')->toArray();




            $currentCompany = null;
            for ($row = 0; $row <= $lastRow; $row++) {
                $A = $sheet->getCell('A' . $row)->getFormattedValue();
                if(!$currentCompany && is_numeric($A) && $A != 0){
                    $rowData = [];
                    $currentCompany = isset($companies[$A]) ? $companies[$A] : null;

                    foreach($this::UPLOAD_COLUMNS as $column => $value){
                        if(isset($value['field'])){
                            $rowData[$value['field']] = 0;
                        }else if(isset($value['hours'])){
                            $rowData['hours'] = 0;
                            $rowData['wages'] = 0;
                        }else if(isset($value['overtime'])){
                            $rowData['overtime'] = 0;
                            $rowData['overtime_wages'] = 0;
                        }
                    }
                    if(!$currentCompany){
                        $messages[] = "Company {$A} is not found (row {$row})";
                        continue;
                    }
                }else if ($currentCompany && $A != null && $A != '' && $A != 'Grand Total'){
                    $field = self::UPLOAD_COLUMNS[strtolower($A)] ?? null;
                    if($field && isset($field['hours'])){
                        $rowData['hours'] = $sheet->getCell($field['hours'] . $row)->getFormattedValue();
                        $rowData['wages'] = $sheet->getCell($field['wages'] . $row)->getFormattedValue();
                    }else if($field && isset($field['overtime'])){
                        $rowData['overtime'] = $sheet->getCell($field['overtime'] . $row)->getFormattedValue();
                        $rowData['overtime_wages'] = $sheet->getCell($field['overtime_wages'] . $row)->getFormattedValue();
                    }else if($field && isset($field['field'])){
                        $rowData[$field['field']] = $sheet->getCell($field['column'] . $row)->getFormattedValue();
                    }
                } else if($currentCompany && $A == 'Grand Total'){
                    $rowData['total_hours'] = $sheet->getCell('B' . $row)->getFormattedValue();
                    $rowData['total_earnings'] = $sheet->getCell('C' . $row)->getFormattedValue();
                    $rowData['total_deductions'] = $sheet->getCell('D' . $row)->getFormattedValue();
                    $rowData['total_other_payments'] = $sheet->getCell('E' . $row)->getFormattedValue();    
                    $rowData['ee_withholdings'] = $sheet->getCell('G' . $row)->getFormattedValue();    
                    $rowData['er_withholdings'] = $sheet->getCell('H' . $row)->getFormattedValue();    
                    $rowData['manual_net_pay'] = $sheet->getCell('I' . $row)->getFormattedValue();    
                    $rowData['negotiable_net_pay'] = $sheet->getCell('J' . $row)->getFormattedValue();    
                    $rowData['non_negotiable_net_pay'] = $sheet->getCell('K' . $row)->getFormattedValue(); 
                    $rowData['company_id'] = $currentCompany;
                    $rowData['eow'] = $eow;
                    $rowData['created_by'] = $userId;
                    $rowData['updated_by'] = $userId;
                    $rowData['updated_at'] = $now;
                    $rowData['created_at'] = $now;
                    $rows[] = $rowData;

                    $currentCompany = null;
                }
            }

            if (count($rows) > 0) {
                PayrollJournal::where('eow', $eow)->whereIn('company_id', array_column($rows, 'company_id'))->delete();
                foreach (array_chunk($rows, 500) as $chunk) {
                    PayrollJournal::insert($chunk);
                }
            }
            DB::commit();

            return to_json([
                'uploaded' => true,
                'message' => 'Payroll journal uploaded successfully',
                'messages' => $messages,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e);

            return to_json([
                'uploaded' => false,
                'message' => 'Payroll journal upload failed',
            ], 500);
        }
    }

    public function companyData(Request $request)
    {
        $this->authorize('access', 'payroll-journal.index');

        $validated = $request->validate([
            'eow' => ['required', 'date'],
            'created_at' => ['required', 'date'],
        ]);

        $collection = PayrollJournal::query()
            ->authorizedCompanies('company_id')
            ->whereDate('eow', $validated['eow'])
            ->where('created_at', $validated['created_at'])
            ->groupBy('company_id')
            ->selectRaw('company_id, SUM(total_earnings) as total_earnings, SUM(er_withholdings) as er_withholdings, SUM(charge_tips_reimb) as total_tips, SUM(mileage_reimb) as total_mileage, SUM(bonus) as total_bonus, SUM(total_earnings) + SUM(er_withholdings) - SUM(charge_tips_reimb) - SUM(mileage_reimb) - SUM(bonus) as ctc')
            ->with('company')
            ->get();

        return to_json([
            'data' => $collection,
        ]);
    }

    private function toFloat($value): float
    {
        $normalized = str_replace([',', '$', ' '], '', (string) $value);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
}
