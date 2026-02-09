<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\RefusedReason;
use Illuminate\Auth\Access\HandlesAuthorization;

class RefusedReasonPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('ViewAny:RefusedReason');
    }

    public function view(User $user, RefusedReason $refusedReason): bool
    {
        return $user->isAdmin() || $user->can('View:RefusedReason');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->can('Create:RefusedReason');
    }

    public function update(User $user, RefusedReason $refusedReason): bool
    {
        return $user->isAdmin() || $user->can('Update:RefusedReason');
    }

    public function delete(User $user, RefusedReason $refusedReason): bool
    {
        return $user->isAdmin() || $user->can('Delete:RefusedReason');
    }

    public function restore(User $user, RefusedReason $refusedReason): bool
    {
        return $user->isAdmin() || $user->can('Restore:RefusedReason');
    }

    public function forceDelete(User $user, RefusedReason $refusedReason): bool
    {
        return $user->isAdmin() || $user->can('ForceDelete:RefusedReason');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('ForceDeleteAny:RefusedReason');
    }

    public function restoreAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('RestoreAny:RefusedReason');
    }

    public function replicate(User $user, RefusedReason $refusedReason): bool
    {
        return $user->isAdmin() || $user->can('Replicate:RefusedReason');
    }

    public function reorder(User $user): bool
    {
        return $user->isAdmin() || $user->can('Reorder:RefusedReason');
    }

}