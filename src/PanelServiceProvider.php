<?php

namespace Laravilt\Panel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravilt\Panel\Middleware\IdentifyPanel;

class PanelServiceProvider extends ServiceProvider
{
    /**
     * Register any panel services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravilt-panel.php',
            'laravilt.panel'
        );

        $this->app->singleton(PanelRegistry::class);
    }

    /**
     * Bootstrap any panel services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravilt-panel');

        $this->publishes([
            __DIR__.'/../config/laravilt-panel.php' => config_path('laravilt/panel.php'),
        ], 'laravilt-panel-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravilt-panel'),
        ], 'laravilt-panel-views');

        $this->registerRoutes();
        $this->registerBladeComponents();
        $this->registerCommands();
    }

    /**
     * Register panel routes.
     */
    protected function registerRoutes(): void
    {
        $this->app->booted(function () {
            $registry = app(PanelRegistry::class);

            foreach ($registry->all() as $panel) {
                Route::middleware(array_merge(
                    $panel->getMiddleware(),
                    $panel->getAuthMiddleware(),
                    [IdentifyPanel::class.':'.$panel->getId()],
                    [Http\Middleware\SharePanelData::class]
                ))
                    ->prefix($panel->getPath())
                    ->name($panel->getId().'.')
                    ->group(function () use ($panel) {
                        // Register page routes
                        foreach ($panel->getPages() as $pageClass) {
                            $this->registerPageRoute($pageClass, $panel);
                        }

                        // Register resource routes
                        foreach ($panel->getResources() as $resourceClass) {
                            $this->registerResourceRoutes($resourceClass, $panel);
                        }
                    });
            }

            // Register global /login route that redirects to default panel
            $this->registerDefaultLoginRoute($registry);
        });
    }

    /**
     * Register a global /login route that redirects to the default panel's login.
     */
    protected function registerDefaultLoginRoute(PanelRegistry $registry): void
    {
        $defaultPanel = $registry->getDefault();

        if ($defaultPanel && $defaultPanel->hasLogin()) {
            Route::get('login', function () use ($defaultPanel) {
                return redirect()->route($defaultPanel->getId().'.login');
            })->name('login');
        }
    }

    /**
     * Register routes for a resource.
     */
    protected function registerResourceRoutes(string $resourceClass, Panel $panel): void
    {
        $slug = $resourceClass::getSlug();
        $pages = $resourceClass::getPages();

        // Register each resource page
        foreach ($pages as $pageName => $pageConfig) {
            $pageClass = $pageConfig['class'];
            $pagePath = ltrim($pageConfig['path'], '/');

            // Build the full path for the resource page
            $fullPath = $pagePath ? $slug.'/'.$pagePath : $slug;

            // Build the route name
            $routeName = 'resources.'.$slug.'.'.$pageName;

            // Register the page route (same as registerPageRoute but for resource pages)
            $this->registerPageRoute($pageClass, $panel, $routeName, $fullPath);
        }
    }

    /**
     * Overload to support resource routes with custom route names and paths.
     */
    protected function registerPageRoute(string $pageClass, Panel $panel, ?string $customRouteName = null, ?string $customPath = null): void
    {
        $slug = $customPath ?? $pageClass::getSlug();
        $routeName = $customRouteName ?? ($slug ?: 'dashboard');

        Route::get($slug, [$pageClass, 'create'])->name($routeName);

        // Register POST route if page has store method
        if (method_exists($pageClass, 'store')) {
            Route::post($slug, function () use ($pageClass, $panel) {
                $page = app($pageClass);
                $page->panel($panel);
                $page->boot();
                $page->mount();

                return $page->store(request());
            })->name($routeName.'.store');
        }

        // Register DELETE route if page has destroy method
        if (method_exists($pageClass, 'destroy')) {
            Route::delete($slug.'/{id}', function ($id) use ($pageClass, $panel) {
                $page = app($pageClass);
                $page->panel($panel);
                $page->boot();
                $page->mount();

                return $page->destroy(request(), $id);
            })->name($routeName.'.destroy');
        }

        // Register DELETE route for destroyAll if page has that method
        if (method_exists($pageClass, 'destroyAll')) {
            Route::post($slug.'/revoke-all', function () use ($pageClass, $panel) {
                $page = app($pageClass);
                $page->panel($panel);
                $page->boot();
                $page->mount();

                return $page->destroyAll(request());
            })->name($routeName.'.destroy-all');
        }

        // Register DELETE route for destroyOthers if page has that method
        if (method_exists($pageClass, 'destroyOthers')) {
            Route::delete($slug.'/logout-others', function () use ($pageClass, $panel) {
                $page = app($pageClass);
                $page->panel($panel);
                $page->boot();
                $page->mount();

                return $page->destroyOthers(request());
            })->name($routeName.'.logout-others');
        }
    }

    /**
     * Register Blade components.
     */
    protected function registerBladeComponents(): void
    {
        Blade::componentNamespace('Laravilt\\Panel\\View\\Components', 'laravilt');

        // Register anonymous components
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'laravilt');
    }

    /**
     * Register Artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\InstallCommand::class,
                Commands\MakePanelCommand::class,
                Commands\MakePageCommand::class,
                Commands\MakeResourceCommand::class,
            ]);
        }
    }
}
