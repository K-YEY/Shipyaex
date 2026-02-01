<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Governorate;
use Illuminate\Auth\Access\HandlesAuthorization;

class GovernoratePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Governorate');
    }

    public function view(AuthUser $authUser, Governorate $governorate): bool
    {
        return $authUser->can('View:Governorate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Governorate');
    }

    public function update(AuthUser $authUser, Governorate $governorate): bool
    {
        return $authUser->can('Update:Governorate');
    }

    public function delete(AuthUser $authUser, Governorate $governorate): bool
    {
        return $authUser->can('Delete:Governorate');
    }

    public function restore(AuthUser $authUser, Governorate $governorate): bool
    {
        return $authUser->can('Restore:Governorate');
    }

    public function forceDelete(AuthUser $authUser, Governorate $governorate): bool
    {
        return $authUser->can('ForceDelete:Governorate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Governorate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Governorate');
    }

    public function replicate(AuthUser $authUser, Governorate $governorate): bool
    {
        return $authUser->can('Replicate:Governorate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Governorate');
    }

}