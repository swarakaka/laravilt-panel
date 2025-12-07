<?php

namespace Laravilt\Panel\Concerns;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

trait HasPages
{
    protected array $pages = [];

    protected array $pageDirectories = [];

    /**
     * Register a page.
     */
    public function pages(array $pages): static
    {
        $this->pages = array_merge($this->pages, $pages);

        return $this;
    }

    /**
     * Get registered pages.
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * Discover pages in a directory.
     */
    public function discoverPages(string $in, string $for): static
    {
        $this->pageDirectories[] = [
            'directory' => $in,
            'namespace' => $for,
        ];

        return $this;
    }

    /**
     * Boot pages - discover and register.
     */
    protected function bootPages(): void
    {
        foreach ($this->pageDirectories as $config) {
            if (! is_dir($config['directory'])) {
                continue;
            }

            $pages = collect(File::allFiles($config['directory']))
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

            $this->pages = array_unique(array_merge($this->pages, $pages));
        }
    }
}
