<?php

namespace Laravilt\Panel\Concerns;

trait HasPath
{
    protected string $path;

    /**
     * Set the panel path.
     */
    public function path(string $path): static
    {
        $this->path = trim($path, '/');

        return $this;
    }

    /**
     * Get the panel path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the full URL path for this panel.
     */
    public function getUrl(string $path = ''): string
    {
        $url = '/'.$this->path;

        if ($path) {
            $url .= '/'.ltrim($path, '/');
        }

        return $url;
    }
}
