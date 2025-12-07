# Laravilt Panel Package

The Laravilt Panel package provides a powerful, flexible foundation for building admin panels, dashboards, and multi-tenant applications with Laravel and Inertia.js.

## Table of Contents

1. [Installation](#installation)
2. [Core Concepts](#core-concepts)
3. [Creating Panels](#creating-panels)
4. [Pages](#pages)
5. [Navigation](#navigation)
6. [Layouts](#layouts)
7. [Middleware](#middleware)
8. [Multi-Tenancy](#multi-tenancy)
9. [Customization](#customization)
10. [API Reference](#api-reference)

## Installation

```bash
composer require laravilt/panel
```

### Publish Configuration

```bash
php artisan vendor:publish --tag="laravilt-panel-config"
```

### Publish Assets

```bash
php artisan vendor:publish --tag="laravilt-panel-assets"
```

## Core Concepts

### What is a Panel?

A Panel is an isolated section of your application with its own:
- **URL Path**: `/admin`, `/app`, `/tenant/{tenant}`
- **Authentication Guard**: `web`, `admin`, `tenant`
- **Navigation**: Sidebar, topbar, custom menus
- **Pages**: Dashboard, resources, settings
- **Layouts**: Different UI layouts per panel
- **Middleware**: Custom authentication and authorization

### Use Cases

- **Admin Panel**: Backend administration interface
- **User Dashboard**: Customer-facing dashboard
- **Multi-Tenant Apps**: Separate panels per tenant
- **API Dashboard**: API management interface

## Creating Panels

### Basic Panel

Register a panel in your `AppServiceProvider` or dedicated `PanelServiceProvider`:

```php
use Laravilt\Panel\Panel;

public function boot(): void
{
    Panel::make('admin')
        ->path('/admin')
        ->register();
}
```

### Panel with Authentication

```php
Panel::make('admin')
    ->path('/admin')
    ->authGuard('admin')
    ->login()                    // Enable login page
    ->registration()             // Enable registration
    ->emailVerification()        // Require email verification
    ->middleware(['web', 'auth:admin'])
    ->register();
```

### Multi-Panel Application

```php
// Admin Panel
Panel::make('admin')
    ->path('/admin')
    ->authGuard('admin')
    ->login()
    ->register();

// User Dashboard
Panel::make('app')
    ->path('/app')
    ->authGuard('web')
    ->login()
    ->register();

// Tenant Panel
Panel::make('tenant')
    ->path('/tenant/{tenant}')
    ->authGuard('tenant')
    ->login()
    ->register();
```

## Pages

### Creating a Page

Create a page class:

```php
namespace App\Pages;

use Laravilt\Panel\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $slug = 'dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function getHeading(): string
    {
        return 'Welcome to Dashboard';
    }

    public function getSubheading(): ?string
    {
        return 'Overview of your application';
    }

    protected function getInertiaProps(): array
    {
        return [
            'stats' => $this->getStats(),
            'recentActivity' => $this->getRecentActivity(),
        ];
    }

    protected function getStats(): array
    {
        return [
            'users' => \App\Models\User::count(),
            'revenue' => '$12,345',
            'orders' => 156,
        ];
    }
}
```

### Registering Pages

```php
Panel::make('admin')
    ->pages([
        Dashboard::class,
        UsersPage::class,
        SettingsPage::class,
    ])
    ->register();
```

### Page Layouts

Available layouts:

```php
use Laravilt\Panel\Enums\PageLayout;

class MyPage extends Page
{
    public function getLayout(): string
    {
        return PageLayout::Default->value;    // Full width layout
        // or
        return PageLayout::Centered->value;   // Centered content
        // or
        return PageLayout::Card->value;       // Card-based layout
        // or
        return PageLayout::Settings->value;   // Settings sidebar layout
    }
}
```

### Page with Actions

```php
use Laravilt\Actions\Action;
use Laravilt\Forms\Components\TextInput;

class EditProfile extends Page
{
    protected function getSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required(),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required(),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->action(function (array $data) {
                    auth()->user()->update($data);

                    session()->flash('notifications', [[
                        'type' => 'success',
                        'message' => 'Profile updated successfully!',
                    ]]);
                })
                ->requiresConfirmation()
                ->modalHeading('Save Changes')
                ->modalDescription('Are you sure you want to save these changes?'),
        ];
    }
}
```

### Page Clusters

Group related pages together:

```php
namespace App\Clusters;

use Laravilt\Panel\Clusters\Cluster;

class Settings extends Cluster
{
    protected static ?string $title = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static ?int $navigationSort = 100;
}
```

Register pages in cluster:

```php
namespace App\Pages\Settings;

use App\Clusters\Settings;
use Laravilt\Panel\Pages\Page;

class GeneralSettings extends Page
{
    protected static ?string $cluster = Settings::class;

    protected static ?string $title = 'General';
}
```

## Navigation

### Navigation Configuration

```php
Panel::make('admin')
    ->navigation([
        'dashboard' => [
            'label' => 'Dashboard',
            'icon' => 'heroicon-o-home',
            'url' => '/admin/dashboard',
            'sort' => 1,
        ],
        'users' => [
            'label' => 'Users',
            'icon' => 'heroicon-o-users',
            'url' => '/admin/users',
            'sort' => 2,
            'badge' => fn () => \App\Models\User::count(),
        ],
    ])
    ->register();
```

### Navigation Groups

```php
protected static ?string $navigationGroup = 'Management';

protected static ?int $navigationSort = 10;
```

### Custom Navigation

```php
Panel::make('admin')
    ->navigationBuilder(function (NavigationBuilder $builder) {
        return $builder
            ->items([
                NavigationItem::make('Dashboard')
                    ->icon('heroicon-o-home')
                    ->url('/admin'),

                NavigationItem::make('Users')
                    ->icon('heroicon-o-users')
                    ->url('/admin/users')
                    ->badge(fn () => User::count()),

                NavigationGroup::make('Settings')
                    ->items([
                        NavigationItem::make('General')
                            ->url('/admin/settings/general'),
                        NavigationItem::make('Security')
                            ->url('/admin/settings/security'),
                    ]),
            ]);
    })
    ->register();
```

## Layouts

### Default Layout

The default layout includes:
- Sidebar navigation
- Top bar with user menu
- Main content area
- Notifications

### Custom Layout Components

```php
Panel::make('admin')
    ->sidebarComponent('CustomSidebar')
    ->topbarComponent('CustomTopbar')
    ->register();
```

### Layout Props

Pass custom props to layout:

```php
protected function getLayoutProps(): array
{
    return [
        'showSidebar' => true,
        'sidebarCollapsible' => true,
        'theme' => 'dark',
    ];
}
```

## Middleware

### Global Middleware

```php
Panel::make('admin')
    ->middleware(['web', 'auth:admin', 'verified'])
    ->register();
```

### Page-Specific Middleware

```php
class SecurePage extends Page
{
    protected static array $middleware = ['two-factor'];
}
```

### Custom Panel Middleware

```php
namespace App\Http\Middleware;

class CheckPanelAccess
{
    public function handle($request, Closure $next, $panel)
    {
        $panelInstance = Panel::get($panel);

        if (!$panelInstance->canAccess(auth()->user())) {
            abort(403);
        }

        return $next($request);
    }
}
```

## Multi-Tenancy

### Tenant-Scoped Panels

```php
Panel::make('tenant')
    ->path('/tenant/{tenant}')
    ->middleware(['web', 'auth', 'tenant'])
    ->tenantMiddleware(function ($request, $next, $tenantId) {
        $tenant = Tenant::findOrFail($tenantId);

        // Set current tenant
        app()->instance('current_tenant', $tenant);

        // Scope queries
        Tenant::setCurrent($tenant);

        return $next($request);
    })
    ->register();
```

### Tenant-Aware Pages

```php
class TenantDashboard extends Page
{
    protected function getInertiaProps(): array
    {
        $tenant = app('current_tenant');

        return [
            'tenant' => $tenant,
            'users' => $tenant->users()->count(),
            'subscription' => $tenant->subscription,
        ];
    }
}
```

## Customization

### Custom Page Override

Replace any built-in page:

```php
Panel::make('admin')
    ->login(page: CustomLoginPage::class)
    ->register(page: CustomRegisterPage::class)
    ->register();
```

Custom page class:

```php
namespace App\Pages;

use Laravilt\Auth\Pages\Login as BaseLogin;

class CustomLoginPage extends BaseLogin
{
    protected ?string $component = 'CustomLoginPage';

    public function getHeading(): string
    {
        return 'Welcome Back!';
    }

    protected function getInertiaProps(): array
    {
        return array_merge(parent::getInertiaProps(), [
            'companyLogo' => asset('images/logo.png'),
            'features' => [
                'Secure login',
                'Two-factor authentication',
                'Password reset',
            ],
        ]);
    }
}
```

### Custom Theme

```php
Panel::make('admin')
    ->theme([
        'primary' => '#3b82f6',
        'secondary' => '#10b981',
        'danger' => '#ef4444',
    ])
    ->register();
```

### Custom Branding

```php
Panel::make('admin')
    ->brandName('My Admin Panel')
    ->brandLogo(asset('images/admin-logo.png'))
    ->brandUrl('/admin')
    ->favicon(asset('images/favicon.ico'))
    ->register();
```

## API Reference

### Panel Methods

```php
// Basic Configuration
Panel::make(string $id)                  // Create panel with ID
    ->path(string $path)                  // Set URL path
    ->authGuard(string $guard)            // Set auth guard
    ->middleware(array $middleware)       // Add middleware
    ->domain(string $domain)              // Set domain

// Authentication Features
    ->login(bool|string $page = true)     // Enable login
    ->registration(bool|string $page)     // Enable registration
    ->emailVerification()                 // Require email verification
    ->otp()                               // Enable OTP
    ->twoFactor(array $config)            // Enable 2FA
    ->passwordReset()                     // Enable password reset
    ->socialLogin(array $providers)       // Enable social auth
    ->passkeys()                          // Enable passkeys
    ->magicLinks()                        // Enable magic links

// Pages & Navigation
    ->pages(array $pages)                 // Register pages
    ->navigation(array $items)            // Set navigation
    ->navigationGroups(array $groups)     // Set navigation groups

// Branding
    ->brandName(string $name)             // Set brand name
    ->brandLogo(string $url)              // Set logo URL
    ->brandUrl(string $url)               // Set brand link
    ->favicon(string $url)                // Set favicon

// Advanced
    ->tenantMiddleware(Closure $callback) // Add tenant middleware
    ->beforeRegister(Closure $callback)   // Run before registration
    ->afterRegister(Closure $callback)    // Run after registration

// Registration
    ->register()                          // Register the panel
```

### Panel Facade

```php
use Laravilt\Panel\Facades\Panel;

// Get panel instance
$panel = Panel::get('admin');

// Get current panel
$current = Panel::getCurrent();

// Check if panel exists
Panel::has('admin'); // returns boolean

// Get all panels
$panels = Panel::all();

// Panel properties
$panel->getId();                    // Get panel ID
$panel->getPath();                  // Get URL path
$panel->getAuthGuard();             // Get auth guard
$panel->url($path = '');            // Generate panel URL
$panel->canAccess($user);           // Check if user can access
$panel->hasLogin();                 // Check if login enabled
$panel->hasRegistration();          // Check if registration enabled
```

### Page Methods

```php
// Override in your page class
protected static ?string $title;              // Page title
protected static ?string $slug;               // URL slug
protected static ?string $navigationIcon;     // Navigation icon
protected static ?string $navigationGroup;    // Navigation group
protected static ?int $navigationSort;        // Navigation sort order
protected static bool $shouldRegisterNavigation = true;

// Methods to override
public function getHeading(): string;         // Page heading
public function getSubheading(): ?string;     // Page subheading
public function getLayout(): string;          // Page layout
protected function getSchema(): array;        // Form schema
protected function getActions(): array;       // Page actions
protected function getInertiaProps(): array;  // Props for frontend
```

## Best Practices

### 1. Panel Organization

```php
// Group related panels in a PanelServiceProvider
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravilt\Panel\Panel;

class PanelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerAdminPanel();
        $this->registerUserPanel();
    }

    protected function registerAdminPanel(): void
    {
        Panel::make('admin')
            ->path('/admin')
            ->authGuard('admin')
            ->login()
            ->pages([...])
            ->register();
    }

    protected function registerUserPanel(): void
    {
        Panel::make('app')
            ->path('/app')
            ->authGuard('web')
            ->login()
            ->pages([...])
            ->register();
    }
}
```

### 2. Page Organization

```
app/
  Pages/
    Admin/
      Dashboard.php
      Users/
        ListUsers.php
        EditUser.php
      Settings/
        GeneralSettings.php
    App/
      Dashboard.php
      Profile.php
```

### 3. Custom Pages

Always extend base pages when customizing:

```php
use Laravilt\Auth\Pages\Login as BaseLogin;

class CustomLogin extends BaseLogin
{
    // Only override what you need
}
```

### 4. Event Listeners

Listen to auth events for custom behavior:

```php
Event::listen(LoginSuccessful::class, function ($event) {
    // Track login
    // Send notification
    // Update last login
});
```

## Troubleshooting

### Panel Not Found

Ensure panel is registered in a service provider that's loaded:

```php
// config/app.php
'providers' => [
    App\Providers\PanelServiceProvider::class,
],
```

### Routes Not Working

Clear route cache:

```bash
php artisan route:clear
php artisan route:cache
```

### Middleware Issues

Check middleware priority and order:

```php
Panel::make('admin')
    ->middleware(['web', 'auth:admin']) // 'web' must come first
    ->register();
```

## Support

For issues, questions, or contributions:
- GitHub: https://github.com/laravilt/panel
- Documentation: https://laravilt.com/docs/panel
- Discord: https://discord.gg/laravilt
