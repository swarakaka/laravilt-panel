<?php

namespace Laravilt\Panel\Contracts;

interface HasTenantName
{
    /**
     * Get the display name for the tenant.
     */
    public function getTenantName(): string;
}
