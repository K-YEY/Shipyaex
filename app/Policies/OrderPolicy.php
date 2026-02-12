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
        return $authUser->can('ViewAny:Order');
    }

    public function view(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('View:Order');
    }

    public function create(AuthUser $authUser): bool
    {
        // Check if user has basic create permission
        if (!$authUser->can('Create:Order')) {
            return false;
        }

        // Check if user has bypass permission
        if ($authUser->can('BypassWorkingHours:Order')) {
            return true;
        }

        // Enforce working hours
        $start = \App\Models\Setting::get('working_hours_orders_start', '08:00');
        $end   = \App\Models\Setting::get('working_hours_orders_end', '22:00');
        $now   = \Carbon\Carbon::now()->format('H:i');

        return $now >= $start && $now <= $end;
    }

    public function update(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('Update:Order');
    }

    public function delete(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('Delete:Order');
    }

    public function restore(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('Restore:Order');
    }

    public function forceDelete(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ForceDelete:Order');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Order');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Order');
    }

    public function replicate(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('Replicate:Order');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Order');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Order');
    }

    public function viewShipperFees(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewShipperFees:Order');
    }

    public function editShipperFees(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('EditShipperFees:Order');
    }

    public function viewCop(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewCop:Order');
    }

    public function editCop(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('EditCop:Order');
    }

    public function viewNetFees(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewNetFees:Order');
    }

    public function viewFinancialSummary(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewFinancialSummary:Order');
    }

    public function editFinancialSummary(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('EditFinancialSummary:Order');
    }

    public function viewCustomerDetails(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewCustomerDetails:Order');
    }

    public function editCustomerDetails(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('EditCustomerDetails:Order');
    }

    public function viewShipperDetails(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewShipperDetails:Order');
    }

    public function assignShipper(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('AssignShipper:Order');
    }

    public function viewDates(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewRegistrationDateColumn:Order') || $authUser->can('ViewShipperDateColumn:Order') || $authUser->can('ViewDatesColumn:Order');
    }

    public function viewExternalCode(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewExternalCodeColumn:Order') || $authUser->can('ViewExternalCodeField:Order');
    }

    public function editExternalCode(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('EditExternalCodeField:Order');
    }

    public function viewOrderNotes(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewOrderNotesColumn:Order') || $authUser->can('ViewOrderNotesField:Order');
    }

    public function editOrderNotes(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('EditOrderNotesField:Order');
    }

    public function viewStatusNotes(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewStatusNotesColumn:Order');
    }

    public function editLocked(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('EditLocked:Order');
    }

    public function editClient(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('EditClient:Order');
    }

    public function manageCollections(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ManageShipperCollectionAction:Order') || $authUser->can('ManageClientCollectionAction:Order');
    }

    public function cancelCollections(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ManageShipperCollectionAction:Order') || $authUser->can('ManageClientCollectionAction:Order');
    }

    public function viewLocation(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewLocation:Order');
    }

    public function barcodeScanner(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('BarcodeScanner:Order');
    }

    public function changeStatus(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ChangeStatusAction:Order') || $authUser->can('ChangeStatusField:Order');
    }

    public function manageReturns(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ManageShipperReturnAction:Order') || $authUser->can('ManageClientReturnAction:Order');
    }

    public function printLabels(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('PrintLabels:Order');
    }

    public function exportData(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ExportData:Order');
    }

    public function viewAll(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewAll:Order');
    }

    public function viewOwn(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewOwn:Order');
    }

    public function viewAssigned(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ViewAssigned:Order');
    }

}