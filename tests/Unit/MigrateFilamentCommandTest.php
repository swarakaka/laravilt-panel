<?php

use Illuminate\Filesystem\Filesystem;
use Laravilt\Panel\Commands\MigrateFilamentCommand;

describe('MigrateFilamentCommand', function () {
    it('has correct command name', function () {
        $command = app(MigrateFilamentCommand::class);

        expect($command->getName())->toBe('laravilt:filament');
    });

    it('has required options', function () {
        $command = app(MigrateFilamentCommand::class);
        $definition = $command->getDefinition();

        expect($definition->hasOption('source'))->toBeTrue()
            ->and($definition->hasOption('target'))->toBeTrue()
            ->and($definition->hasOption('panel'))->toBeTrue()
            ->and($definition->hasOption('dry-run'))->toBeTrue()
            ->and($definition->hasOption('force'))->toBeTrue()
            ->and($definition->hasOption('all'))->toBeTrue();
    });

    it('has correct default option values', function () {
        $command = app(MigrateFilamentCommand::class);
        $definition = $command->getDefinition();

        expect($definition->getOption('source')->getDefault())->toBe('app/Filament')
            ->and($definition->getOption('target')->getDefault())->toBe('app/Laravilt')
            ->and($definition->getOption('panel')->getDefault())->toBe('Admin');
    });

    it('has namespace mappings configured', function () {
        $command = app(MigrateFilamentCommand::class);
        $reflection = new ReflectionClass($command);
        $property = $reflection->getProperty('namespaceMap');
        $property->setAccessible(true);
        $namespaceMap = $property->getValue($command);

        expect($namespaceMap)->toBeArray()
            ->and($namespaceMap)->toHaveKey('Filament\\Resources\\Resource')
            ->and($namespaceMap['Filament\\Resources\\Resource'])->toBe('Laravilt\\Panel\\Resources\\Resource')
            ->and($namespaceMap)->toHaveKey('Filament\\Tables\\Table')
            ->and($namespaceMap['Filament\\Tables\\Table'])->toBe('Laravilt\\Tables\\Table');
    });

    it('has icon mappings configured', function () {
        $command = app(MigrateFilamentCommand::class);
        $reflection = new ReflectionClass($command);
        $property = $reflection->getProperty('iconMap');
        $property->setAccessible(true);
        $iconMap = $property->getValue($command);

        expect($iconMap)->toBeArray()
            ->and($iconMap)->toHaveKey('Heroicon::OutlinedUsers')
            ->and($iconMap['Heroicon::OutlinedUsers'])->toBe('users')
            ->and($iconMap)->toHaveKey('Heroicon::OutlinedHome')
            ->and($iconMap['Heroicon::OutlinedHome'])->toBe('home');
    });

    it('has correct description', function () {
        $command = app(MigrateFilamentCommand::class);

        expect($command->getDescription())->toBe('Migrate Filament PHP v3/v4 resources to Laravilt resources');
    });
});

describe('Filament Migration File Detection', function () {
    beforeEach(function () {
        $this->files = new Filesystem;
        $this->tempDir = sys_get_temp_dir().'/laravilt-migration-test-'.uniqid();
        $this->files->makeDirectory($this->tempDir.'/Resources', 0755, true);
        $this->files->makeDirectory($this->tempDir.'/Pages', 0755, true);
        $this->files->makeDirectory($this->tempDir.'/Widgets', 0755, true);
    });

    afterEach(function () {
        if ($this->files->isDirectory($this->tempDir)) {
            $this->files->deleteDirectory($this->tempDir);
        }
    });

    it('can detect Filament resource files', function () {
        $content = <<<'PHP'
<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

class UserResource extends Resource
{
    protected static ?string $model = \App\Models\User::class;
}
PHP;
        $this->files->put($this->tempDir.'/Resources/UserResource.php', $content);

        $files = $this->files->glob($this->tempDir.'/Resources/*Resource.php');

        expect($files)->toHaveCount(1);
    });

    it('can detect Filament page files', function () {
        $content = <<<'PHP'
<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static string $view = 'filament.pages.dashboard';
}
PHP;
        $this->files->put($this->tempDir.'/Pages/Dashboard.php', $content);

        $files = $this->files->glob($this->tempDir.'/Pages/*.php');

        expect($files)->toHaveCount(1);
    });

    it('can detect Filament widget files', function () {
        $content = <<<'PHP'
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class StatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.stats';
}
PHP;
        $this->files->put($this->tempDir.'/Widgets/StatsWidget.php', $content);

        $files = $this->files->glob($this->tempDir.'/Widgets/*.php');

        expect($files)->toHaveCount(1);
    });
});
