<?php

namespace Laravilt\Panel\Concerns;

use Closure;

trait HasTenancy
{
    protected string|Closure|null $tenantModel = null;

    protected string|Closure|null $tenantOwnershipRelationship = null;

    protected string|Closure|null $tenantSlugAttribute = null;

    protected string|Closure|null $tenantRoutePrefix = null;

    protected string|Closure|null $tenantRegistrationPage = null;

    protected string|Closure|null $tenantProfilePage = null;

    protected mixed $tenantBillingProvider = null;

    protected bool $isTenancyEnabled = false;

    protected bool $tenantMenuEnabled = true;

    /**
     * @var array<string, mixed>
     */
    protected array $tenantMenuItems = [];

    /**
     * Enable tenancy for this panel with a model.
     */
    public function tenant(
        string|Closure|null $model,
        ?string $ownershipRelationship = null,
        ?string $slugAttribute = null
    ): static {
        $this->isTenancyEnabled = true;
        $this->tenantModel = $model;

        if ($ownershipRelationship !== null) {
            $this->tenantOwnershipRelationship = $ownershipRelationship;
        }

        if ($slugAttribute !== null) {
            $this->tenantSlugAttribute = $slugAttribute;
        }

        return $this;
    }

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
     * Set the tenant ownership relationship.
     */
    public function tenantOwnershipRelationship(string|Closure|null $relationship): static
    {
        $this->tenantOwnershipRelationship = $relationship;

        return $this;
    }

    /**
     * Set the tenant slug attribute for URL routing.
     */
    public function tenantSlugAttribute(string|Closure|null $attribute): static
    {
        $this->tenantSlugAttribute = $attribute;

        return $this;
    }

    /**
     * Set the tenant route prefix.
     */
    public function tenantRoutePrefix(string|Closure|null $prefix): static
    {
        $this->tenantRoutePrefix = $prefix;

        return $this;
    }

    /**
     * Set the tenant registration page.
     *
     * @param  string|bool|null  $page  Pass a custom page class, true for default, or null/false to disable
     */
    public function tenantRegistration(string|bool|null $page = true): static
    {
        $this->tenantRegistrationPage = $page;

        return $this;
    }

    /**
     * Set the tenant profile/settings page.
     *
     * @param  string|bool|null  $page  Pass a custom page class, true for default, or null/false to disable
     */
    public function tenantProfile(string|bool|null $page = true): static
    {
        $this->tenantProfilePage = $page;

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
     * Enable or disable the tenant menu.
     */
    public function tenantMenu(bool $enabled = true): static
    {
        $this->tenantMenuEnabled = $enabled;

        return $this;
    }

    /**
     * Set custom tenant menu items.
     *
     * @param  array<string, mixed>  $items
     */
    public function tenantMenuItems(array $items): static
    {
        $this->tenantMenuItems = $items;

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
     * Check if tenant registration is enabled.
     * Returns true if page is explicitly set OR if using default (true means allow registration).
     */
    public function hasTenantRegistration(): bool
    {
        // If explicitly set to a page class, it's enabled
        // If set to true (boolean), use default
        // If null or false, disabled
        return $this->tenantRegistrationPage !== null && $this->tenantRegistrationPage !== false;
    }

    /**
     * Check if tenant profile/settings is enabled.
     */
    public function hasTenantProfile(): bool
    {
        return $this->tenantProfilePage !== null && $this->tenantProfilePage !== false;
    }

    /**
     * Check if using default tenant registration page.
     */
    public function usesDefaultTenantRegistration(): bool
    {
        return $this->tenantRegistrationPage === true || $this->tenantRegistrationPage === 'default';
    }

    /**
     * Check if using default tenant profile page.
     */
    public function usesDefaultTenantProfile(): bool
    {
        return $this->tenantProfilePage === true || $this->tenantProfilePage === 'default';
    }

    /**
     * Check if tenant menu is enabled.
     */
    public function hasTenantMenu(): bool
    {
        return $this->tenantMenuEnabled;
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
        $relationship = $this->evaluate($this->tenantOwnershipRelationship);

        if ($relationship !== null) {
            return $relationship;
        }

        // Default to guessing from model name
        $model = $this->getTenantModel();

        if ($model === null) {
            return null;
        }

        return str(class_basename($model))->camel()->toString();
    }

    /**
     * Get the tenant slug attribute.
     * Defaults to 'slug' if the model has it, otherwise 'id'.
     */
    public function getTenantSlugAttribute(): string
    {
        $attribute = $this->evaluate($this->tenantSlugAttribute);

        if ($attribute !== null) {
            return $attribute;
        }

        // Try to detect if model has 'slug' column
        $model = $this->getTenantModel();
        if ($model !== null) {
            try {
                $instance = new $model;
                if (method_exists($instance, 'getSlugAttribute') ||
                    in_array('slug', $instance->getFillable()) ||
                    property_exists($instance, 'slug')) {
                    return 'slug';
                }
            } catch (\Throwable) {
                // Fall through to default
            }
        }

        return 'id';
    }

    /**
     * Get the tenant route prefix.
     */
    public function getTenantRoutePrefix(): ?string
    {
        return $this->evaluate($this->tenantRoutePrefix);
    }

    /**
     * Get the tenant registration page.
     */
    public function getTenantRegistrationPage(): ?string
    {
        return $this->tenantRegistrationPage;
    }

    /**
     * Get the tenant profile page.
     */
    public function getTenantProfilePage(): ?string
    {
        return $this->tenantProfilePage;
    }

    /**
     * Get the tenant billing provider.
     */
    public function getTenantBillingProvider(): mixed
    {
        return $this->tenantBillingProvider;
    }

    /**
     * Get the tenant menu items.
     *
     * @return array<string, mixed>
     */
    public function getTenantMenuItems(): array
    {
        return $this->tenantMenuItems;
    }

    /**
     * Get the tenant route parameter name.
     */
    public function getTenantRouteParameterName(): string
    {
        $model = $this->getTenantModel();

        if ($model === null) {
            return 'tenant';
        }

        return str(class_basename($model))->camel()->toString();
    }

    /**
     * Get the tenant URL segment for the given tenant.
     */
    public function getTenantUrlSegment(mixed $tenant): string
    {
        $slugAttribute = $this->getTenantSlugAttribute();

        return (string) $tenant->{$slugAttribute};
    }

    /**
     * Build the tenant URL for the panel.
     */
    public function getTenantUrl(mixed $tenant, string $path = ''): string
    {
        $panelPath = trim($this->getPath(), '/');
        $tenantSegment = $this->getTenantUrlSegment($tenant);
        $basePath = $panelPath ? "/{$panelPath}/{$tenantSegment}" : "/{$tenantSegment}";

        if ($path) {
            $path = '/'.trim($path, '/');
        }

        return $basePath.$path ?: '/';
    }
}
