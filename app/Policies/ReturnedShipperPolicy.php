<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ReturnedShipper;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReturnedShipperPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReturnedShipper');
    }

    public function view(AuthUser $authUser, ReturnedShipper $returnedShipper): bool
    {
        return $authUser->can('View:ReturnedShipper');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReturnedShipper');
    }

    public function update(AuthUser $authUser, ReturnedShipper $returnedShipper): bool
    {
        return $authUser->can('Update:ReturnedShipper');
    }

    public function delete(AuthUser $authUser, ReturnedShipper $returnedShipper): bool
    {
        return $authUser->can('Delete:ReturnedShipper');
    }

    public function restore(AuthUser $authUser, ReturnedShipper $returnedShipper): bool
    {
        return $authUser->can('Restore:ReturnedShipper');
    }

    public function forceDelete(AuthUser $authUser, ReturnedShipper $returnedShipper): bool
    {
        return $authUser->can('ForceDelete:ReturnedShipper');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReturnedShipper');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReturnedShipper');
    }

    public function replicate(AuthUser $authUser, ReturnedShipper $returnedShipper): bool
    {
        return $authUser->can('Replicate:ReturnedShipper');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReturnedShipper');
    }

}