<?php

namespace Laravilt\Panel\Pages;

class Dashboard extends Page
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -999;

    protected static ?string $slug = '';

    /**
     * Get breadcrumbs for dashboard.
     */
    public function getBreadcrumbs(): array
    {
        return [
            [
                'label' => static::getTitle(),
                'url' => null,
            ],
        ];
    }

    /**
     * Render the dashboard using Inertia.
     */
    public function render(?string $component = null)
    {
        // Determine the Inertia component based on panel path
        $panelPath = $this->getPanel()->getPath();
        $component = $panelPath.'/Dashboard';

        return \Inertia\Inertia::render($component, [
            'breadcrumbs' => $this->getBreadcrumbs(),
        ]);
    }
}
