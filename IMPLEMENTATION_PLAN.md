# Laravilt Panel - Implementation Plan

This document outlines the comprehensive implementation plan for packaging the panel package and creating necessary commands.

## 🎯 Objectives

1. Package panel for NPM distribution
2. Move all Laravel starter kit customizations to stubs
3. Create artisan commands for panel/page/resource generation
4. Make sidebar dynamic with plugin support
5. Ensure plugin system integration matches Filament PHP functionality

---

## 📦 Phase 1: NPM Package Setup

### Files to Create

1. **package.json** - NPM package configuration
   - Name: `@laravilt/panel`
   - Version: 1.0.0
   - Exports: ES/UMD bundles, TypeScript definitions
   - Dependencies: Vue 3, Inertia, etc.

2. **vite.config.ts** - Build configuration
   - Entry: `resources/js/app.ts`
   - Formats: ES and UMD
   - External dependencies

3. **tsconfig.json** - TypeScript configuration

4. **.npmignore** - Exclude PHP files from NPM package

5. **resources/js/app.ts** - Main entry point
   - Export all panel components
   - Export types
   - Export composables

---

## 📝 Phase 2: Stub Creation

### Middleware Stubs

**`stubs/Middleware/HandleInertiaRequests.stub`**
```php
<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'notifications' => session()->get('notifications', []),
            'actionUpdatedData' => session()->get('action_updated_data'),
        ];
    }
}
```

**`stubs/Middleware/HandleAppearance.stub`**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    public function handle(Request $request, Closure $next): Response
    {
        View::share('appearance', $request->cookie('appearance') ?? 'system');

        return $next($request);
    }
}
```

### Layout Stubs

Copy all layouts from `/resources/js/layouts/` to `stubs/layouts/`:
- `AppLayout.vue.stub`
- `AuthLayout.vue.stub`
- `app/AppSidebarLayout.vue.stub`
- `app/AppHeaderLayout.vue.stub`
- `auth/AuthSimpleLayout.vue.stub`
- `auth/AuthSplitLayout.vue.stub`
- `auth/AuthCardLayout.vue.stub`
- `settings/Layout.vue.stub`

### Bootstrap Stubs

**`stubs/bootstrap/app.stub`**
- Copy from `/bootstrap/app.php`
- Include middleware registration

**`stubs/bootstrap/providers.stub`**
- Copy from `/bootstrap/providers.php`

### Route Stubs

**`stubs/routes/web.stub`**
- Basic web routes setup

**`stubs/routes/settings.stub`**
- Settings routes (if exists)

---

## 🔨 Phase 3: Artisan Commands

### 1. Panel Generation Command

**File**: `src/Commands/MakePanelCommand.php`

```php
<?php

namespace Laravilt\Panel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakePanelCommand extends Command
{
    protected $signature = 'make:panel {id} {--path=}';

    protected $description = 'Create a new panel';

    public function handle()
    {
        $id = $this->argument('id');
        $path = $this->option('path') ?? $id;
        $studlyId = Str::studly($id);

        // Create panel provider
        $this->createPanelProvider($id, $path, $studlyId);

        // Create directory structure
        $this->createDirectoryStructure($studlyId);

        // Create dashboard page
        $this->createDashboardPage($studlyId);

        // Register provider
        $this->registerProvider($studlyId);

        $this->info("Panel [{$id}] created successfully!");
        $this->info("Run: php artisan panel:install to set up your application.");
    }

    protected function createPanelProvider($id, $path, $studlyId)
    {
        $stub = file_get_contents(__DIR__.'/../../stubs/panel-provider.stub');

        $stub = str_replace(
            ['{{ id }}', '{{ path }}', '{{ studlyId }}'],
            [$id, $path, $studlyId],
            $stub
        );

        $file = app_path("Providers/{$studlyId}PanelProvider.php");
        file_put_contents($file, $stub);
    }

    protected function createDirectoryStructure($studlyId)
    {
        $base = app_path("Laravilt/{$studlyId}");

        mkdir("{$base}/Pages", 0755, true);
        mkdir("{$base}/Widgets", 0755, true);
        mkdir("{$base}/Resources", 0755, true);
    }

    protected function createDashboardPage($studlyId)
    {
        $stub = file_get_contents(__DIR__.'/../../stubs/dashboard.stub');
        $stub = str_replace('{{ studlyId }}', $studlyId, $stub);

        $file = app_path("Laravilt/{$studlyId}/Pages/Dashboard.php");
        file_put_contents($file, $stub);
    }

    protected function registerProvider($studlyId)
    {
        $provider = "App\\Providers\\{$studlyId}PanelProvider::class";
        $providersFile = base_path('bootstrap/providers.php');

        $content = file_get_contents($providersFile);

        if (!str_contains($content, $provider)) {
            $content = str_replace(
                'return [',
                "return [\n    {$provider},",
                $content
            );

            file_put_contents($providersFile, $content);
        }
    }
}
```

### 2. Page Generation Command

**File**: `src/Commands/MakePageCommand.php`

```php
<?php

namespace Laravilt\Panel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakePageCommand extends Command
{
    protected $signature = 'make:panel-page {panel} {name}';

    protected $description = 'Create a new panel page';

    public function handle()
    {
        $panel = Str::studly($this->argument('panel'));
        $name = Str::studly($this->argument('name'));

        $stub = file_get_contents(__DIR__.'/../../stubs/page.stub');

        $stub = str_replace(
            ['{{ panel }}', '{{ name }}'],
            [$panel, $name],
            $stub
        );

        $file = app_path("Laravilt/{$panel}/Pages/{$name}.php");
        file_put_contents($file, $stub);

        // Create Vue page
        $this->createVuePage($panel, $name);

        $this->info("Page [{$name}] created for panel [{$panel}]!");
    }

    protected function createVuePage($panel, $name)
    {
        $stub = file_get_contents(__DIR__.'/../../stubs/page-view.stub');
        $stub = str_replace('{{ name }}', $name, $stub);

        $dir = resource_path("js/pages/{$panel}");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = "{$dir}/{$name}.vue";
        file_put_contents($file, $stub);
    }
}
```

### 3. Resource Generation Command

**File**: `src/Commands/MakeResourceCommand.php`

```php
<?php

namespace Laravilt\Panel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeResourceCommand extends Command
{
    protected $signature = 'make:panel-resource {panel} {name} {--model=}';

    protected $description = 'Create a new panel resource with CRUD pages';

    public function handle()
    {
        $panel = Str::studly($this->argument('panel'));
        $name = Str::studly($this->argument('name'));
        $model = $this->option('model') ?? $name;

        // Create resource directory
        $resourceDir = app_path("Laravilt/{$panel}/Resources/{$name}");
        mkdir($resourceDir, 0755, true);

        // Create pages
        $this->createResourcePages($panel, $name, $model, $resourceDir);

        $this->info("Resource [{$name}] created for panel [{$panel}]!");
    }

    protected function createResourcePages($panel, $name, $model, $dir)
    {
        $pages = ['List', 'Create', 'Edit', 'View'];

        foreach ($pages as $page) {
            $stub = file_get_contents(__DIR__."/../../stubs/resource-{$page}.stub");

            $stub = str_replace(
                ['{{ panel }}', '{{ name }}', '{{ model }}'],
                [$panel, $name, $model],
                $stub
            );

            file_put_contents("{$dir}/{$page}.php", $stub);
        }
    }
}
```

### 4. Install Command

**File**: `src/Commands/InstallCommand.php`

```php
<?php

namespace Laravilt\Panel\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'panel:install';

    protected $description = 'Install Laravilt Panel stubs and setup application';

    public function handle()
    {
        $this->info('Installing Laravilt Panel...');

        // Publish middleware
        $this->publishMiddleware();

        // Publish layouts
        $this->publishLayouts();

        // Publish bootstrap files
        $this->publishBootstrap();

        // Register middleware in bootstrap/app.php
        $this->registerMiddleware();

        $this->info('Laravilt Panel installed successfully!');
        $this->info('Run: php artisan make:panel admin to create your first panel.');
    }

    protected function publishMiddleware()
    {
        $this->copyStub(
            __DIR__.'/../../stubs/Middleware/HandleInertiaRequests.stub',
            app_path('Http/Middleware/HandleInertiaRequests.php')
        );

        $this->copyStub(
            __DIR__.'/../../stubs/Middleware/HandleAppearance.stub',
            app_path('Http/Middleware/HandleAppearance.php')
        );

        $this->info('✓ Middleware published');
    }

    protected function publishLayouts()
    {
        $layouts = [
            'AppLayout.vue',
            'AuthLayout.vue',
            'app/AppSidebarLayout.vue',
            'app/AppHeaderLayout.vue',
            'auth/AuthSimpleLayout.vue',
            'auth/AuthSplitLayout.vue',
            'auth/AuthCardLayout.vue',
            'settings/Layout.vue',
        ];

        foreach ($layouts as $layout) {
            $this->copyStub(
                __DIR__."/../../stubs/layouts/{$layout}.stub",
                resource_path("js/layouts/{$layout}")
            );
        }

        $this->info('✓ Layouts published');
    }

    protected function publishBootstrap()
    {
        // Only overwrite if user confirms
        if ($this->confirm('Overwrite bootstrap/app.php?', false)) {
            $this->copyStub(
                __DIR__.'/../../stubs/bootstrap/app.stub',
                base_path('bootstrap/app.php')
            );
        }

        $this->info('✓ Bootstrap files published');
    }

    protected function registerMiddleware()
    {
        $appFile = base_path('bootstrap/app.php');
        $content = file_get_contents($appFile);

        // Register HandleAppearance middleware
        if (!str_contains($content, 'HandleAppearance')) {
            $this->info('Please manually register HandleAppearance middleware in bootstrap/app.php');
        }

        // Register HandleInertiaRequests middleware
        if (!str_contains($content, 'HandleInertiaRequests')) {
            $this->info('Please manually register HandleInertiaRequests middleware in bootstrap/app.php');
        }
    }

    protected function copyStub($from, $to)
    {
        $dir = dirname($to);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = file_get_contents($from);
        file_put_contents($to, $content);
    }
}
```

---

## 🎨 Phase 4: Dynamic Sidebar

### Sidebar Item Registration

**File**: `src/Navigation/NavigationItem.php`

```php
<?php

namespace Laravilt\Panel\Navigation;

class NavigationItem
{
    protected string $label;
    protected string $url;
    protected ?string $icon = null;
    protected ?string $badge = null;
    protected array $children = [];
    protected bool $active = false;

    public static function make(string $label): static
    {
        return new static($label);
    }

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    public function url(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function badge(string $badge): static
    {
        $this->badge = $badge;
        return $this;
    }

    public function children(array $children): static
    {
        $this->children = $children;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'url' => $this->url,
            'icon' => $this->icon,
            'badge' => $this->badge,
            'children' => array_map(fn($child) => $child->toArray(), $this->children),
            'active' => $this->active,
        ];
    }
}
```

### Panel Navigation Method

**File**: `src/Panel.php` (add method)

```php
protected array $navigationItems = [];

public function navigation(callable $callback): static
{
    $items = $callback();

    if (!is_array($items)) {
        $items = [$items];
    }

    $this->navigationItems = array_merge($this->navigationItems, $items);

    return $this;
}

public function getNavigation(): array
{
    return array_map(fn($item) => $item->toArray(), $this->navigationItems);
}
```

### Plugin Navigation Hook

**File**: `src/Plugin.php` (add method)

```php
public function navigation(): array
{
    return [];
}
```

---

## 🔌 Phase 5: Plugin System Integration

### Plugin Registration

Plugins can register navigation items:

```php
// In plugin's boot method
public function boot()
{
    $this->panel->navigation(function() {
        return [
            NavigationItem::make('My Plugin')
                ->url('/admin/my-plugin')
                ->icon('puzzle')
                ->badge('New'),
        ];
    });
}
```

### Sidebar Component Update

Update `AppSidebarLayout.vue` to fetch navigation from panel:

```vue
<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const navigation = computed(() => page.props.navigation || [])
</script>
```

---

## 📋 Implementation Checklist

- [ ] Create NPM package configuration files
- [ ] Create all middleware stubs
- [ ] Create all layout stubs
- [ ] Create all bootstrap stubs
- [ ] Create MakePanelCommand
- [ ] Create MakePageCommand
- [ ] Create MakeResourceCommand
- [ ] Create InstallCommand
- [ ] Implement NavigationItem class
- [ ] Update Panel class with navigation support
- [ ] Update Plugin class with navigation hook
- [ ] Update sidebar component for dynamic navigation
- [ ] Test complete workflow
- [ ] Update documentation

---

## 🚀 Next Steps

1. Implement all stubs
2. Implement all commands
3. Test panel installation
4. Test page generation
5. Test resource generation
6. Verify plugin navigation integration
7. Package for NPM
8. Update documentation

---

**This is a comprehensive plan that matches Filament PHP's functionality for panel management, page generation, and plugin system integration.**
