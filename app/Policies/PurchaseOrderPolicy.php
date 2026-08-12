<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\PermissionName;
use App\PurchaseOrderStatus;

class PurchaseOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::ViewPurchaseOrders)
            || $user->hasPermission(PermissionName::ManagePurchaseOrders)
            || $user->hasPermission(PermissionName::ApprovePurchaseOrders)
            || $user->hasPermission(PermissionName::ReceivePurchaseOrders);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->viewAny($user);
    }

    public function viewSubmitted(User $user): bool
    {
        return $user->hasPermission(PermissionName::ApprovePurchaseOrders);
    }

    public function viewApproved(User $user): bool
    {
        return $user->hasPermission(PermissionName::ApprovePurchaseOrders)
            || $user->hasPermission(PermissionName::ManagePurchaseOrders);
    }

    public function viewSent(User $user): bool
    {
        return $user->hasPermission(PermissionName::ReceivePurchaseOrders)
            || $user->hasPermission(PermissionName::ApprovePurchaseOrders);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::ManagePurchaseOrders);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === PurchaseOrderStatus::Draft
            && $user->hasPermission(PermissionName::ManagePurchaseOrders);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return false;
    }

    public function submit(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === PurchaseOrderStatus::Draft
            && $user->hasPermission(PermissionName::ManagePurchaseOrders);
    }

    public function decide(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === PurchaseOrderStatus::Submitted
            && $user->hasPermission(PermissionName::ApprovePurchaseOrders);
    }

    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === PurchaseOrderStatus::Approved
            && $user->hasPermission(PermissionName::ManagePurchaseOrders);
    }

    public function export(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return in_array($purchaseOrder->status, [
            PurchaseOrderStatus::Approved,
            PurchaseOrderStatus::Sent,
            PurchaseOrderStatus::PartiallyReceived,
            PurchaseOrderStatus::Received,
        ], true) && $this->viewAny($user);
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return in_array($purchaseOrder->status, [PurchaseOrderStatus::Sent, PurchaseOrderStatus::PartiallyReceived], true)
            && $user->hasPermission(PermissionName::ReceivePurchaseOrders);
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return ! in_array($purchaseOrder->status, [
            PurchaseOrderStatus::Received,
            PurchaseOrderStatus::Rejected,
            PurchaseOrderStatus::Cancelled,
        ], true) && $user->hasPermission(PermissionName::ApprovePurchaseOrders);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return false;
    }
}
