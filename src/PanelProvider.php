<?php

namespace Laravilt\Panel;

use Illuminate\Support\ServiceProvider;

abstract class PanelProvider extends ServiceProvider
{
    /**
     * Bootstrap any panel services.
     */
    public function boot(): void
    {
        $panel = $this->panel(Panel::make($this->getPanelId()));

        $panel->register();
    }

    /**
     * Configure the panel.
     */
    abstract public function panel(Panel $panel): Panel;

    /**
     * Get the panel ID from the provider class name.
     */
    protected function getPanelId(): string
    {
        $className = class_basename($this);

        // AdminPanelProvider -> admin
        return strtolower(str_replace('PanelProvider', '', $className));
    }
}
