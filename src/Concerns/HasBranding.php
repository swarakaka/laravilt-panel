<?php

namespace Laravilt\Panel\Concerns;

use Closure;

trait HasBranding
{
    protected string|Closure|null $brandName = null;

    protected string|Closure|null $brandLogo = null;

    protected string|Closure|null $brandLogoHeight = null;

    protected string|Closure|null $favicon = null;

    /**
     * Set the brand name.
     */
    public function brandName(string|Closure|null $name): static
    {
        $this->brandName = $name;

        return $this;
    }

    /**
     * Get the brand name.
     */
    public function getBrandName(): ?string
    {
        return $this->evaluate($this->brandName) ?? config('app.name');
    }

    /**
     * Set the brand logo.
     */
    public function brandLogo(string|Closure|null $logo): static
    {
        $this->brandLogo = $logo;

        return $this;
    }

    /**
     * Get the brand logo.
     */
    public function getBrandLogo(): ?string
    {
        return $this->evaluate($this->brandLogo);
    }

    /**
     * Set the brand logo height.
     */
    public function brandLogoHeight(string|Closure|null $height): static
    {
        $this->brandLogoHeight = $height;

        return $this;
    }

    /**
     * Get the brand logo height.
     */
    public function getBrandLogoHeight(): string
    {
        return $this->evaluate($this->brandLogoHeight) ?? '2rem';
    }

    /**
     * Set the favicon.
     */
    public function favicon(string|Closure|null $favicon): static
    {
        $this->favicon = $favicon;

        return $this;
    }

    /**
     * Get the favicon.
     */
    public function getFavicon(): ?string
    {
        return $this->evaluate($this->favicon);
    }
}
