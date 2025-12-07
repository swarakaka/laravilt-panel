<?php

namespace Laravilt\Panel\Contracts;

use Laravilt\Panel\Panel;

interface HasPanel
{
    /**
     * Set the panel instance.
     */
    public function panel(Panel $panel): static;

    /**
     * Get the panel instance.
     */
    public function getPanel(): Panel;
}
