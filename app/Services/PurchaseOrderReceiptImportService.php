<?php

namespace App\Services;

use App\GoodsReceiptImportStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseOrderReceiptImport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class PurchaseOrderReceiptImportService
{
    /** @var list<string> */
    private const REQUIRED_HEADERS = [
        'order line id',
        'inventory item id',
        'sku',
        'ordered',
        'previously received',
        'quantity delivered now',
        'outstanding',
    ];

    public function __construct(private PurchaseOrderService $orders) {}

    public function parse(PurchaseOrderReceiptImport $import): PurchaseOrderReceiptImport
    {
        $import->update([
            'status' => GoodsReceiptImportStatus::Validating,
            'valid_rows' => 0,
            'skipped_rows' => 0,
            'invalid_rows' => 0,
            'failure_reason' => null,
        ]);
        $import->rows()->delete();

        try {
            $sheet = IOFactory::load(Storage::disk('local')->path($import->file_path))->getActiveSheet();
            $rows = $sheet->toArray(null, false, false, true);
            [$headerRow, $headers] = $this->headers($rows);

            if ($headerRow === null) {
                return $this->fail($import, 'The order-line header row was not found. Use an exported purchase-order file.');
            }

            $missing = array_values(array_diff(self::REQUIRED_HEADERS, array_keys($headers)));
            if ($missing !== []) {
                return $this->fail($import, 'Required columns are missing: '.implode(', ', $missing).'.');
            }

            $order = $import->purchaseOrder;
            $fileOrderNumber = $this->orderNumber($rows, $headers, $headerRow);
            if ($fileOrderNumber !== $order->order_number) {
                return $this->fail($import, "This file belongs to order {$fileOrderNumber}, not {$order->order_number}.");
            }

            $order->load([
                'lines' => fn ($query) => $query
                    ->with('inventoryItem.pace.course.subject')
                    ->withSum('effectiveGoodsReceiptLines as received_quantity', 'quantity_received'),
            ]);
            $orderLines = $order->lines->keyBy('id');
            $seenLineIds = [];
            $counts = ['valid' => 0, 'skipped' => 0, 'invalid' => 0];

            foreach ($rows as $rowNumber => $row) {
                if ($rowNumber <= $headerRow || $rowNumber > $headerRow + 5000 || $this->emptyLine($row, $headers)) {
                    continue;
                }

                $raw = $this->rawData($row, $headers);
                $errors = $this->formulaErrors($raw);
                $lineId = $this->integer($raw['order line id'] ?? null);
                $inventoryItemId = $this->integer($raw['inventory item id'] ?? null);
                $ordered = $this->integer($raw['ordered'] ?? null);
                $previouslyReceived = $this->integer($raw['previously received'] ?? null, true) ?? 0;
                $fileOutstanding = $this->integer($raw['outstanding'] ?? null);
                $quantity = $this->integer($raw['quantity delivered now'] ?? null, true);
                $line = $lineId === null ? null : $orderLines->get($lineId);

                if ($lineId === null) {
                    $errors[] = 'Order line ID must be a whole number.';
                } elseif (isset($seenLineIds[$lineId])) {
                    $errors[] = "Order line {$lineId} appears more than once.";
                } else {
                    $seenLineIds[$lineId] = true;
                }

                if (! $line instanceof PurchaseOrderLine) {
                    $errors[] = 'The order line does not belong to this purchase order.';
                }

                if ($quantity === null) {
                    $errors[] = 'Quantity delivered now must be a whole number.';
                    $quantity = 0;
                } elseif ($quantity < 0 || $quantity > 100000) {
                    $errors[] = 'Quantity delivered now must be between 0 and 100,000.';
                }

                $normalized = null;
                if ($line instanceof PurchaseOrderLine) {
                    $item = $line->inventoryItem;
                    $currentReceived = (int) ($line->received_quantity ?? 0);
                    $outstanding = max($line->quantity_ordered - $currentReceived, 0);

                    if ($inventoryItemId !== $line->inventory_item_id) {
                        $errors[] = 'Inventory item ID was changed.';
                    }
                    if ($this->text($raw['sku'] ?? null) !== $item->sku) {
                        $errors[] = 'SKU was changed.';
                    }
                    if ($ordered !== $line->quantity_ordered) {
                        $errors[] = 'Ordered quantity was changed.';
                    }
                    if ($previouslyReceived !== $currentReceived) {
                        $errors[] = 'Previously received quantity is stale or was changed. Export a current copy of the order.';
                    }
                    if ($fileOutstanding !== $outstanding) {
                        $errors[] = 'Outstanding quantity is stale or was changed. Export a current copy of the order.';
                    }
                    $pace = $item->pace;
                    $normalized = [
                        'purchase_order_line_id' => $line->id,
                        'inventory_item_id' => $item->id,
                        'sku' => $item->sku,
                        'item_type' => $item->item_type->label(),
                        'subject' => $pace?->course->subject->name,
                        'course' => $pace?->course->name,
                        'pace_number' => $pace?->number,
                        'pace_title' => $pace?->title,
                        'quantity_ordered' => $line->quantity_ordered,
                        'previously_received' => $currentReceived,
                        'quantity_received' => $quantity,
                        'outstanding_before' => $outstanding,
                        'outstanding_after' => max($outstanding - $quantity, 0),
                        'excess_quantity' => max($quantity - $outstanding, 0),
                    ];
                }

                $status = $errors !== [] ? 'invalid' : ($quantity > 0 ? 'valid' : 'skipped');
                $counts[$status]++;
                $import->rows()->create([
                    'row_number' => $rowNumber,
                    'purchase_order_line_id' => $line?->id,
                    'raw_data' => $raw,
                    'normalized_data' => $normalized,
                    'status' => $status,
                    'errors' => $errors === [] ? null : $errors,
                ]);
            }

            $missingLines = $orderLines->reject(
                fn (PurchaseOrderLine $line): bool => isset($seenLineIds[$line->id]),
            );
            foreach ($missingLines->values() as $offset => $line) {
                $item = $line->inventoryItem;
                $currentReceived = (int) ($line->received_quantity ?? 0);
                $outstanding = max($line->quantity_ordered - $currentReceived, 0);
                $counts['invalid']++;
                $import->rows()->create([
                    'row_number' => $headerRow + count($rows) + $offset + 1,
                    'purchase_order_line_id' => $line->id,
                    'raw_data' => [],
                    'normalized_data' => [
                        'purchase_order_line_id' => $line->id,
                        'inventory_item_id' => $item->id,
                        'sku' => $item->sku,
                        'item_type' => $item->item_type->label(),
                        'quantity_ordered' => $line->quantity_ordered,
                        'previously_received' => $currentReceived,
                        'quantity_received' => 0,
                        'outstanding_before' => $outstanding,
                        'outstanding_after' => $outstanding,
                        'excess_quantity' => 0,
                    ],
                    'status' => 'invalid',
                    'errors' => ['This purchase-order line is missing from the uploaded file. Export a current copy of the order.'],
                ]);
            }

            $ready = $counts['invalid'] === 0 && $counts['valid'] > 0;
            $failureReason = match (true) {
                $counts['invalid'] > 0 => 'Correct the invalid spreadsheet rows before posting this delivery.',
                $counts['valid'] === 0 => 'Enter a positive quantity in Quantity delivered now for at least one item.',
                default => null,
            };
            $import->update([
                'status' => $ready ? GoodsReceiptImportStatus::Ready : GoodsReceiptImportStatus::Failed,
                'valid_rows' => $counts['valid'],
                'skipped_rows' => $counts['skipped'],
                'invalid_rows' => $counts['invalid'],
                'failure_reason' => $failureReason,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->fail($import, 'The delivery file could not be read. Export a new Excel or CSV copy and try again.');
        }

        return $import->refresh();
    }

    /** @param array<string, mixed> $attributes */
    public function commit(PurchaseOrderReceiptImport $import, array $attributes, User $actor): GoodsReceipt
    {
        return DB::transaction(function () use ($import, $attributes, $actor): GoodsReceipt {
            $import = PurchaseOrderReceiptImport::query()->lockForUpdate()->findOrFail($import->id);
            if ($import->status !== GoodsReceiptImportStatus::Ready || $import->invalid_rows > 0) {
                throw ValidationException::withMessages(['import' => 'Only a validated delivery import can be posted.']);
            }

            $rows = $import->rows()
                ->where('status', 'valid')
                ->get();
            $lines = $rows
                ->map(function ($row): array {
                    $data = $row->normalized_data;
                    if (! is_array($data)) {
                        throw ValidationException::withMessages(['import' => 'A validated import row is missing its normalized data.']);
                    }

                    return [
                        'purchase_order_line_id' => $data['purchase_order_line_id'],
                        'quantity_received' => $data['quantity_received'],
                    ];
                })
                ->all();

            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($import->purchase_order_id);
            $liveLines = PurchaseOrderLine::query()
                ->where('purchase_order_id', $order->id)
                ->whereIn('id', $rows->pluck('purchase_order_line_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            foreach ($rows as $row) {
                $data = $row->normalized_data;
                $liveLine = $liveLines->get($row->purchase_order_line_id);
                if (! is_array($data) || ! $liveLine instanceof PurchaseOrderLine
                    || $liveLine->receivedQuantity() !== $data['previously_received']) {
                    throw ValidationException::withMessages([
                        'import' => 'This order changed after validation. Export and upload a current copy before posting stock.',
                    ]);
                }
            }

            $receipt = $this->orders->receive($order, [
                ...$attributes,
                'lines' => $lines,
            ], $actor);
            $import->update([
                'status' => GoodsReceiptImportStatus::Committed,
                'committed_by' => $actor->id,
                'committed_at' => now(),
                'goods_receipt_id' => $receipt->id,
            ]);

            return $receipt;
        }, 3);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{0: int|null, 1: array<string, string>}
     */
    private function headers(array $rows): array
    {
        foreach (array_slice($rows, 0, 30, true) as $rowNumber => $row) {
            $headers = [];
            foreach ($row as $column => $value) {
                $label = $this->normalizeHeader($value);
                if ($label !== null) {
                    $headers[$label] = $column;
                }
            }

            if (isset($headers['sku'], $headers['quantity delivered now'])) {
                return [(int) $rowNumber, $headers];
            }
        }

        return [null, []];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $headers
     */
    private function orderNumber(array $rows, array $headers, int $headerRow): ?string
    {
        if (isset($headers['order number'])) {
            foreach ($rows as $rowNumber => $row) {
                if ($rowNumber > $headerRow) {
                    $value = $this->text($row[$headers['order number']] ?? null);
                    if ($value !== null) {
                        return $value;
                    }
                }
            }
        }

        foreach (array_slice($rows, 0, 10, true) as $row) {
            foreach ($row as $column => $value) {
                if ($this->normalizeHeader($value) === 'order number') {
                    $nextColumn = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($column) + 1);

                    return $this->text($row[$nextColumn] ?? null);
                }
            }
        }

        $title = $this->text($rows[1]['A'] ?? null);
        if ($title !== null && preg_match('/Purchase Order\s+(.+)$/i', $title, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    private function rawData(array $row, array $headers): array
    {
        return collect($headers)
            ->mapWithKeys(fn (string $column, string $header): array => [$header => $row[$column] ?? null])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $headers
     */
    private function emptyLine(array $row, array $headers): bool
    {
        return $this->text($row[$headers['order line id']] ?? null) === null
            && $this->text($row[$headers['sku']] ?? null) === null
            && $this->text($row[$headers['quantity delivered now']] ?? null) === null;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return list<string>
     */
    private function formulaErrors(array $raw): array
    {
        foreach ($raw as $value) {
            if (is_string($value) && str_starts_with(ltrim($value), '=')) {
                return ['Spreadsheet formulas are not allowed in delivery imports.'];
            }
        }

        return [];
    }

    private function integer(mixed $value, bool $blankIsZero = false): ?int
    {
        if ($blankIsZero && $this->text($value) === null) {
            return 0;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }
        $text = $this->text($value);

        return $text !== null && preg_match('/^-?\d+$/', $text) === 1 ? (int) $text : null;
    }

    private function normalizeHeader(mixed $value): ?string
    {
        $text = $this->text($value);

        return $text === null
            ? null
            : mb_strtolower(preg_replace('/\s+/', ' ', ltrim($text, "\xEF\xBB\xBF")) ?? $text);
    }

    private function text(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function fail(PurchaseOrderReceiptImport $import, string $reason): PurchaseOrderReceiptImport
    {
        $import->update([
            'status' => GoodsReceiptImportStatus::Failed,
            'failure_reason' => $reason,
        ]);

        return $import->refresh();
    }
}
