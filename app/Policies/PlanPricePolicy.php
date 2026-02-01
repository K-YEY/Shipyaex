<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PlanPrice;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanPricePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PlanPrice');
    }

    public function view(AuthUser $authUser, PlanPrice $planPrice): bool
    {
        return $authUser->can('View:PlanPrice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PlanPrice');
    }

    public function update(AuthUser $authUser, PlanPrice $planPrice): bool
    {
        return $authUser->can('Update:PlanPrice');
    }

    public function delete(AuthUser $authUser, PlanPrice $planPrice): bool
    {
        return $authUser->can('Delete:PlanPrice');
    }

    public function restore(AuthUser $authUser, PlanPrice $planPrice): bool
    {
        return $authUser->can('Restore:PlanPrice');
    }

    public function forceDelete(AuthUser $authUser, PlanPrice $planPrice): bool
    {
        return $authUser->can('ForceDelete:PlanPrice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PlanPrice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PlanPrice');
    }

    public function replicate(AuthUser $authUser, PlanPrice $planPrice): bool
    {
        return $authUser->can('Replicate:PlanPrice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PlanPrice');
    }

}