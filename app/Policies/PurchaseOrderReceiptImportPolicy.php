<?php

namespace App\Policies;

use App\GoodsReceiptImportStatus;
use App\Models\PurchaseOrderReceiptImport;
use App\Models\User;
use App\PermissionName;
use App\PurchaseOrderStatus;

class PurchaseOrderReceiptImportPolicy
{
    public function view(User $user, PurchaseOrderReceiptImport $import): bool
    {
        return $user->hasPermission(PermissionName::ReceivePurchaseOrders)
            || $user->hasPermission(PermissionName::ApprovePurchaseOrders);
    }

    public function commit(User $user, PurchaseOrderReceiptImport $import): bool
    {
        return $import->status === GoodsReceiptImportStatus::Ready
            && in_array($import->purchaseOrder->status, [
                PurchaseOrderStatus::Sent,
                PurchaseOrderStatus::PartiallyReceived,
            ], true)
            && $user->hasPermission(PermissionName::ReceivePurchaseOrders);
    }

    public function cancel(User $user, PurchaseOrderReceiptImport $import): bool
    {
        return in_array($import->status, [
            GoodsReceiptImportStatus::Ready,
            GoodsReceiptImportStatus::Failed,
        ], true) && $user->hasPermission(PermissionName::ReceivePurchaseOrders);
    }
}
