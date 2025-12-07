<?php

namespace Laravilt\Panel\Components;

use Laravilt\Support\Component;

/**
 * Base Panel Component
 *
 * Foundation for all Panel UI components. Provides:
 * - Integration with panel context
 * - Navigation and breadcrumb support
 * - User authentication context
 * - Theme and branding
 */
abstract class PanelComponent extends Component
{
    /**
     * The panel instance this component belongs to.
     */
    protected ?\Laravilt\Panel\Panel $panel = null;

    /**
     * Set the panel context for this component.
     */
    public function panel(\Laravilt\Panel\Panel $panel): static
    {
        $this->panel = $panel;

        return $this;
    }

    /**
     * Get the panel instance.
     */
    public function getPanel(): ?\Laravilt\Panel\Panel
    {
        return $this->panel ?? app(\Laravilt\Panel\PanelRegistry::class)->getCurrent();
    }

    /**
     * Get the authenticated user.
     */
    protected function getUser(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        return request()->user();
    }

    /**
     * Serialize component for Laravilt (Blade + Vue.js).
     */
    public function toLaraviltProps(): array
    {
        $panel = $this->getPanel();

        return array_merge(parent::toLaraviltProps(), [
            'panel' => $panel ? [
                'id' => $panel->getId(),
                'path' => $panel->getPath(),
                'brandName' => $panel->getBrandName(),
                'brandLogo' => $panel->getBrandLogo(),
            ] : null,
            'user' => $this->getUser() ? [
                'name' => $this->getUser()->name,
                'email' => $this->getUser()->email,
            ] : null,
            'navigation' => $panel ? $panel->getNavigation() : [],
        ]);
    }
}
