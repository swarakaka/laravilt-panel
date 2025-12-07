<?php

namespace Laravilt\Panel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravilt\Panel\PanelRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IdentifyPanel
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $panelId): Response
    {
        $registry = app(PanelRegistry::class);

        if (! $registry->has($panelId)) {
            throw new NotFoundHttpException("Panel '{$panelId}' not found.");
        }

        $panel = $registry->get($panelId);

        // Set the current panel
        $registry->setCurrent($panelId);

        // Store panel in request
        $request->attributes->set('panel', $panel);

        // Set auth guard if specified
        if ($guard = $panel->getAuthGuard()) {
            auth()->shouldUse($guard);
        }

        return $next($request);
    }
}
