<?php

namespace Laravilt\Panel\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravilt\Panel\Panel;

interface HasTenants
{
    /**
     * Get all tenants that the user can access for the given panel.
     *
     * @return Collection<int, Model>
     */
    public function getTenants(Panel $panel): Collection;

    /**
     * Check if the user can access the given tenant.
     */
    public function canAccessTenant(Model $tenant): bool;
}
