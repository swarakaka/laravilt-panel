<?php

namespace Laravilt\Panel\Concerns;

use Closure;

trait HasTenancy
{
    protected string|Closure|null $tenantModel = null;

    protected string|Closure|null $tenantOwnershipRelationship = null;

    protected mixed $tenantBillingProvider = null;

    protected bool $isTenancyEnabled = false;

    /**
     * Enable tenancy for this panel.
     */
    public function tenancy(string|Closure|null $tenantModel = null): static
    {
        $this->isTenancyEnabled = true;
        $this->tenantModel = $tenantModel;

        return $this;
    }

    /**
     * Set the tenant model for this panel.
     */
    public function tenant(string|Closure|null $tenantModel): static
    {
        $this->isTenancyEnabled = true;
        $this->tenantModel = $tenantModel;

        return $this;
    }

    /**
     * Set the tenant ownership relationship.
     */
    public function tenantOwnershipRelationship(string|Closure|null $relationship): static
    {
        $this->tenantOwnershipRelationship = $relationship;

        return $this;
    }

    /**
     * Set the tenant billing provider.
     */
    public function tenantBillingProvider(mixed $provider): static
    {
        $this->tenantBillingProvider = $provider;

        return $this;
    }

    /**
     * Check if tenancy is enabled.
     */
    public function hasTenancy(): bool
    {
        return $this->isTenancyEnabled;
    }

    /**
     * Get the tenant model.
     */
    public function getTenantModel(): ?string
    {
        return $this->evaluate($this->tenantModel);
    }

    /**
     * Get the tenant ownership relationship.
     */
    public function getTenantOwnershipRelationship(): ?string
    {
        return $this->evaluate($this->tenantOwnershipRelationship);
    }

    /**
     * Get the tenant billing provider.
     */
    public function getTenantBillingProvider(): mixed
    {
        return $this->tenantBillingProvider;
    }
}
