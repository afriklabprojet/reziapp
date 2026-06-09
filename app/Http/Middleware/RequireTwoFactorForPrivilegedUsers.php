<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorForPrivilegedUsers
{
    private const PRIVILEGED_ROLES = ['owner', 'admin', 'super_admin'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, self::PRIVILEGED_ROLES, true)) {
            return $next($request);
        }

        if ($user->two_factor_enabled) {
            return $next($request);
        }

        if ($request->routeIs('security.setup-2fa') || $request->routeIs('two-factor.*')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'La double authentification est obligatoire pour votre compte.',
                'error_code' => 'TWO_FACTOR_REQUIRED',
                'setup_url' => route('security.setup-2fa'),
            ], 403);
        }

        return redirect()->route('security.setup-2fa')
            ->with('warning', 'Vous devez configurer la double authentification pour accéder à cette fonctionnalité.');
    }
}
