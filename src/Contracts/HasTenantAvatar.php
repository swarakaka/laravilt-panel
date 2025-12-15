<?php

namespace Laravilt\Panel\Contracts;

interface HasTenantAvatar
{
    /**
     * Get the avatar URL for the tenant.
     */
    public function getTenantAvatarUrl(): ?string;
}
