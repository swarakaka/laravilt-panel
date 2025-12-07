<?php

namespace Laravilt\Panel\Components;

/**
 * Panel Page Layout Component
 *
 * Renders a complete page layout with:
 * - Sidebar
 * - Header with breadcrumbs
 * - Content area
 */
class PageLayout extends PanelComponent
{
    protected string $view = 'laravilt-panel::components.page-layout';

    protected array $breadcrumbs = [];

    protected ?string $heading = null;

    protected ?string $subheading = null;

    protected array $headerActions = [];

    protected ?string $content = null;

    /**
     * Set breadcrumbs.
     */
    public function breadcrumbs(array $breadcrumbs): static
    {
        $this->breadcrumbs = $breadcrumbs;

        return $this;
    }

    /**
     * Get breadcrumbs.
     */
    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }

    /**
     * Set page heading.
     */
    public function heading(string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    /**
     * Get page heading.
     */
    public function getHeading(): ?string
    {
        return $this->heading;
    }

    /**
     * Set page subheading.
     */
    public function subheading(?string $subheading): static
    {
        $this->subheading = $subheading;

        return $this;
    }

    /**
     * Get page subheading.
     */
    public function getSubheading(): ?string
    {
        return $this->subheading;
    }

    /**
     * Set header actions.
     */
    public function headerActions(array $actions): static
    {
        $this->headerActions = $actions;

        return $this;
    }

    /**
     * Get header actions.
     */
    public function getHeaderActions(): array
    {
        return $this->headerActions;
    }

    /**
     * Set page content.
     */
    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get page content.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Serialize component for Laravilt (Blade + Vue.js).
     */
    public function toLaraviltProps(): array
    {
        return array_merge(parent::toLaraviltProps(), [
            'breadcrumbs' => $this->getBreadcrumbs(),
            'heading' => $this->getHeading(),
            'subheading' => $this->getSubheading(),
            'headerActions' => $this->getHeaderActions(),
            'content' => $this->getContent(),
        ]);
    }
}
