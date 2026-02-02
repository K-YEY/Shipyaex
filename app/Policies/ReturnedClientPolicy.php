<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ReturnedClient;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReturnedClientPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReturnedClient');
    }

    public function view(AuthUser $authUser, ReturnedClient $returnedClient): bool
    {
        return $authUser->can('View:ReturnedClient');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReturnedClient');
    }

    public function update(AuthUser $authUser, ReturnedClient $returnedClient): bool
    {
        return $authUser->can('Update:ReturnedClient');
    }

    public function delete(AuthUser $authUser, ReturnedClient $returnedClient): bool
    {
        return $authUser->can('Delete:ReturnedClient');
    }

    public function restore(AuthUser $authUser, ReturnedClient $returnedClient): bool
    {
        return $authUser->can('Restore:ReturnedClient');
    }

    public function forceDelete(AuthUser $authUser, ReturnedClient $returnedClient): bool
    {
        return $authUser->can('ForceDelete:ReturnedClient');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReturnedClient');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReturnedClient');
    }

    public function replicate(AuthUser $authUser, ReturnedClient $returnedClient): bool
    {
        return $authUser->can('Replicate:ReturnedClient');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReturnedClient');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReturnedClient');
    }

    public function viewAll(AuthUser $authUser, ReturnedClient $returnedClient): bool
    {
        return $authUser->can('ViewAll:ReturnedClient');
    }

    public function viewOwn(AuthUser $authUser, ReturnedClient $returnedClient): bool
    {
        return $authUser->can('ViewOwn:ReturnedClient');
    }

}