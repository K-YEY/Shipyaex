<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RefusedReason;
use Illuminate\Auth\Access\HandlesAuthorization;

class RefusedReasonPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RefusedReason');
    }

    public function view(AuthUser $authUser, RefusedReason $refusedReason): bool
    {
        return $authUser->can('View:RefusedReason');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RefusedReason');
    }

    public function update(AuthUser $authUser, RefusedReason $refusedReason): bool
    {
        return $authUser->can('Update:RefusedReason');
    }

    public function delete(AuthUser $authUser, RefusedReason $refusedReason): bool
    {
        return $authUser->can('Delete:RefusedReason');
    }

    public function restore(AuthUser $authUser, RefusedReason $refusedReason): bool
    {
        return $authUser->can('Restore:RefusedReason');
    }

    public function forceDelete(AuthUser $authUser, RefusedReason $refusedReason): bool
    {
        return $authUser->can('ForceDelete:RefusedReason');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RefusedReason');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RefusedReason');
    }

    public function replicate(AuthUser $authUser, RefusedReason $refusedReason): bool
    {
        return $authUser->can('Replicate:RefusedReason');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RefusedReason');
    }

}