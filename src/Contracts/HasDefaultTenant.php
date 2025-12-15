<?php

namespace Laravilt\Panel\Contracts;

use Illuminate\Database\Eloquent\Model;
use Laravilt\Panel\Panel;

interface HasDefaultTenant
{
    /**
     * Get the default tenant for the given panel.
     */
    public function getDefaultTenant(Panel $panel): ?Model;
}
