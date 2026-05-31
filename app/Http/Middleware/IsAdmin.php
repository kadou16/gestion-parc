<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
   
    public function handle(Request $request, Closure $next): Response
    {
        // On s'assure que l'utilisateur est connecté et est un Admin
        if ($request->user() && $request->user()->role === 'Admin') {
            return $next($request);
        }
        if ($request->isMethod('get') && $request->user() && $request->user()->role === 'Conducteur') {
            // Seuls les conducteurs peuvent lire certaines données, mais pas écrire
            return $next($request);
        }

        return response()->json(['message' => 'Accès refusé. Vous devez être Administrateur pour effectuer cette action.'], 403);
    }
}
