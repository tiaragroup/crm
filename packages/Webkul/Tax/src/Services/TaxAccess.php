<?php

namespace Webkul\Tax\Services;

use Illuminate\Database\Eloquent\Builder;
use Webkul\User\Contracts\User;

class TaxAccess
{
    public function authorize(User $actor, string $permission): void
    {
        abort_unless($actor->status && ($actor->role?->permission_type === 'all'
            || in_array($permission, $actor->role?->permissions ?? [], true)), 403);
    }

    public function scope(Builder $query, User $actor, string $column = 'created_by'): Builder
    {
        if ($actor->view_permission === 'global') {
            return $query;
        }

        $ids = [$actor->id];
        if ($actor->view_permission === 'group') {
            $ids = $actor->groups()->with('users')->get()->flatMap->users->pluck('id')->push($actor->id)->unique()->all();
        }

        return $query->whereIn($column, $ids);
    }
}
