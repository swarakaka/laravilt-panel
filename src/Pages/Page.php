<?php

namespace Laravilt\Panel\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\View\Component as ViewComponent;
use Laravilt\Actions\Concerns\InteractsWithActions;
use Laravilt\Actions\Contracts\HasActions;
use Laravilt\Forms\Concerns\InteractsWithForms;
use Laravilt\Forms\Contracts\HasForms;
use Laravilt\Panel\Concerns\HasAuth;
use Laravilt\Panel\Concerns\HasBreadcrumbs;
use Laravilt\Panel\Concerns\HasMiddleware;
use Laravilt\Panel\Concerns\HasNavigation;
use Laravilt\Panel\Concerns\HasResources;
use Laravilt\Panel\Concerns\HasWidgets;
use Laravilt\Panel\Contracts\HasPanel as HasPanelContract;
use Laravilt\Panel\Facades\Panel as PanelFacade;
use Laravilt\Panel\Panel;
use Laravilt\Support\Concerns\EvaluatesClosures;

abstract class Page extends ViewComponent implements HasActions, HasForms, HasPanelContract, Htmlable
{
    use Conditionable;
    use EvaluatesClosures;
    use HasAuth;
    use HasBreadcrumbs;
    use HasMiddleware;
    use HasNavigation;
    use HasResources;
    use HasWidgets;
    use InteractsWithActions;
    use InteractsWithForms;

    /**
     * The cluster this page belongs to.
     */
    protected static ?string $cluster = null;

    /**
     * The resource this page belongs to.
     */
    protected static ?string $resource = null;

    /**
     * The page title.
     */
    protected static ?string $title = null;

    /**
     * The page navigation label.
     */
    protected static ?string $navigationLabel = null;

    /**
     * The custom Inertia component for this page.
     */
    protected ?string $component = null;

    /**
     * The page navigation icon.
     */
    protected static ?string $navigationIcon = null;

    /**
     * The page navigation sort order.
     */
    protected static ?int $navigationSort = null;

    /**
     * The page navigation group.
     */
    protected static ?string $navigationGroup = null;

    /**
     * The page route name.
     */
    protected static ?string $slug = null;

    /**
     * The page view.
     */
    protected static string $view = 'laravilt-panel::pages.blank';

    /**
     * The panel instance.
     */
    protected ?Panel $panel = null;

    /**
     * Whether this page should be shown in navigation.
     */
    protected static bool $shouldRegisterNavigation = true;

    /**
     * Top hook content (rendered above the form).
     * Can be HTML string, Closure returning HTML, or array with 'component' and 'props'.
     */
    protected string|\Closure|array|null $topHook = null;

    /**
     * Bottom hook content (rendered below the form).
     * Can be HTML string, Closure returning HTML, or array with 'component' and 'props'.
     */
    protected string|\Closure|array|null $bottomHook = null;

    /**
     * Get the page title.
     */
    public static function getTitle(): string
    {
        return static::$title ?? static::getLabel();
    }

    /**
     * Get the page label.
     */
    public static function getLabel(): string
    {
        return static::$navigationLabel ?? (string) str(class_basename(static::class))
            ->beforeLast('Page')
            ->kebab()
            ->replace('-', ' ')
            ->title();
    }

    /**
     * Get the cluster this page belongs to.
     */
    public static function getCluster(): ?string
    {
        return static::$cluster;
    }

    /**
     * Get the resource this page belongs to.
     */
    public static function getResource(): ?string
    {
        return static::$resource;
    }

    /**
     * Get the navigation icon.
     */
    public static function getNavigationIcon(): ?string
    {
        return static::$navigationIcon;
    }

    /**
     * Get the navigation sort order.
     */
    public static function getNavigationSort(): int
    {
        return static::$navigationSort ?? 0;
    }

    /**
     * Get the navigation group.
     */
    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup;
    }

    /**
     * Get the page slug.
     */
    public static function getSlug(): string
    {
        return static::$slug ?? (string) str(class_basename(static::class))
            ->beforeLast('Page')
            ->kebab();
    }

    /**
     * Get the page URL.
     */
    public static function getUrl(?Panel $panel = null): string
    {
        $panel = $panel ?? PanelFacade::getCurrent();

        return $panel?->url(static::getSlug()) ?? '#';
    }

    /**
     * Get the page route name.
     */
    public static function getRouteName(?Panel $panel = null): string
    {
        $panel = $panel ?? PanelFacade::getCurrent();

        return $panel?->route(static::getSlug()) ?? '';
    }

    /**
     * Create a route configuration for resource pages.
     */
    public static function route(string $path): array
    {
        return [
            'class' => static::class,
            'path' => $path,
        ];
    }

    /**
     * Check if should register navigation.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::$shouldRegisterNavigation;
    }

    /**
     * Set the panel instance.
     */
    public function panel(Panel $panel): static
    {
        $this->panel = $panel;

        return $this;
    }

    /**
     * Get the panel instance.
     */
    public function getPanel(): Panel
    {
        return $this->panel ?? PanelFacade::getCurrent();
    }

    /**
     * Get the page heading.
     */
    public function getHeading(): string
    {
        return static::getTitle();
    }

    /**
     * Get the page subheading.
     */
    public function getSubheading(): ?string
    {
        return null;
    }

    /**
     * Get the view data.
     */
    protected function getViewData(): array
    {
        return [];
    }

    /**
     * Get the page layout.
     */
    public function getLayout(): string
    {
        return \Laravilt\Panel\Enums\PageLayout::Panel->value;
    }

    /**
     * Get extra props for Inertia response.
     */
    protected function getInertiaProps(): array
    {
        return [];
    }

    /**
     * Get cluster navigation items for sidebar.
     */
    protected function getClusterNavigation(): ?array
    {
        $clusterClass = static::getCluster();

        if (! $clusterClass) {
            return null;
        }

        $panel = $this->getPanel();
        $clusterSlug = $clusterClass::getSlug();

        // Get all pages that belong to this cluster
        $clusterPages = collect($panel->getPages())
            ->filter(function ($pageClass) use ($clusterClass) {
                // Skip clusters themselves
                if (is_subclass_of($pageClass, \Laravilt\Panel\Cluster::class)) {
                    return false;
                }

                return $pageClass::getCluster() === $clusterClass;
            })
            ->unique()
            ->sortBy(fn ($pageClass) => $pageClass::getNavigationSort() ?? 0)
            ->map(function ($pageClass) use ($panel, $clusterSlug) {
                $pageSlug = $pageClass::getSlug();

                return [
                    'title' => $pageClass::getTitle(),
                    'href' => $panel->url("{$clusterSlug}/{$pageSlug}"),
                    'icon' => $pageClass::getNavigationIcon(),
                ];
            })
            ->values()
            ->all();

        return $clusterPages;
    }

    /**
     * Set the top hook content.
     */
    public function topHook(string|\Closure|array|null $content): static
    {
        $this->topHook = $content;

        return $this;
    }

    /**
     * Get the top hook content.
     *
     * @return string|array|null HTML string or array with component data
     */
    public function getTopHook(): string|array|null
    {
        $content = $this->evaluate($this->topHook);

        // If it's already an array (component definition), return as is
        if (is_array($content)) {
            return $content;
        }

        return $content;
    }

    /**
     * Set the bottom hook content.
     *
     * @param  string|\Closure|array|null  $content  HTML string, Closure, or ['component' => 'ComponentName', 'props' => []]
     */
    public function bottomHook(string|\Closure|array|null $content): static
    {
        $this->bottomHook = $content;

        return $this;
    }

    /**
     * Get the bottom hook content.
     *
     * @return string|array|null HTML string or array with component data
     */
    public function getBottomHook(): string|array|null
    {
        $content = $this->evaluate($this->bottomHook);

        // If it's already an array (component definition), return as is
        if (is_array($content)) {
            return $content;
        }

        return $content;
    }

    /**
     * Display the page (GET request handler).
     *
     * This method is called by Laravel routing when a page is accessed.
     * Override this method if you need custom logic before rendering.
     * Child classes can add route parameters (like Model $record) as needed.
     *
     * If the child class defines an __invoke() method, it will be called instead
     * of the default render() method, allowing for custom page rendering.
     */
    public function create(\Illuminate\Http\Request $request, ...$parameters)
    {
        // Check if child class has a custom __invoke method
        // Use ReflectionMethod to check if __invoke is defined in the child class (not inherited)
        $reflectionClass = new \ReflectionClass($this);
        if ($reflectionClass->hasMethod('__invoke')) {
            $method = $reflectionClass->getMethod('__invoke');
            // Only call __invoke if it's defined in the child class, not inherited from a parent
            if ($method->getDeclaringClass()->getName() === static::class) {
                return $this->__invoke();
            }
        }

        return $this->render();
    }

    /**
     * Get the URL for executing actions on this page.
     */
    protected function getActionUrl(): string
    {
        return "/actions/execute";
    }

    /**
     * Configure actions in schema with component context.
     * Sets the Page class on actions so they can auto-configure based on their context.
     */
    protected function configureActions(array $schema): array
    {
        foreach ($schema as $item) {
            // Check if this is a Grid or Table
            if ($item instanceof \Laravilt\Grids\Grid || $item instanceof \Laravilt\Tables\Table) {
                $this->configureGridTableActions($item);
            }
        }

        return $schema;
    }

    /**
     * Set component context on Grid/Table record actions.
     */
    protected function configureGridTableActions($gridOrTable): void
    {
        // Set the Page class as component on all record actions
        if (method_exists($gridOrTable, 'getRecordActions')) {
            $actions = $gridOrTable->getRecordActions();

            foreach ($actions as $action) {
                // Set this Page class as the component so actions know their context
                $action->component(static::class);
            }
        }
    }

    /**
     * Execute an action by name.
     *
     * This method is called via POST when an action is triggered.
     * It will execute either the action's closure or call a public method on the page.
     */
    public function executeAction(\Illuminate\Http\Request $request)
    {
        $actionName = $request->input('action');

        if (! $actionName) {
            return back()->with('error', 'Action name is required');
        }

        // Find the action in the page's actions
        $action = collect($this->getHeaderActions())->first(function ($action) use ($actionName) {
            return $action->getName() === $actionName;
        });

        if (! $action) {
            return back()->with('error', "Action '{$actionName}' not found");
        }

        // Check authorization
        if (! $action->canAuthorize()) {
            return back()->with('error', 'Unauthorized');
        }

        // Get form data if provided
        $data = $request->except(['action', '_token']);

        // If action has a closure, execute it
        if ($action->getAction() !== null) {
            $result = $action->execute(null, $data);

            // If result is a response, return it
            if ($result instanceof \Illuminate\Http\Response || $result instanceof \Illuminate\Http\RedirectResponse) {
                return $result;
            }

            return back();
        }

        // Otherwise, try to call a public method on the page with the same name
        $methodName = 'handle'.str($actionName)->studly();

        if (method_exists($this, $methodName) && (new \ReflectionMethod($this, $methodName))->isPublic()) {
            $result = $this->{$methodName}($request, $data);

            // If result is a response, return it
            if ($result instanceof \Illuminate\Http\Response || $result instanceof \Illuminate\Http\RedirectResponse) {
                return $result;
            }

            return back();
        }

        return back()->with('error', "No handler found for action '{$actionName}'");
    }

    /**
     * Render the page.
     *
     * Supports both rendering modes:
     * 1. Inertia mode (default) - renders via Inertia.js
     * 2. Component mode - renders via PageLayout component with Blade bridge
     */
    public function render(?string $component = null)
    {
        // Store custom component if provided
        if ($component !== null) {
            $this->component = $component;
        }

        // Check if should use component mode (when page has custom view or uses component rendering)
        if ($this->shouldUseComponentMode()) {
            $html = $this->renderAsComponent();

            // If this is a Laravilt AJAX request, return JSON
            if (request()->header('X-Laravilt') === 'true') {
                return response()->json([
                    'html' => $html,
                    'title' => $this->getHeading(),
                ]);
            }

            return $html;
        }

        // Default: Inertia mode
        $clusterClass = static::getCluster();
        $clusterNavigation = $this->getClusterNavigation();

        // Get and configure schema
        $schema = method_exists($this, 'getSchema') ? $this->getSchema() : [];
        $schema = $this->configureActions($schema);

        $baseProps = [
            'page' => [
                'heading' => $this->getHeading(),
                'subheading' => $this->getSubheading(),
                'headerActions' => collect($this->getHeaderActions())
                    ->filter(fn ($action) => is_object($action))
                    ->map(function ($action) {
                        // Clone action to avoid modifying the original
                        $actionClone = clone $action;

                        // Set component context so actions can auto-configure
                        $actionClone->component(static::class);

                        // If action works with records (Edit/View on EditRecord/ViewRecord pages), configure it
                        if (isset($this->record)) {
                            $actionClone->resolveRecordContext($this->record->id);
                        }

                        return $actionClone->toArray();
                    })->values()->all(),
                'actionUrl' => $this->getActionUrl(), // URL for executing actions
            ],
            'breadcrumbs' => $this->getBreadcrumbs(),
            'pageSlug' => static::getSlug(),
            'panelId' => $this->getPanel()->getId(),
            'layout' => $this->getLayout(),
            'schema' => collect($schema)
                ->filter(fn ($item) => is_object($item) && (method_exists($item, 'toInertiaProps') || method_exists($item, 'toLaraviltProps')))
                ->map(function ($item) {
                    // Try toInertiaProps first (for Schema objects), then toLaraviltProps (for Field/Entry components)
                    return method_exists($item, 'toInertiaProps') ? $item->toInertiaProps() : $item->toLaraviltProps();
                })
                ->values()
                ->toArray(),
            'content' => $this->getBladeContent(),
            'topHook' => $this->getTopHook(),
            'bottomHook' => $this->getBottomHook(),
            'clusterNavigation' => $clusterNavigation,
            'clusterTitle' => $clusterClass ? $clusterClass::getNavigationLabel() : null,
            'clusterDescription' => null,
        ];

        $inertiaProps = $this->getInertiaProps();

        $props = array_merge($baseProps, $inertiaProps);

        // Debug: Find any Eloquent models in props
        $findModels = function($data, $path = '') use (&$findModels) {
            if (is_object($data) && $data instanceof \Illuminate\Database\Eloquent\Model) {
                \Log::error('[Page] Found Eloquent Model in props!', [
                    'path' => $path,
                    'class' => get_class($data),
                    'id' => method_exists($data, 'getKey') ? $data->getKey() : null,
                ]);
            } elseif (is_array($data)) {
                foreach ($data as $key => $value) {
                    $findModels($value, $path ? "$path.$key" : $key);
                }
            } elseif (is_object($data) && !($data instanceof \Closure)) {
                foreach (get_object_vars($data) as $key => $value) {
                    $findModels($value, $path ? "$path.$key" : $key);
                }
            }
        };
        $findModels($props);

        // Use custom component if set, otherwise use default
        $componentName = $this->component ?? 'laravilt/Page';

        return \Inertia\Inertia::render($componentName, $props);
    }

    /**
     * Check if should use component rendering mode.
     */
    protected function shouldUseComponentMode(): bool
    {
        // Use component mode if page defines a custom view
        return static::$view !== 'laravilt-panel::pages.blank';
    }

    /**
     * Render page using PageLayout component.
     */
    protected function renderAsComponent(): string
    {
        // For auth pages or pages that don't need the panel layout,
        // just render the Blade content directly
        if ($this->isStandalonePage()) {
            return $this->getBladeContent();
        }

        // For regular panel pages, wrap in PageLayout
        $layout = \Laravilt\Panel\Components\PageLayout::make('page-layout')
            ->panel($this->getPanel())
            ->breadcrumbs($this->getBreadcrumbs())
            ->heading($this->getHeading())
            ->subheading($this->getSubheading())
            ->headerActions($this->getHeaderActions())
            ->content($this->getBladeContent());

        // Create a temporary view to wrap the page content in the app layout
        return view()->make('laravilt-panel::pages.wrapper', [
            'pageContent' => $layout->render(),
        ])->render();
    }

    /**
     * Check if this is a standalone page (no panel layout needed).
     */
    protected function isStandalonePage(): bool
    {
        // Auth pages should be standalone
        return str_contains(static::$view, 'auth.');
    }

    /**
     * Get Blade content if using blade view.
     */
    protected function getBladeContent(): ?string
    {
        // If the view is the default blank view, we assume we want to render via Inertia/Vue schema
        // We also check for the 'laravilt::' prefix just in case of legacy/typo issues
        if (in_array(static::$view, ['laravilt-panel::pages.blank', 'laravilt::pages.blank'])) {
            return null;
        }

        // For backward compatibility, allow pages to render blade content
        if (method_exists($this, 'getView') || static::$view !== 'laravilt-panel::pages.blank') {
            $content = view(static::$view, array_merge([
                'page' => $this,
                'heading' => $this->getHeading(),
                'subheading' => $this->getSubheading(),
                'actions' => $this->getActions(),
                'breadcrumbs' => $this->getBreadcrumbs(),
            ], $this->getViewData()))->render();

            return empty(trim($content)) ? null : $content;
        }

        return null;
    }

    /**
     * Convert to HTML.
     */
    public function toHtml()
    {
        return $this->render();
    }

    /**
     * Boot the page.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Mount the page.
     */
    public function mount(): void
    {
        //
    }

    /**
     * Serialize page for Laravilt (Blade + Vue.js).
     */
    public function toLaraviltProps(): array
    {
        return [
            'page' => [
                'heading' => $this->getHeading(),
                'subheading' => $this->getSubheading(),
                'title' => $this->getTitle(),
            ],
            'breadcrumbs' => $this->getBreadcrumbs(),
            'panel' => $this->getPanel() ? [
                'id' => $this->getPanel()->getId(),
                'path' => $this->getPanel()->getPath(),
                'brandName' => $this->getPanel()->getBrandName(),
                'brandLogo' => $this->getPanel()->getBrandLogo(),
            ] : null,
        ];
    }
}
