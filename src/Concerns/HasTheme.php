<?php

namespace Laravilt\Panel\Concerns;

use Closure;

trait HasTheme
{
    protected string|Closure|null $font = null;

    protected bool|Closure $darkMode = true;

    protected array|Closure $customCss = [];

    protected array|Closure $customJs = [];

    /**
     * Set the panel font.
     */
    public function font(string|Closure|null $font): static
    {
        $this->font = $font;

        return $this;
    }

    /**
     * Get the panel font.
     */
    public function getFont(): ?string
    {
        return $this->evaluate($this->font);
    }

    /**
     * Enable/disable dark mode.
     */
    public function darkMode(bool|Closure $enabled = true): static
    {
        $this->darkMode = $enabled;

        return $this;
    }

    /**
     * Check if dark mode is enabled.
     */
    public function hasDarkMode(): bool
    {
        return $this->evaluate($this->darkMode);
    }

    /**
     * Add custom CSS files.
     */
    public function customCss(array|Closure $files): static
    {
        $this->customCss = $files;

        return $this;
    }

    /**
     * Get custom CSS files.
     */
    public function getCustomCss(): array
    {
        return $this->evaluate($this->customCss);
    }

    /**
     * Add custom JS files.
     */
    public function customJs(array|Closure $files): static
    {
        $this->customJs = $files;

        return $this;
    }

    /**
     * Get custom JS files.
     */
    public function getCustomJs(): array
    {
        return $this->evaluate($this->customJs);
    }
}
