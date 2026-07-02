<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user && method_exists($user, 'hasRole') && $user->hasRole($roles)) {
            return $next($request);
        }

        return response()->json(['message' => 'Accès refusé. Rôle non autorisé.'], 403);
    }
}
