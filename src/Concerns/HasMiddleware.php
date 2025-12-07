<?php

namespace Laravilt\Panel\Concerns;

use Closure;

trait HasMiddleware
{
    protected array|Closure $middleware = [];

    protected array|Closure $authMiddleware = [];

    protected string|Closure|null $authGuard = null;

    /**
     * Set the panel middleware.
     */
    public function middleware(array|Closure $middleware): static
    {
        $this->middleware = $middleware;

        return $this;
    }

    /**
     * Get the panel middleware.
     */
    public function getMiddleware(): array
    {
        return $this->evaluate($this->middleware);
    }

    /**
     * Set the auth middleware.
     */
    public function authMiddleware(array|Closure $middleware): static
    {
        $this->authMiddleware = $middleware;

        return $this;
    }

    /**
     * Get the auth middleware.
     */
    public function getAuthMiddleware(): array
    {
        return $this->evaluate($this->authMiddleware);
    }

    /**
     * Set the auth guard.
     */
    public function authGuard(string|Closure|null $guard): static
    {
        $this->authGuard = $guard;

        return $this;
    }

    /**
     * Get the auth guard.
     */
    public function getAuthGuard(): ?string
    {
        return $this->evaluate($this->authGuard);
    }
}
