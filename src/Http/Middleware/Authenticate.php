<?php

namespace Laravilt\Panel\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            // Get the current panel from the request path
            $panel = \Laravilt\Panel\Facades\Panel::getCurrent();

            if ($panel && $panel->hasLogin()) {
                return $panel->loginUrl();
            }

            // Fallback to default login route
            return route('login');
        }

        return null;
    }
}
