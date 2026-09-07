<?php

namespace App\Http\Controllers\Ttm;

use App\Http\Controllers\Controller;
use App\Models\Settings\PandlConfigurationDetail;
use App\Models\Ttm\TtmReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TtmUploadController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'ttm-upload.index');

        $collection = TtmReport::query()
            ->select('ttm_reports.year', 'ttm_reports.month')
            ->selectRaw('COUNT(*) as row_count')
            ->selectRaw('COUNT(DISTINCT ttm_reports.store_number) as store_count')
            ->selectRaw('MAX(ttm_reports.created_at) as created_at')
            ->groupBy('ttm_reports.year', 'ttm_reports.month')
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function upload(Request $request)
    {
        $this->authorize('access', 'ttm-upload.upload');

        $request->validate([
            'file' => 'sometimes|file|mimes:csv,txt,xlsx,xls|' . upload_max_file_size_rule(),
            'files' => 'sometimes|array',
            'files.*' => 'file|mimes:csv,txt,xlsx,xls|' . upload_max_file_size_rule(),
        ]);

        $files = $request->hasFile('files')
            ? (array) $request->file('files')
            : ($request->hasFile('file') ? [$request->file('file')] : []);
        $files = array_values(array_filter($files));

        if (empty($files)) {
            return to_json([
                'success' => false,
                'message' => 'No file(s) provided.',
            ], 422);
        }
        $messages = [];
        $totalInserted = 0;

        $monthNames = [
            'January'=>1,
            'February'=>2,
            'March'=>3,
            'April'=>4,
            'May'=>5,
            'June'=>6,
            'July'=>7,
            'August'=>8,
            'September'=>9,
            'October'=>10,
            'November'=>11,
            'December'=>12,
        ];

        $labels = PandlConfigurationDetail::pluck('label', 'label')->toArray();

        DB::beginTransaction();
        try {
            $userId = Auth::id();
            $now = now();

            foreach ($files as $theFile) {

                $excelfile = IOFactory::load($theFile->getRealPath());
                $sheet = $excelfile->getActiveSheet();
                $row_limit = $sheet->getHighestDataRow();
                
                $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
                $year = $sheet->getCell('B2')->getFormattedValue();
                $month = $sheet->getCell('B3')->getFormattedValue();
                $month = isset($monthNames[$month]) ? $monthNames[$month] : null;

                if(!$year || !$month){
                        return to_json([
                            'success' => false,
                            'message' => 'Invalid year or month',
                        ], 422);
                }   
                
                TtmReport::query()
                ->where('year', $year)
                ->where('month', $month)
                ->delete();

                if($sheet->getCell('A8')->getFormattedValue() == null || $sheet->getCell('A8')->getFormattedValue() != 'Category' || $sheet->getCell('B8')->getFormattedValue() == null){
                    return to_json([
                        'success' => false,
                        'message' => 'Invalid data in row 8',
                    ], 422);
                }

                $store_numbers = [];
                for($col = 1; $col <= $highestColumnIndex; $col++){

                    $columnLetter = Coordinate::stringFromColumnIndex($col);
                    $colValue = $sheet->getCell($columnLetter.'8')->getFormattedValue();
                    if(str_contains(strtolower($colValue), 'total') || $colValue == 'Category'){
                        continue;
                    }
                    $store_number = explode(' - ', $colValue);
                    $store_numbers[$columnLetter] = isset($store_number[0]) ? $store_number[0] : null;
                    if(!$store_numbers[$columnLetter]){
                        return to_json([
                            'success' => false,
                            'message' => 'Invalid store number in row 8: '.$colValue,
                        ], 422);
                    }
                }
                $ttm_data=[];
                for($row = 9; $row <= $row_limit; $row++){
                    $category = $sheet->getCell('A'.$row)->getFormattedValue(); 
                    $label = isset($labels[$category]) ? $labels[$category] : null;
                    if(str_contains(strtolower($category), 'total') || str_contains(strtolower($category), 'percentage') || str_contains(strtolower($category), 'net income')){
                        continue;
                    }
                    if(!$label){
                        $messages[] = "Invalid category in row {$row}: {$category}";
                        continue;
                    }

                     foreach($store_numbers as $columnLetter => $store_number){
                        $value = $sheet->getCell($columnLetter.$row)->getFormattedValue();
                        $formattedValue = $this->parseAmount($value);
                        if((float)$formattedValue == 0){
                            continue;
                        }
                        $ttm_data[] = [
                            'store_number' => $store_number,
                            'label' => $label,
                            'amount' => $formattedValue,
                            'year' => $year,
                            'month' => $month,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                     }

                }
                foreach(array_chunk($ttm_data, 500) as $chunk){
                    TtmReport::insert($chunk);
                }
                $totalInserted += count($ttm_data);
            }
            if ($totalInserted === 0) {
                DB::rollBack();

                return to_json([
                    'success' => false,
                    'message' => 'No data rows were imported. Check your file and column headers.',
                    'messages' => $messages,
                ], 422);
            }

            DB::commit();

            return to_json([
                'success' => true,
                'message' => "Imported successfully.",
                'messages' => $messages,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return to_json([
                'success' => false,
                'message' => 'Upload failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function deletePeriod(Request $request)
    {
        $this->authorize('access', 'ttm-upload.delete');

        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|between:1,12',
        ]);

        $year = (string) $request->integer('year');
        $month = str_pad((string) $request->integer('month'), 2, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $deleted = TtmReport::query()
                ->where('year', $year)
                ->where('month', $month)
                ->delete();

            DB::commit();

            return to_json([
                'deleted' => true,
                'message' => $deleted > 0
                    ? "Deleted {$deleted} row(s) for {$year}-{$month}."
                    : 'No rows found for that period.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return to_json([
                'deleted' => false,
                'message' => 'Delete failed: '.$e->getMessage(),
            ], 500);
        }
    }

    private function pickColumn(array $slugToCol, array $preferredSlugs): ?int
    {
        foreach ($preferredSlugs as $slug) {
            if (isset($slugToCol[$slug])) {
                return $slugToCol[$slug];
            }
        }

        return null;
    }

    private function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }
}
