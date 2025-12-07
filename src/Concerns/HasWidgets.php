<?php

namespace Laravilt\Panel\Concerns;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

trait HasWidgets
{
    protected array $widgets = [];

    protected array $widgetDirectories = [];

    /**
     * Register widgets.
     */
    public function widgets(array $widgets): static
    {
        $this->widgets = array_merge($this->widgets, $widgets);

        return $this;
    }

    /**
     * Get registered widgets.
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    /**
     * Discover widgets in a directory.
     */
    public function discoverWidgets(string $in, string $for): static
    {
        $this->widgetDirectories[] = [
            'directory' => $in,
            'namespace' => $for,
        ];

        return $this;
    }

    /**
     * Boot widgets - discover and register.
     */
    protected function bootWidgets(): void
    {
        foreach ($this->widgetDirectories as $config) {
            if (! is_dir($config['directory'])) {
                continue;
            }

            $widgets = collect(File::allFiles($config['directory']))
                ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'php')
                ->map(function (SplFileInfo $file) use ($config) {
                    $class = $config['namespace'].'\\'.
                        str_replace(
                            ['/', '.php'],
                            ['\\', ''],
                            $file->getRelativePathname()
                        );

                    if (! class_exists($class)) {
                        return;
                    }

                    $reflection = new ReflectionClass($class);

                    if ($reflection->isAbstract() || $reflection->isInterface()) {
                        return;
                    }

                    return $class;
                })
                ->filter()
                ->all();

            $this->widgets(array_merge($this->widgets, $widgets));
        }
    }
}
