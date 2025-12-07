<?php

namespace Laravilt\Panel\Components;

/**
 * Panel Sidebar Component
 *
 * Renders the panel sidebar with:
 * - Brand logo and name
 * - Dynamic navigation from panel
 * - User menu
 * - Collapsible functionality
 */
class Sidebar extends PanelComponent
{
    protected string $view = 'laravilt-panel::components.sidebar';

    protected array $navigation = [];

    protected bool $collapsible = true;

    protected string $variant = 'inset';

    /**
     * Set navigation items.
     */
    public function navigation(array $navigation): static
    {
        $this->navigation = $navigation;

        return $this;
    }

    /**
     * Get navigation items.
     */
    public function getNavigation(): array
    {
        if (! empty($this->navigation)) {
            return $this->navigation;
        }

        // Build navigation from panel
        $panel = $this->getPanel();
        if (! $panel) {
            return [];
        }

        return $panel->getNavigation();
    }

    /**
     * Set whether sidebar is collapsible.
     */
    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    /**
     * Check if sidebar is collapsible.
     */
    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    /**
     * Set sidebar variant.
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * Get sidebar variant.
     */
    public function getVariant(): string
    {
        return $this->variant;
    }

    /**
     * Serialize component for Laravilt (Blade + Vue.js).
     */
    public function toLaraviltProps(): array
    {
        return array_merge(parent::toLaraviltProps(), [
            'navigation' => $this->getNavigation(),
            'collapsible' => $this->isCollapsible(),
            'variant' => $this->getVariant(),
        ]);
    }
}
