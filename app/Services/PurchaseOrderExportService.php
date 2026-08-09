<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\ReportFormat;
use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PurchaseOrderExportService
{
    /** @var list<string> */
    private const LINE_HEADERS = [
        'Order line ID',
        'Inventory item ID',
        'SKU',
        'Item type',
        'Subject',
        'Course',
        'PACE number',
        'PACE title',
        'Ordered',
        'Previously received',
        'Quantity delivered now',
        'Outstanding',
        'Line notes',
    ];

    public function generate(PurchaseOrder $order, ReportFormat $format): string
    {
        $this->load($order);

        return $format === ReportFormat::Csv
            ? $this->csv($order)
            : $this->xlsx($order);
    }

    private function load(PurchaseOrder $order): void
    {
        $order->load([
            'supplier:id,name,code',
            'createdBy:id,name',
            'submittedBy:id,name',
            'decidedBy:id,name',
            'lines' => fn ($query) => $query
                ->with([
                    'inventoryItem.pace.course.subject:id,name',
                ])
                ->withSum('effectiveGoodsReceiptLines as received_quantity', 'quantity_received')
                ->orderBy('id'),
        ]);
    }

    private function csv(PurchaseOrder $order): string
    {
        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) {
            throw new \RuntimeException('Unable to open the purchase-order CSV stream.');
        }

        $headers = [
            'Order number', 'Order source', 'Supplier', 'Supplier code', 'Status',
            'Expected date', 'Prepared by', 'Submitted by', 'Approved by', 'Approved at',
            'Order notes', ...self::LINE_HEADERS,
        ];
        fputcsv($stream, $headers);

        foreach ($order->lines as $line) {
            fputcsv($stream, array_map($this->sanitize(...), [
                $order->order_number,
                $order->source->value,
                $order->supplier->name,
                $order->supplier->code,
                $order->status->label(),
                $this->date($order->expected_on),
                $order->createdBy?->name,
                $order->submittedBy?->name,
                $order->decidedBy?->name,
                $this->dateTime($order->decided_at),
                $order->notes,
                ...$this->lineRow($line),
            ]));
        }

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read the purchase-order CSV stream.');
        }

        return "\xEF\xBB\xBF{$contents}";
    }

    private function xlsx(PurchaseOrder $order): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Purchase Order');
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', "Purchase Order {$order->order_number}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $metadata = [
            ['A3', 'Order number', 'B3', $order->order_number],
            ['A4', 'Supplier', 'B4', "{$order->supplier->name} ({$order->supplier->code})"],
            ['D3', 'Status', 'E3', $order->status->label()],
            ['D4', 'Expected', 'E4', $this->date($order->expected_on) ?? 'Not set'],
            ['G3', 'Approved by', 'H3', $order->decided_by === null ? 'Not recorded' : $order->decidedBy->name],
            ['G4', 'Approved at', 'H4', $this->dateTime($order->decided_at) ?? 'Not recorded'],
        ];
        foreach ($metadata as [$labelCell, $label, $valueCell, $value]) {
            $sheet->setCellValue($labelCell, $label);
            $sheet->setCellValue($valueCell, $this->sanitize($value));
            $sheet->getStyle($labelCell)->getFont()->setBold(true);
        }

        $sheet->setCellValue('A6', 'Order notes');
        $sheet->getStyle('A6')->getFont()->setBold(true);
        $sheet->mergeCells('B6:M6');
        $sheet->setCellValue('B6', $this->sanitize($order->notes ?? '—'));

        $headerRow = 8;
        $sheet->fromArray(self::LINE_HEADERS, null, "A{$headerRow}");
        $sheet->fromArray(
            $order->lines->map(fn (PurchaseOrderLine $line): array => array_map($this->sanitize(...), $this->lineRow($line)))->all(),
            null,
            'A9',
        );
        $sheet->getStyle("A{$headerRow}:M{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '222222']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->freezePane('A9');
        $sheet->setAutoFilter("A{$headerRow}:M".max($headerRow, $sheet->getHighestRow()));
        $sheet->getStyle('I9:L'.$sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('K9:K'.$sheet->getHighestRow())->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCEAF7');
        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $temporary = tempnam(sys_get_temp_dir(), 'pace-order-');
        if ($temporary === false) {
            throw new \RuntimeException('Unable to create the purchase-order XLSX file.');
        }

        try {
            (new Xlsx($spreadsheet))->save($temporary);
            $contents = file_get_contents($temporary);
        } finally {
            unlink($temporary);
            $spreadsheet->disconnectWorksheets();
        }

        if ($contents === false) {
            throw new \RuntimeException('Unable to read the purchase-order XLSX file.');
        }

        return $contents;
    }

    /** @return list<string|int|null> */
    private function lineRow(PurchaseOrderLine $line): array
    {
        $item = $line->inventoryItem;
        $pace = $item->pace;
        $received = (int) ($line->received_quantity ?? 0);

        return [
            $line->id,
            $item->id,
            $item->sku,
            $item->item_type->label(),
            $pace?->course->subject->name,
            $pace?->course->name,
            $pace?->number,
            $pace?->title,
            $line->quantity_ordered,
            $received,
            null,
            max($line->quantity_ordered - $received, 0),
            $line->notes,
        ];
    }

    private function date(?CarbonInterface $date): ?string
    {
        return $date?->format('d/m/Y');
    }

    private function dateTime(?CarbonInterface $date): ?string
    {
        return $date?->timezone(config('app.timezone'))->format('d/m/Y g:i A');
    }

    private function sanitize(string|int|null $value): string|int|null
    {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value) === 1) {
            return "'{$value}";
        }

        return $value;
    }
}
