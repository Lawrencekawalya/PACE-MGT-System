<?php

namespace App\Services;

use App\Models\ReportExport;
use App\ReportExportStatus;
use App\ReportFormat;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class ReportExportGenerator
{
    public function __construct(private ReportDataService $reports) {}

    public function generate(ReportExport $export): ReportExport
    {
        $export->update(['status' => ReportExportStatus::Processing, 'error_message' => null]);

        try {
            $result = $this->reports->data($export->report_type, $export->filters);
            $data = $this->reports->exportData($export->report_type, $result['rows']);
            $filename = Str::slug($export->report_type->label()).'-'.now()->format('Ymd-His').'.'.$export->format->value;
            $path = "report-exports/{$export->user_id}/{$export->id}-{$filename}";
            $contents = $export->format === ReportFormat::Csv
                ? $this->csv($data['headers'], $data['rows']->all())
                : $this->xlsx($data['headers'], $data['rows']->all());
            Storage::disk($export->disk)->put($path, $contents);
            $export->update([
                'status' => ReportExportStatus::Completed,
                'path' => $path,
                'original_filename' => $filename,
                'row_count' => $data['rows']->count(),
                'completed_at' => now(),
                'expires_at' => now()->addDays((int) config('reports.expiry_days')),
            ]);
        } catch (Throwable $exception) {
            $export->update([
                'status' => ReportExportStatus::Failed,
                'error_message' => Str::limit($exception->getMessage(), 1000),
            ]);

            throw $exception;
        }

        return $export->refresh();
    }

    /**
     * @param  list<string>  $headers
     * @param  array<int, list<string|int|float|null>>  $rows
     */
    private function csv(array $headers, array $rows): string
    {
        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) {
            throw new \RuntimeException('Unable to open the CSV export stream.');
        }
        fputcsv($stream, $headers);
        foreach ($rows as $row) {
            fputcsv($stream, array_map($this->sanitize(...), $row));
        }
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read the CSV export stream.');
        }

        return $contents;
    }

    /**
     * @param  list<string>  $headers
     * @param  array<int, list<string|int|float|null>>  $rows
     */
    private function xlsx(array $headers, array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray(array_map(fn (array $row): array => array_map($this->sanitize(...), $row), $rows), null, 'A2');
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $temporary = tempnam(sys_get_temp_dir(), 'pace-report-');
        if ($temporary === false) {
            throw new \RuntimeException('Unable to create the XLSX export file.');
        }
        (new Xlsx($spreadsheet))->save($temporary);
        $contents = file_get_contents($temporary);
        unlink($temporary);
        $spreadsheet->disconnectWorksheets();

        if ($contents === false) {
            throw new \RuntimeException('Unable to read the XLSX export file.');
        }

        return $contents;
    }

    private function sanitize(string|int|float|null $value): string|int|float|null
    {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value) === 1) {
            return "'{$value}";
        }

        return $value;
    }
}
