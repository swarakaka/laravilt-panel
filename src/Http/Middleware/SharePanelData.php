<?php

namespace Laravilt\Panel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravilt\Panel\PanelRegistry;
use Symfony\Component\HttpFoundation\Response;

class SharePanelData
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $registry = app(PanelRegistry::class);
        $panel = $registry->getCurrent();

        if ($panel) {
            Inertia::share([
                'panel' => [
                    'id' => $panel->getId(),
                    'path' => $panel->getPath(),
                    'brandName' => $panel->getBrandName(),
                    'brandLogo' => $panel->getBrandLogo(),
                    'navigation' => $panel->getNavigation(),
                    'userMenu' => $panel->getUserMenu(),
                    'user' => $request->user() ? [
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                    ] : null,
                    'auth' => [
                        'hasProfile' => $panel->hasProfile(),
                        'hasLogin' => $panel->hasLogin(),
                        'hasRegistration' => $panel->hasRegistration(),
                        'hasPasswordReset' => $panel->hasPasswordReset(),
                        'hasEmailVerification' => $panel->hasEmailVerification(),
                        'hasOtp' => $panel->hasOtp(),
                    ],
                ],
                'notifications' => fn () => $request->session()->get('notifications', []),
            ]);
        }

        return $next($request);
    }
}
