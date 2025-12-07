<?php

namespace Laravilt\Panel\Concerns;

use Closure;
use Laravilt\Panel\Navigation\NavigationBuilder;
use Laravilt\Panel\Navigation\UserMenu;

trait HasNavigation
{
    protected ?Closure $navigationCallback = null;

    protected ?Closure $userMenuCallback = null;

    /**
     * Set the navigation callback.
     */
    public function navigation(Closure $callback): static
    {
        $this->navigationCallback = $callback;

        return $this;
    }

    /**
     * Get the navigation.
     */
    public function getNavigation(): array
    {
        if ($this->navigationCallback) {
            $builder = new NavigationBuilder;
            $builder->panel($this);
            call_user_func($this->navigationCallback, $builder);

            return collect($builder->get())
                ->map(fn ($item) => $item->toArray())
                ->all();
        }

        // Build from pages and resources
        return collect(NavigationBuilder::fromPagesAndResources(
            $this->getPages(),
            $this->getResources(),
            $this
        )->get())
            ->map(fn ($item) => $item->toArray())
            ->all();
    }

    /**
     * Set the user menu callback.
     */
    public function userMenu(Closure $callback): static
    {
        $this->userMenuCallback = $callback;

        return $this;
    }

    /**
     * Get the user menu.
     */
    public function getUserMenu(): array
    {
        if ($this->userMenuCallback) {
            $menu = new UserMenu;
            call_user_func($this->userMenuCallback, $menu);

            return $menu->toArray();
        }

        return UserMenu::default()->toArray();
    }

    /**
     * Boot navigation.
     */
    protected function bootNavigation(): void
    {
        // Navigation is built on-demand
    }
}
