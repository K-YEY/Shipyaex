<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CollectedShipper;
use Illuminate\Auth\Access\HandlesAuthorization;

class CollectedShipperPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CollectedShipper');
    }

    public function view(AuthUser $authUser, CollectedShipper $collectedShipper): bool
    {
        return $authUser->can('View:CollectedShipper');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CollectedShipper');
    }

    public function update(AuthUser $authUser, CollectedShipper $collectedShipper): bool
    {
        return $authUser->can('Update:CollectedShipper');
    }

    public function delete(AuthUser $authUser, CollectedShipper $collectedShipper): bool
    {
        return $authUser->can('Delete:CollectedShipper');
    }

    public function restore(AuthUser $authUser, CollectedShipper $collectedShipper): bool
    {
        return $authUser->can('Restore:CollectedShipper');
    }

    public function forceDelete(AuthUser $authUser, CollectedShipper $collectedShipper): bool
    {
        return $authUser->can('ForceDelete:CollectedShipper');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CollectedShipper');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CollectedShipper');
    }

    public function replicate(AuthUser $authUser, CollectedShipper $collectedShipper): bool
    {
        return $authUser->can('Replicate:CollectedShipper');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CollectedShipper');
    }

}