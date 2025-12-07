<?php

namespace Laravilt\Panel\Concerns;

trait HasBreadcrumbs
{
    /**
     * Get the page breadcrumbs.
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];

        // Add home/dashboard
        $breadcrumbs[] = [
            'label' => 'Dashboard',
            'url' => $this->getPanel()->url(),
        ];

        // Add navigation group if exists
        if ($group = static::getNavigationGroup()) {
            $breadcrumbs[] = [
                'label' => $group,
                'url' => null,
            ];
        }

        // Add resource list page if this is a resource page (Create, Edit, View)
        if (method_exists(static::class, 'getResource')) {
            $resource = static::getResource();
            if ($resource) {
                // Only add the list page breadcrumb if we're NOT on the list page itself
                if (!($this instanceof \Laravilt\Panel\Pages\ListRecords)) {
                    $breadcrumbs[] = [
                        'label' => $resource::getPluralLabel() ?? $resource::getLabel(),
                        'url' => $resource::getUrl('list'),
                    ];
                }
            }
        }

        // Add current page
        $breadcrumbs[] = [
            'label' => static::getTitle(),
            'url' => null,
        ];

        return $breadcrumbs;
    }
}
