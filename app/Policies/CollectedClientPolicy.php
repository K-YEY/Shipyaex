<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CollectedClient;
use Illuminate\Auth\Access\HandlesAuthorization;

class CollectedClientPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CollectedClient');
    }

    public function view(AuthUser $authUser, CollectedClient $collectedClient): bool
    {
        return $authUser->can('View:CollectedClient');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CollectedClient');
    }

    public function update(AuthUser $authUser, CollectedClient $collectedClient): bool
    {
        return $authUser->can('Update:CollectedClient');
    }

    public function delete(AuthUser $authUser, CollectedClient $collectedClient): bool
    {
        return $authUser->can('Delete:CollectedClient');
    }

    public function restore(AuthUser $authUser, CollectedClient $collectedClient): bool
    {
        return $authUser->can('Restore:CollectedClient');
    }

    public function forceDelete(AuthUser $authUser, CollectedClient $collectedClient): bool
    {
        return $authUser->can('ForceDelete:CollectedClient');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CollectedClient');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CollectedClient');
    }

    public function replicate(AuthUser $authUser, CollectedClient $collectedClient): bool
    {
        return $authUser->can('Replicate:CollectedClient');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CollectedClient');
    }

}