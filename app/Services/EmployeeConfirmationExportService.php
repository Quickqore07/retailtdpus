<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeConfirmationExportService
{
    public const HEADERS = [
        'Employee',
        'Employee ID',
        'Store Number',
        'Store',
        'Status',
        'HR Status',
        'I-9 Choice',
        'Approved At',
        'I-9 Form',
    ];

    /**
     * @param  array<int, array{title: string, rows: array<int, array<int, string>>}>  $sheets
     */
    public function download(array $sheets, ?string $filename = null): BinaryFileResponse
    {
        if (empty($sheets)) {
            abort(422, 'No data to export.');
        }

        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $index => $sheetData) {
            $sheet = $spreadsheet->createSheet($index);
            $sheet->setTitle($this->sanitizeSheetTitle($sheetData['title'] ?? ('Sheet ' . ($index + 1))));

            $headers = self::HEADERS;
            $lastCol = Coordinate::stringFromColumnIndex(count($headers));

            $sheet->fromArray($headers, null, 'A1');
            $headerRange = 'A1:' . $lastCol . '1';
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4472C4');
            $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

            $row = 2;
            foreach ($sheetData['rows'] ?? [] as $values) {
                $sheet->fromArray($values, null, 'A' . $row);
                $row++;
            }

            for ($i = 1; $i <= count($headers); $i++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = $filename ?: 'manual_i9_export_' . date('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    protected function sanitizeSheetTitle(string $title): string
    {
        $title = str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $title);
        $title = trim($title);

        if ($title === '') {
            $title = 'Sheet';
        }

        return mb_substr($title, 0, 31);
    }
}
