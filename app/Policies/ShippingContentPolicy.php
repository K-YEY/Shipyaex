<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ShippingContent;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShippingContentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ShippingContent');
    }

    public function view(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('View:ShippingContent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ShippingContent');
    }

    public function update(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('Update:ShippingContent');
    }

    public function delete(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('Delete:ShippingContent');
    }

    public function restore(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('Restore:ShippingContent');
    }

    public function forceDelete(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('ForceDelete:ShippingContent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ShippingContent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ShippingContent');
    }

    public function replicate(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('Replicate:ShippingContent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ShippingContent');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ShippingContent');
    }

}