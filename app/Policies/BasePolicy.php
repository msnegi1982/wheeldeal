<?php

namespace App\Policies;

use App\Models\User;

abstract class BasePolicy
{
    /**
     * Permission prefix, e.g. "vehicles" for view_any_vehicles.
     */
    protected string $resource = '';

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'view_any');
    }

    public function view(User $user): bool
    {
        return $this->allowed($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'create');
    }

    public function update(User $user): bool
    {
        return $this->allowed($user, 'update');
    }

    public function delete(User $user): bool
    {
        return $this->allowed($user, 'delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->allowed($user, 'delete');
    }

    public function restore(User $user): bool
    {
        return $this->allowed($user, 'update');
    }

    public function restoreAny(User $user): bool
    {
        return $this->allowed($user, 'update');
    }

    public function forceDelete(User $user): bool
    {
        return $this->allowed($user, 'delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allowed($user, 'delete');
    }

    public function replicate(User $user): bool
    {
        return $this->allowed($user, 'create');
    }

    public function reorder(User $user): bool
    {
        return $this->allowed($user, 'update');
    }

    public function export(User $user): bool
    {
        return $this->allowed($user, 'export');
    }

    protected function allowed(User $user, string $action): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->can($action.'_'.$this->resource);
    }
}
