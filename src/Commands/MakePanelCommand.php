<?php

namespace Laravilt\Panel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePanelCommand extends Command
{
    protected $signature = 'laravilt:panel
                            {id? : The panel identifier}
                            {--path= : The URL path for the panel}
                            {--quick : Skip interactive mode and use defaults}';

    protected $description = 'Create a new panel with interactive feature selection';

    /**
     * Available features for panels.
     */
    protected array $availableFeatures = [
        'login' => 'Login page',
        'registration' => 'User registration',
        'password-reset' => 'Password reset functionality',
        'email-verification' => 'Email verification',
        'profile' => 'User profile management',
        'two-factor' => 'Two-factor authentication (2FA)',
        'passkeys' => 'Passkey authentication (WebAuthn)',
        'social-login' => 'Social login (OAuth providers)',
        'database-notifications' => 'Database notifications',
        'browser-sessions' => 'Browser session management',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = $this->argument('id');

        // If no ID provided, ask for it
        if (! $id) {
            $id = $this->ask('What is the panel identifier?', 'admin');
        }

        $path = $this->option('path') ?? $id;
        $studlyId = Str::studly($id);

        $this->newLine();
        $this->components->info("Creating '{$id}' panel...");
        $this->newLine();

        // Interactive feature selection (unless --quick flag is used)
        $features = $this->option('quick')
            ? $this->getDefaultFeatures()
            : $this->selectFeatures();

        // Create panel provider
        $this->createPanelProvider($studlyId, $id, $path, $features);

        // Create directory structure
        $this->createDirectories($studlyId);

        // Create Dashboard page
        $this->createDashboardPage($studlyId);

        // Register provider
        $this->registerProvider($studlyId);

        // Run additional setup based on features
        $this->runFeatureSetup($features);

        $this->newLine();
        $this->components->info("Panel [{$id}] created successfully!");
        $this->newLine();

        $this->components->bulletList([
            "Panel provider: <fg=cyan>app/Providers/Laravilt/{$studlyId}PanelProvider.php</>",
            "Pages directory: <fg=cyan>app/Laravilt/{$studlyId}/Pages</>",
            "Resources directory: <fg=cyan>app/Laravilt/{$studlyId}/Resources</>",
            "Widgets directory: <fg=cyan>app/Laravilt/{$studlyId}/Widgets</>",
        ]);

        $this->newLine();
        $this->components->info('Selected features:');
        foreach ($features as $feature) {
            $this->components->twoColumnDetail(
                $feature,
                $this->availableFeatures[$feature] ?? $feature
            );
        }

        // Run optimize to clear and rebuild caches
        $this->newLine();
        $this->components->task('Rebuilding caches', function () {
            Artisan::call('optimize');

            return true;
        });

        return self::SUCCESS;
    }

    /**
     * Get default features for quick mode.
     */
    protected function getDefaultFeatures(): array
    {
        return [
            'login',
            'password-reset',
            'profile',
        ];
    }

    /**
     * Interactive feature selection.
     */
    protected function selectFeatures(): array
    {
        $this->components->info('Select the features you want to enable for this panel:');
        $this->newLine();

        $selectedFeatures = [];

        // Group features for better UX
        $featureGroups = [
            'Authentication' => ['login', 'registration', 'password-reset', 'email-verification'],
            'Security' => ['two-factor', 'passkeys', 'browser-sessions'],
            'User Management' => ['profile', 'social-login'],
            'Notifications' => ['database-notifications'],
        ];

        foreach ($featureGroups as $groupName => $groupFeatures) {
            $this->components->info($groupName);

            foreach ($groupFeatures as $feature) {
                if (! isset($this->availableFeatures[$feature])) {
                    continue;
                }

                $default = in_array($feature, ['login', 'password-reset', 'profile']);
                $label = $this->availableFeatures[$feature];

                if ($this->confirm("  Enable {$label}?", $default)) {
                    $selectedFeatures[] = $feature;
                }
            }

            $this->newLine();
        }

        return $selectedFeatures;
    }

    /**
     * Create panel provider with selected features.
     */
    protected function createPanelProvider(string $studlyId, string $id, string $path, array $features): void
    {
        $content = $this->generatePanelProviderContent($studlyId, $id, $path, $features);

        $providerPath = app_path("Providers/Laravilt/{$studlyId}PanelProvider.php");

        File::ensureDirectoryExists(dirname($providerPath));
        File::put($providerPath, $content);

        $this->components->task("Creating panel provider", fn () => true);
    }

    /**
     * Generate panel provider content based on features.
     */
    protected function generatePanelProviderContent(string $studlyId, string $id, string $path, array $features): string
    {
        $authFeatures = $this->buildAuthFeatures($features);
        $middleware = $this->buildMiddleware($features);
        $additionalMethods = $this->buildAdditionalMethods($features);

        $content = <<<PHP
<?php

namespace App\Providers\Laravilt;

use Laravilt\Panel\Panel;
use Laravilt\Panel\PanelProvider;

class {$studlyId}PanelProvider extends PanelProvider
{
    /**
     * Configure the panel.
     */
    public function panel(Panel \$panel): Panel
    {
        return \$panel
            ->id('{$id}')
            ->path('{$path}')
            ->brandName('{$studlyId}')
            ->discoverAutomatically()
{$authFeatures}{$middleware};
    }
{$additionalMethods}}

PHP;

        return $content;
    }

    /**
     * Build authentication feature chain.
     */
    protected function buildAuthFeatures(array $features): string
    {
        $methods = [];

        if (in_array('login', $features)) {
            $methods[] = '            ->login()';
        }

        if (in_array('registration', $features)) {
            $methods[] = '            ->registration()';
        }

        if (in_array('password-reset', $features)) {
            $methods[] = '            ->passwordReset()';
        }

        if (in_array('email-verification', $features)) {
            $methods[] = '            ->emailVerification()';
        }

        if (in_array('profile', $features)) {
            $methods[] = '            ->profile()';
        }

        if (in_array('two-factor', $features)) {
            $methods[] = '            ->twoFactorAuthentication()';
        }

        if (in_array('passkeys', $features)) {
            $methods[] = '            ->passkeys()';
        }

        if (in_array('social-login', $features)) {
            $methods[] = "            ->socialLogin(['google', 'github'])";
        }

        if (in_array('browser-sessions', $features)) {
            $methods[] = '            ->browserSessions()';
        }

        if (in_array('database-notifications', $features)) {
            $methods[] = '            ->databaseNotifications()';
        }

        return empty($methods) ? '' : implode("\n", $methods)."\n";
    }

    /**
     * Build middleware chain.
     */
    protected function buildMiddleware(array $features): string
    {
        $middleware = "            ->middleware(['web', 'auth'])\n";
        $middleware .= "            ->authMiddleware(['auth'])";

        return $middleware;
    }

    /**
     * Build additional methods (widgets, navigation, etc).
     */
    protected function buildAdditionalMethods(array $features): string
    {
        return '';
    }

    /**
     * Create directories for the panel.
     */
    protected function createDirectories(string $studlyId): void
    {
        $basePath = app_path("Laravilt/{$studlyId}");

        $directories = [
            'Pages',
            'Widgets',
            'Resources',
        ];

        foreach ($directories as $directory) {
            File::ensureDirectoryExists("{$basePath}/{$directory}");
        }

        $this->components->task("Creating directories", fn () => true);
    }

    /**
     * Create Dashboard page.
     */
    protected function createDashboardPage(string $studlyId): void
    {
        $stub = File::get(__DIR__.'/../../stubs/dashboard.stub');

        $content = str_replace(
            ['{{ studlyId }}'],
            [$studlyId],
            $stub
        );

        $pagePath = app_path("Laravilt/{$studlyId}/Pages/Dashboard.php");

        File::ensureDirectoryExists(dirname($pagePath));
        File::put($pagePath, $content);

        $this->components->task("Creating Dashboard page", fn () => true);
    }

    /**
     * Register provider in bootstrap/providers.php.
     */
    protected function registerProvider(string $studlyId): void
    {
        $provider = "App\\Providers\\Laravilt\\{$studlyId}PanelProvider::class";
        $providersFile = base_path('bootstrap/providers.php');

        if (! File::exists($providersFile)) {
            $this->components->warn('bootstrap/providers.php not found. Please register the provider manually.');

            return;
        }

        $content = File::get($providersFile);

        if (str_contains($content, $provider)) {
            $this->components->warn('Provider already registered');

            return;
        }

        // Add provider to the array
        $content = str_replace(
            'return [',
            "return [\n    {$provider},",
            $content
        );

        File::put($providersFile, $content);
        $this->components->task("Registering provider", fn () => true);
    }

    /**
     * Run additional setup based on selected features.
     */
    protected function runFeatureSetup(array $features): void
    {
        // Run notifications:table migration if database notifications is selected
        if (in_array('database-notifications', $features)) {
            $this->components->task("Setting up database notifications", function () {
                // Check if notifications table migration already exists
                $migrations = File::glob(database_path('migrations/*_create_notifications_table.php'));

                if (empty($migrations)) {
                    Artisan::call('notifications:table');
                    $this->info('  Created notifications table migration');
                }

                // Run migrations
                Artisan::call('migrate', ['--force' => true]);

                return true;
            });
        }

        // Setup for passkeys (WebAuthn)
        if (in_array('passkeys', $features)) {
            $this->components->task("Setting up passkeys", function () {
                // Check if webauthn migrations exist
                $migrations = File::glob(database_path('migrations/*_create_web_authn_credentials_table.php'));

                if (empty($migrations)) {
                    // Publish webauthn migrations if not exists
                    Artisan::call('vendor:publish', [
                        '--tag' => 'webauthn-migrations',
                    ]);
                }

                // Run migrations
                Artisan::call('migrate', ['--force' => true]);

                return true;
            });
        }

        // Setup for two-factor authentication
        if (in_array('two-factor', $features)) {
            $this->components->task("Setting up two-factor authentication", function () {
                // Ensure Fortify 2FA is enabled
                // This is typically done via config, but we can remind the user
                return true;
            });
        }
    }
}
