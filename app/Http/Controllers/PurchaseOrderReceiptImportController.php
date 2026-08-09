<?php

namespace App\Http\Controllers;

use App\GoodsReceiptImportStatus;
use App\Http\Requests\CommitPurchaseOrderReceiptImportRequest;
use App\Http\Requests\StorePurchaseOrderReceiptImportRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReceiptImport;
use App\Services\ActivityLogger;
use App\Services\PurchaseOrderReceiptImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderReceiptImportController extends Controller
{
    public function __construct(
        private PurchaseOrderReceiptImportService $imports,
        private ActivityLogger $activityLogger,
    ) {}

    public function store(StorePurchaseOrderReceiptImportRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $file = $request->file('workbook');
        abort_unless($file instanceof UploadedFile, 422);
        $checksum = hash_file('sha256', $file->getRealPath());
        if ($checksum === false) {
            throw ValidationException::withMessages(['workbook' => 'The uploaded file checksum could not be calculated.']);
        }
        if ($purchaseOrder->receiptImports()
            ->where('checksum', $checksum)
            ->where('status', GoodsReceiptImportStatus::Committed)
            ->exists()) {
            throw ValidationException::withMessages(['workbook' => 'This delivery file has already been posted.']);
        }

        $path = $file->store("purchase-order-receipt-imports/{$purchaseOrder->id}", 'local');
        $import = $purchaseOrder->receiptImports()->create([
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'checksum' => $checksum,
            'status' => GoodsReceiptImportStatus::Validating,
            'uploaded_by' => $request->user()->id,
        ]);
        $import = $this->imports->parse($import);
        $this->activityLogger->record(
            $request->user(),
            'purchase-order-receipt-import.uploaded',
            $import,
            newValues: $import->only(['purchase_order_id', 'original_name', 'checksum', 'status', 'valid_rows', 'invalid_rows']),
        );
        Inertia::flash('toast', [
            'type' => $import->status === GoodsReceiptImportStatus::Ready ? 'success' : 'error',
            'message' => $import->status === GoodsReceiptImportStatus::Ready
                ? 'Delivery file validated. Review it before posting stock.'
                : 'Delivery file validation found problems.',
        ]);

        return redirect()->route('purchase-order-receipt-imports.show', $import);
    }

    public function show(PurchaseOrderReceiptImport $purchaseOrderReceiptImport): Response
    {
        Gate::authorize('view', $purchaseOrderReceiptImport);
        $purchaseOrderReceiptImport->load([
            'purchaseOrder.supplier:id,name,code',
            'uploader:id,name',
            'committer:id,name',
            'goodsReceipt:id,receipt_number',
            'rows',
        ]);

        return Inertia::render('purchase-order-receipt-imports/Show', [
            'receiptImport' => $purchaseOrderReceiptImport,
            'canCommit' => $purchaseOrderReceiptImport->status === GoodsReceiptImportStatus::Ready
                && Gate::allows('commit', $purchaseOrderReceiptImport),
            'canCancel' => in_array($purchaseOrderReceiptImport->status, [
                GoodsReceiptImportStatus::Ready,
                GoodsReceiptImportStatus::Failed,
            ], true) && Gate::allows('cancel', $purchaseOrderReceiptImport),
        ]);
    }

    public function commit(
        CommitPurchaseOrderReceiptImportRequest $request,
        PurchaseOrderReceiptImport $purchaseOrderReceiptImport,
    ): RedirectResponse {
        $receipt = $this->imports->commit($purchaseOrderReceiptImport, $request->validated(), $request->user());
        $this->activityLogger->record(
            $request->user(),
            'purchase-order-receipt-import.committed',
            $purchaseOrderReceiptImport,
            newValues: [
                'goods_receipt_id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'purchase_order_id' => $receipt->purchase_order_id,
            ],
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => "{$receipt->receipt_number} posted to the stock ledger."]);

        return redirect()->route('purchase-order-receipt-imports.show', $purchaseOrderReceiptImport);
    }

    public function cancel(Request $request, PurchaseOrderReceiptImport $purchaseOrderReceiptImport): RedirectResponse
    {
        Gate::authorize('cancel', $purchaseOrderReceiptImport);
        abort_unless(in_array($purchaseOrderReceiptImport->status, [
            GoodsReceiptImportStatus::Ready,
            GoodsReceiptImportStatus::Failed,
        ], true), 422, 'Only an unposted delivery import can be cancelled.');
        $purchaseOrderReceiptImport->update(['status' => GoodsReceiptImportStatus::Cancelled]);
        $this->activityLogger->record($request->user(), 'purchase-order-receipt-import.cancelled', $purchaseOrderReceiptImport);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Delivery import cancelled; stock was unchanged.']);

        return redirect()->route('purchase-orders.sent');
    }
}
