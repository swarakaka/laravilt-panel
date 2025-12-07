<?php

namespace Laravilt\Panel;

use Illuminate\Support\Collection;

class PanelRegistry
{
    /**
     * Registered panels.
     */
    protected array $panels = [];

    /**
     * Default panel ID.
     */
    protected ?string $defaultPanel = null;

    /**
     * Current panel ID.
     */
    protected ?string $currentPanel = null;

    /**
     * Register a panel.
     */
    public function register(Panel $panel): void
    {
        $this->panels[$panel->getId()] = $panel;

        if ($panel->isDefault()) {
            $this->defaultPanel = $panel->getId();
        }

        // Boot the panel
        $panel->boot();
    }

    /**
     * Get a panel by ID.
     */
    public function get(string $id): ?Panel
    {
        return $this->panels[$id] ?? null;
    }

    /**
     * Get all panels.
     */
    public function all(): Collection
    {
        return collect($this->panels);
    }

    /**
     * Get the default panel.
     */
    public function getDefault(): ?Panel
    {
        if ($this->defaultPanel) {
            return $this->get($this->defaultPanel);
        }

        return $this->all()->first();
    }

    /**
     * Set the current panel.
     */
    public function setCurrent(string $id): void
    {
        $this->currentPanel = $id;
    }

    /**
     * Get the current panel.
     */
    public function getCurrent(): ?Panel
    {
        if ($this->currentPanel) {
            return $this->get($this->currentPanel);
        }

        return $this->getDefault();
    }

    /**
     * Check if a panel exists.
     */
    public function has(string $id): bool
    {
        return isset($this->panels[$id]);
    }

    /**
     * Get panel by path.
     */
    public function getByPath(string $path): ?Panel
    {
        $path = trim($path, '/');

        foreach ($this->panels as $panel) {
            if ($panel->getPath() === $path) {
                return $panel;
            }
        }

        return null;
    }
}
