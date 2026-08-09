<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadPurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\ReportFormat;
use App\Services\PurchaseOrderExportService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PurchaseOrderExportController extends Controller
{
    public function __construct(private PurchaseOrderExportService $exports) {}

    public function __invoke(DownloadPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): Response
    {
        $format = ReportFormat::from($request->validated('format'));
        $filename = "{$purchaseOrder->order_number}.{$format->value}";

        return response($this->exports->generate($purchaseOrder, $format), 200, [
            'Content-Type' => $format === ReportFormat::Xlsx
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv; charset=UTF-8',
            'Content-Disposition' => (new ResponseHeaderBag)->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename,
            ),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
