<?php

namespace Coroowicaksono\ChartJsIntegration\Api;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export chart data to Excel file.
     *
     * @param NovaRequest $request
     * @return StreamedResponse
     */
    public function export(NovaRequest $request)
    {
        $series = $request->input('series', []);
        $labels = $request->input('labels', []);
        $title = $request->input('title', 'chart-export');

        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setTitle(substr($title, 0, 31)); // Excel sheet title max 31 chars

        // Add header row with labels
        $sheet->setCellValue('A1', 'Label');
        $columnIndex = 2; // Start from column B

        foreach ($series as $seriesItem) {
            $seriesLabel = $seriesItem['label'] ?? 'Series ' . $columnIndex;
            $sheet->setCellValueByColumnAndRow($columnIndex, 1, $seriesLabel);
            $columnIndex++;
        }

        // Add data rows
        $rowIndex = 2;
        foreach ($labels as $labelIndex => $label) {
            $sheet->setCellValueByColumnAndRow(1, $rowIndex, $label);

            $columnIndex = 2;
            foreach ($series as $seriesItem) {
                $data = $seriesItem['data'] ?? [];
                $value = $data[$labelIndex] ?? 0;
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $value);
                $columnIndex++;
            }

            $rowIndex++;
        }

        // Style the header row
        $headerRange = 'A1:' . $sheet->getCellByColumnAndRow($columnIndex - 1, 1)->getCoordinate();
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Auto-size columns
        foreach (range(1, $columnIndex - 1) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        // Generate filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $sanitizedTitle = preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $title);
        $sanitizedTitle = str_replace(' ', '_', $sanitizedTitle);
        $filename = "{$sanitizedTitle}_{$timestamp}.xlsx";

        // Create streamed response
        return new StreamedResponse(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
}
