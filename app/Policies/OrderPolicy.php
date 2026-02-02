<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Order;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->hasPermissionTo('ViewAny:Order');
    }

    public function view(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('View:Order');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->hasPermissionTo('Create:Order');
    }

    public function update(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('Update:Order');
    }

    public function delete(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('Delete:Order');
    }

    public function restore(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('Restore:Order');
    }

    public function forceDelete(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ForceDelete:Order');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->hasPermissionTo('ForceDeleteAny:Order');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->hasPermissionTo('RestoreAny:Order');
    }

    public function replicate(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('Replicate:Order');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->hasPermissionTo('Reorder:Order');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->hasPermissionTo('DeleteAny:Order');
    }

    public function viewShipperFees(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ViewShipperFees:Order');
    }

    public function editShipperFees(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('EditShipperFees:Order');
    }

    public function viewCop(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ViewCop:Order');
    }

    public function editCop(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('EditCop:Order');
    }

    public function viewNetFees(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ViewNetFees:Order');
    }

    public function viewFinancialSummary(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ViewFinancialSummary:Order');
    }

    public function editFinancialSummary(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('EditFinancialSummary:Order');
    }

    public function viewCustomerDetails(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ViewCustomerDetails:Order');
    }

    public function editCustomerDetails(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('EditCustomerDetails:Order');
    }

    public function viewShipperDetails(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ViewShipperDetails:Order');
    }

    public function assignShipper(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('AssignShipper:Order');
    }

    public function viewDates(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ViewDates:Order');
    }

    public function viewExternalCode(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ViewExternalCode:Order');
    }

    public function editExternalCode(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('EditExternalCode:Order');
    }

    public function viewOrderNotes(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ViewOrderNotes:Order');
    }

    public function editOrderNotes(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('EditOrderNotes:Order');
    }

    public function viewStatusNotes(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ViewStatusNotes:Order');
    }

    public function editLocked(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('EditLocked:Order');
    }

    public function editClient(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('EditClient:Order');
    }

    public function manageCollections(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ManageCollections:Order');
    }

    public function cancelCollections(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('CancelCollections:Order');
    }

    public function viewLocation(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ViewLocation:Order');
    }

    public function barcodeScanner(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('BarcodeScanner:Order');
    }

    public function changeStatus(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ChangeStatus:Order');
    }

    public function manageReturns(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ManageReturns:Order');
    }

    public function printLabels(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('PrintLabels:Order');
    }

    public function exportData(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasPermissionTo('ExportData:Order');
    }

}