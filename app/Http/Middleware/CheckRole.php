<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('role:Administrador,Chef')
     * The user must have one of the listed role names to pass.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Load role relationship if not already loaded
        $user->loadMissing('role');

        if (empty($roles) || in_array($user->role?->name, $roles)) {
            return $next($request);
        }

        abort(403, 'No tienes permisos para acceder a esta sección.');
    }
}
