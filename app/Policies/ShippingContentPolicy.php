<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ShippingContent;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShippingContentPolicy
{
    use HandlesAuthorization;
    
    // Shield generates some with lowercase 'c' and some with uppercase 'C'
    // To be safe, we check what exists or just use the generated ones.
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ShippingContent') || $authUser->can('ViewAny:Shippingcontent');
    }

    public function view(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('View:ShippingContent') || $authUser->can('View:Shippingcontent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ShippingContent') || $authUser->can('Create:Shippingcontent');
    }

    public function update(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('Update:ShippingContent') || $authUser->can('Update:Shippingcontent');
    }

    public function delete(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('Delete:ShippingContent') || $authUser->can('Delete:Shippingcontent');
    }

    public function restore(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('Restore:ShippingContent') || $authUser->can('Restore:Shippingcontent');
    }

    public function forceDelete(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('ForceDelete:ShippingContent') || $authUser->can('ForceDelete:Shippingcontent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ShippingContent') || $authUser->can('ForceDeleteAny:Shippingcontent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ShippingContent') || $authUser->can('RestoreAny:Shippingcontent');
    }

    public function replicate(AuthUser $authUser, ShippingContent $shippingContent): bool
    {
        return $authUser->can('Replicate:ShippingContent') || $authUser->can('Replicate:Shippingcontent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ShippingContent') || $authUser->can('Reorder:Shippingcontent');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ShippingContent') || $authUser->can('DeleteAny:Shippingcontent');
    }

}