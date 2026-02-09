<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\OrderStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderStatusPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('ViewAny:OrderStatus');
    }

    public function view(User $user, OrderStatus $orderStatus): bool
    {
        return $user->isAdmin() || $user->can('View:OrderStatus');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->can('Create:OrderStatus');
    }

    public function update(User $user, OrderStatus $orderStatus): bool
    {
        return $user->isAdmin() || $user->can('Update:OrderStatus');
    }

    public function delete(User $user, OrderStatus $orderStatus): bool
    {
        return $user->isAdmin() || $user->can('Delete:OrderStatus');
    }

    public function restore(User $user, OrderStatus $orderStatus): bool
    {
        return $user->isAdmin() || $user->can('Restore:OrderStatus');
    }

    public function forceDelete(User $user, OrderStatus $orderStatus): bool
    {
        return $user->isAdmin() || $user->can('ForceDelete:OrderStatus');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('ForceDeleteAny:OrderStatus');
    }

    public function restoreAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('RestoreAny:OrderStatus');
    }

    public function replicate(User $user, OrderStatus $orderStatus): bool
    {
        return $user->isAdmin() || $user->can('Replicate:OrderStatus');
    }

    public function reorder(User $user): bool
    {
        return $user->isAdmin() || $user->can('Reorder:OrderStatus');
    }

}